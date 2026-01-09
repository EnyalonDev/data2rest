<?php

namespace App\Modules\Api;

use App\Core\Database;
use App\Core\Config;
use App\Core\Auth;
use App\Core\BaseController;
use PDO;

class RestController extends BaseController
{
    private function authenticate()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $apiKey = $headers['X-API-KEY'] ?? $headers['X-API-Key'] ?? $headers['x-api-key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

        if (!$apiKey) {
            $this->json(['error' => 'API Key required (X-API-KEY header or api_key param)'], 401);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM api_keys WHERE key_value = ? AND status = 1");
        $stmt->execute([$apiKey]);
        $keyData = $stmt->fetch();

        if (!$keyData) {
            $this->json(['error' => 'Invalid or inactive API Key'], 403);
        }

        return $keyData;
    }

    public function handle($db_id, $table, $id = null)
    {
        $this->authenticate();

        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        $sysDb = Database::getInstance()->getConnection();
        // Support finding by ID or by name
        $stmt = $sysDb->prepare("SELECT * FROM databases WHERE id = ? OR name = ? OR REPLACE(LOWER(name), ' ', '_') = ?");
        $stmt->execute([$db_id, $db_id, strtolower($db_id)]);
        $database = $stmt->fetch();

        if (!$database) {
            $this->json(['error' => "Database container '$db_id' not found"], 404);
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $sysDb = Database::getInstance()->getConnection();
            $stmtConf = $sysDb->prepare("SELECT * FROM fields_config WHERE db_id = ? AND table_name = ?");
            $stmtConf->execute([$db_id, $table]);
            $fieldsConfig = $stmtConf->fetchAll();

            $method = $_SERVER['REQUEST_METHOD'];
            // Method spoofing for multipart/form-data (required for PATCH/PUT with files in PHP)
            if ($method === 'POST' && isset($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            }

            switch ($method) {
                case 'GET':
                    $this->handleGetRequest($targetDb, $table, $id, $fieldsConfig, $db_id);
                    break;

                case 'POST':
                    $this->handlePostRequest($targetDb, $table, $db_id);
                    break;

                case 'PUT':
                case 'PATCH':
                    $this->handleUpdateRequest($targetDb, $table, $id, $db_id);
                    break;

                case 'DELETE':
                    $this->handleDeleteRequest($targetDb, $table, $id);
                    break;

                default:
                    $this->json(['error' => 'Method not allowed'], 405);
            }
        } catch (\PDOException $e) {
            $this->json(['error' => 'Database error: ' . $e->getMessage()], 500);
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
                $this->json(['error' => 'Record not found'], 404);
            $this->json($result);
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
                if (is_string($val)) {
                    $val = trim($val);
                }

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

            $this->json([
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

    private function handlePostRequest($targetDb, $table, $db_id)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // Process file uploads if present (images only for pages, general for others)
        $allowedExt = ($table === 'web_pages') ? ['jpg', 'jpeg', 'png', 'webp'] : ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt', 'doc', 'docx', 'odt', 'md', 'rar', 'zip'];
        $filesData = $this->processUploads($db_id, $table, $allowedExt);
        $input = array_merge($input, $filesData);

        // Filter input to only include actual table columns
        $stmtCols = $targetDb->query("PRAGMA table_info($table)");
        $validCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
        $input = array_intersect_key($input, array_flip($validCols));

        if (empty($input))
            $this->json(['error' => 'No valid data provided'], 400);

        if (!isset($input['fecha_de_creacion']) && in_array('fecha_de_creacion', $validCols))
            $input['fecha_de_creacion'] = date('Y-m-d H:i:s');
        if (!isset($input['fecha_edicion']) && in_array('fecha_edicion', $validCols))
            $input['fecha_edicion'] = date('Y-m-d H:i:s');

        $keys = array_map(function ($k) {
            return preg_replace('/[^a-zA-Z0-9_]/', '', $k);
        }, array_keys($input));
        $cols = implode(', ', $keys);
        $vals = implode(', ', array_fill(0, count($keys), '?'));

        $stmt = $targetDb->prepare("INSERT INTO $table ($cols) VALUES ($vals)");
        $stmt->execute(array_values($input));

        $this->json(['success' => true, 'id' => $targetDb->lastInsertId()], 201);
    }

    private function handleUpdateRequest($targetDb, $table, $id, $db_id)
    {
        if (!$id)
            $this->json(['error' => 'ID required'], 400);

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // Process file uploads
        $allowedExt = ($table === 'web_pages') ? ['jpg', 'jpeg', 'png', 'webp'] : ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt', 'doc', 'docx', 'odt', 'md', 'rar', 'zip'];
        $filesData = $this->processUploads($db_id, $table, $allowedExt);
        $input = array_merge($input, $filesData);

        // Filter input to only include actual table columns
        $stmtCols = $targetDb->query("PRAGMA table_info($table)");
        $validCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
        $input = array_intersect_key($input, array_flip($validCols));

        // id should not be updated manually
        unset($input['id']);

        if (empty($input))
            $this->json(['error' => 'No valid data to update'], 400);

        if (in_array('fecha_edicion', $validCols)) {
            $input['fecha_edicion'] = date('Y-m-d H:i:s');
        }

        $sets = [];
        foreach ($input as $key => $val) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $sets[] = "$safeKey = ?";
        }
        $sql = "UPDATE $table SET " . implode(', ', $sets) . " WHERE id = ?";

        $stmt = $targetDb->prepare($sql);
        $stmt->execute(array_merge(array_values($input), [$id]));
        $this->json(['success' => true]);
    }

    private function handleDeleteRequest($targetDb, $table, $id)
    {
        if (!$id)
            $this->json(['error' => 'ID required'], 400);
        $stmt = $targetDb->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $this->json(['success' => true]);
    }

    /**
     * Processes files uploaded via multipart/form-data.
     * Logic similar to CrudController but adapted for the API.
     */
    private function processUploads($db_id, $table, $allowed = [])
    {
        if (empty($_FILES))
            return [];

        $data = [];
        $uploadBase = Config::get('upload_dir');
        $dateFolder = date('Y-m-d');
        // Standardize storage prefix (p1, p2, etc.)
        $scopePath = $this->getStoragePrefix($db_id);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        // Root / pID / table / date / file
        $relativeDir = "$scopePath/$safeTable/$dateFolder/";
        $absoluteDir = $uploadBase . $relativeDir;

        if (empty($allowed)) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'doc', 'docx', 'odt', 'md', 'rar', 'zip'];
        }

        foreach ($_FILES as $field => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                if (!is_dir($absoluteDir)) {
                    mkdir($absoluteDir, 0777, true);
                }

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) {
                    $this->json([
                        'error' => "Invalid file extension '.$ext'. Allowed: " . implode(', ', $allowed)
                    ], 400);
                }

                $safeName = $this->sanitizeFilename($file['name']);

                // Handle collisions like CrudController does
                if (file_exists($absoluteDir . $safeName)) {
                    $fi = pathinfo($safeName);
                    $safeName = $fi['filename'] . '-' . substr(uniqid(), -5) . '.' . $fi['extension'];
                }

                if (move_uploaded_file($file['tmp_name'], $absoluteDir . $safeName)) {
                    $data[$field] = Auth::getFullBaseUrl() . 'uploads/' . str_replace('//', '/', $relativeDir . $safeName);
                }
            }
        }
        return $data;
    }
}
