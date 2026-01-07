<?php

namespace App\Modules\Api;

use App\Core\Database;
use App\Core\Auth;
use PDO;

class RestController
{
    public function __construct()
    {
    }

    private function authenticate()
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
        if (!$apiKey) {
            $this->jsonResponse(['error' => 'API Key required (X-API-KEY header or api_key param)'], 401);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM api_keys WHERE key_value = ? AND status = 1");
        $stmt->execute([$apiKey]);
        $keyData = $stmt->fetch();

        if (!$keyData) {
            $this->jsonResponse(['error' => 'Invalid or inactive API Key'], 403);
        }

        return $keyData;
    }

    public function handle($db_id, $table, $id = null)
    {
        $this->authenticate();

        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        $sysDb = Database::getInstance()->getConnection();
        $stmt = $sysDb->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database)
            $this->jsonResponse(['error' => 'Database container not found'], 404);

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Get field configurations to detect Foreign Keys
            $stmtConf = $sysDb->prepare("SELECT * FROM fields_config WHERE db_id = ? AND table_name = ?");
            $stmtConf->execute([$db_id, $table]);
            $fieldsConfig = $stmtConf->fetchAll();

            $method = $_SERVER['REQUEST_METHOD'];

            switch ($method) {
                case 'GET':
                    $this->handleGetRequest($targetDb, $table, $id, $fieldsConfig, $db_id);
                    break;

                case 'POST':
                    $this->handlePostRequest($targetDb, $table);
                    break;

                case 'PUT':
                case 'PATCH':
                    $this->handleUpdateRequest($targetDb, $table, $id);
                    break;

                case 'DELETE':
                    $this->handleDeleteRequest($targetDb, $table, $id);
                    break;

                default:
                    $this->jsonResponse(['error' => 'Method not allowed'], 405);
            }
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    private function handleGetRequest($targetDb, $table, $id, $fieldsConfig, $db_id)
    {
        $params = $_GET;
        unset($params['api_key']);

        // 1. Base Select and Joins
        $selectFields = ["t.*"];
        $joins = [];

        foreach ($fieldsConfig as $field) {
            if ($field['is_foreign_key'] && !empty($field['related_table'])) {
                $alias = "ref_" . $field['field_name'];
                $relTable = $field['related_table'];

                // Determine display field for the related table
                $displayField = $this->getDisplayField($targetDb, $db_id, $relTable, $field['related_field']);

                $joins[] = "LEFT JOIN $relTable $alias ON t.{$field['field_name']} = $alias.id";
                $selectFields[] = "$alias.$displayField AS {$field['field_name']}_label";
            }
        }

        // 2. Column selection override
        if (!empty($params['fields'])) {
            $requested = explode(',', preg_replace('/[^a-zA-Z0-9_,]/', '', $params['fields']));
            $selectFields = [];
            foreach ($requested as $r) {
                // If it's a basic field, we prefix with t.
                $selectFields[] = "t.$r";
                // Also try to find if it has a label from our joins
                foreach ($fieldsConfig as $f) {
                    if ($f['field_name'] === $r && $f['is_foreign_key']) {
                        $selectFields[] = "ref_$r." . $this->getDisplayField($targetDb, $db_id, $f['related_table'], $f['related_field']) . " AS {$r}_label";
                    }
                }
            }
            unset($params['fields']);
        }

        $sqlSelect = implode(', ', $selectFields);
        $sqlJoins = implode(' ', $joins);

        if ($id) {
            $sql = "SELECT $sqlSelect FROM $table t $sqlJoins WHERE t.id = ?";
            $stmt = $targetDb->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            if (!$result)
                $this->jsonResponse(['error' => 'Record not found'], 404);
            $this->jsonResponse($result);
        } else {
            // Pagination
            $limit = (int) ($params['limit'] ?? 50);
            $offset = (int) ($params['offset'] ?? 0);
            unset($params['limit'], $params['offset']);

            // Filters
            $where = [];
            $values = [];

            // Validate columns for filtering
            $stmtCols = $targetDb->query("PRAGMA table_info($table)");
            $validCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);

            foreach ($params as $key => $val) {
                if (in_array($key, $validCols)) {
                    if (strpos($val, '%') !== false) {
                        $where[] = "t.$key LIKE ?";
                    } else {
                        $where[] = "t.$key = ?";
                    }
                    $values[] = $val;
                }
            }

            $sql = "SELECT $sqlSelect FROM $table t $sqlJoins";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY t.id DESC LIMIT ? OFFSET ?";
            $values[] = $limit;
            $values[] = $offset;

            $stmt = $targetDb->prepare($sql);
            $stmt->execute($values);
            $results = $stmt->fetchAll();

            // Metadata for response
            $countSql = "SELECT COUNT(*) FROM $table t";
            if (!empty($where))
                $countSql .= " WHERE " . implode(' AND ', $where);
            $total = $targetDb->prepare($countSql);
            $total->execute(array_slice($values, 0, count($where)));
            $totalCount = $total->fetchColumn();

            $this->jsonResponse([
                'metadata' => [
                    'total_records' => (int) $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => count($results)
                ],
                'data' => $results
            ]);
        }
    }

    private function getDisplayField($targetDb, $db_id, $tableName, $preferredField = null)
    {
        if (!empty($preferredField))
            return $preferredField;

        // Try to find common label fields in system config first
        $sysDb = Database::getInstance()->getConnection();
        $stmtCheck = $sysDb->prepare("SELECT field_name FROM fields_config 
                                   WHERE db_id = ? AND table_name = ? 
                                   AND LOWER(field_name) IN ('nombre', 'name', 'title', 'titulo', 'label') 
                                   LIMIT 1");
        $stmtCheck->execute([$db_id, $tableName]);
        $found = $stmtCheck->fetchColumn();
        if ($found)
            return $found;

        // Fallback to searching in actual table schema
        try {
            $stmt = $targetDb->query("PRAGMA table_info($tableName)");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                if (in_array(strtolower($col['name']), ['nombre', 'name', 'title', 'titulo', 'label'])) {
                    return $col['name'];
                }
            }
        } catch (\Exception $e) {
        }

        return 'id';
    }

    private function handlePostRequest($targetDb, $table)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($input))
            $this->jsonResponse(['error' => 'No data provided'], 400);

        if (!isset($input['fecha_de_creacion']))
            $input['fecha_de_creacion'] = date('Y-m-d H:i:s');
        if (!isset($input['fecha_edicion']))
            $input['fecha_edicion'] = date('Y-m-d H:i:s');

        $keys = array_map(function ($k) {
            return preg_replace('/[^a-zA-Z0-9_]/', '', $k); }, array_keys($input));
        $cols = implode(', ', $keys);
        $vals = implode(', ', array_fill(0, count($keys), '?'));

        $stmt = $targetDb->prepare("INSERT INTO $table ($cols) VALUES ($vals)");
        $stmt->execute(array_values($input));

        $this->jsonResponse(['success' => true, 'id' => $targetDb->lastInsertId()], 201);
    }

    private function handleUpdateRequest($targetDb, $table, $id)
    {
        if (!$id)
            $this->jsonResponse(['error' => 'ID required'], 400);
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input))
            $this->jsonResponse(['error' => 'No data'], 400);

        $input['fecha_edicion'] = date('Y-m-d H:i:s');
        $sets = [];
        foreach ($input as $key => $val) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $sets[] = "$safeKey = ?";
        }
        $sql = "UPDATE $table SET " . implode(', ', $sets) . " WHERE id = ?";

        $stmt = $targetDb->prepare($sql);
        $stmt->execute(array_merge(array_values($input), [$id]));
        $this->jsonResponse(['success' => true]);
    }

    private function handleDeleteRequest($targetDb, $table, $id)
    {
        if (!$id)
            $this->jsonResponse(['error' => 'ID required'], 400);
        $stmt = $targetDb->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $this->jsonResponse(['success' => true]);
    }

    private function jsonResponse($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
