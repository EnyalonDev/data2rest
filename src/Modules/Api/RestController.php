<?php

namespace App\Modules\Api;

use App\Core\Database;
use App\Core\Config;
use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\ApiPermissionManager;
use App\Core\QueryFilterBuilder;
use App\Core\ApiCacheManager;
use App\Core\ApiVersionManager;
use App\Core\BulkOperationsManager;
use App\Modules\Webhooks\WebhookDispatcher;
use App\Modules\Media\ImageService;
use PDO;

/**
 * REST API Controller
 * 
 * Main controller for the RESTful API providing full CRUD operations
 * on database tables with advanced features.
 * 
 * Core Features:
 * - RESTful endpoints (GET, POST, PUT/PATCH, DELETE)
 * - API key authentication
 * - Internal session bypass for dashboard users
 * - Foreign key relationship resolution
 * - File upload handling via multipart/form-data
 * - Pagination and filtering
 * - Field projection (selective column retrieval)
 * - Webhook integration for events
 * - Complete audit trail logging
 * 
 * Authentication:
 * - API Key via X-API-KEY header or api_key parameter
 * - Internal session support for authenticated dashboard users
 * - Automatic API key tracking in audit logs
 * 
 * Query Features:
 * - Pagination: ?limit=50&offset=0
 * - Filtering: ?field_name=value (supports LIKE with %)
 * - Field selection: ?fields=id,name,email
 * - Foreign key auto-resolution with _label suffix
 * 
 * Events:
 * - record.created - Triggered on POST
 * - record.updated - Triggered on PUT/PATCH
 * - record.deleted - Triggered on DELETE
 * 
 * @package App\Modules\Api
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * RestController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class RestController extends BaseController
{
    /**
     * API key data for the current request
     * 
     * @var array|null
     */
    private $apiKeyData;

    /**
     * Authenticate API request
     * 
     * Validates API key from X-API-KEY header or api_key parameter.
     * Supports internal session bypass for authenticated dashboard users.
     * 
     * @return array API key data or internal session data
     * @throws void Outputs JSON error and exits on authentication failure
     * 
     * @example
     * Header: X-API-KEY: your_api_key_here
     * OR
     * Query: ?api_key=your_api_key_here
     */
    private function authenticate()
    {
        Auth::init();

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $apiKey = $headers['X-API-KEY'] ?? $headers['X-API-Key'] ?? $headers['x-api-key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

        // Exemption for authentication routes (Login, Register, Google Auth)
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isAuthRoute = (strpos($uri, '/auth/') !== false);

        // Internal bypass for authenticated dashboard users
        if (!$apiKey && (Auth::check() || $isAuthRoute)) {
            header('X-Data2Rest-Auth: ' . ($isAuthRoute ? 'Public-Auth-Route' : 'Internal-Session'));
            return ['name' => $isAuthRoute ? 'Public Auth Route' : 'Internal Console Session', 'key_value' => 'internal'];
        }

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

        header('X-Data2Rest-Auth: API-Key');
        return $keyData;
    }

    /**
     * Main API request handler
     * 
     * Routes HTTP requests to appropriate CRUD handlers based on method.
     * Supports method spoofing via _method parameter for PUT/PATCH with files.
     * 
     * @param string|int $db_id Database ID or name
     * @param string $table Table name
     * @param int|null $id Optional record ID for single record operations
     * @return void Outputs JSON response
     * 
     * @example
     * GET /api/db/1/users - List all users
     * GET /api/db/1/users/5 - Get user with ID 5
     * POST /api/db/1/users - Create new user
     * PUT /api/db/1/users/5 - Update user 5
     * DELETE /api/db/1/users/5 - Delete user 5
     */
    /**
     * handle method
     *
     * @return void
     */
    public function handle($db_id, $table, $id = null)
    {
        $this->apiKeyData = $this->authenticate();

        // Phase 1: Rate Limiting
        if ($this->apiKeyData['key_value'] !== 'internal') {
            $rateLimiter = new RateLimiter();
            $endpoint = "db_{$db_id}_table_{$table}";

            // Get custom limit from API key or use default
            $limit = $this->apiKeyData['rate_limit'] ?? RateLimiter::DEFAULT_LIMIT;
            $limitInfo = $rateLimiter->checkLimit($this->apiKeyData['id'], $endpoint, $limit);

            // Set rate limit headers
            $rateLimiter->setHeaders($limitInfo);

            if (!$limitInfo['allowed']) {
                $this->json([
                    'error' => 'Rate limit exceeded',
                    'message' => "You have exceeded the rate limit of {$limitInfo['limit']} requests per hour",
                    'retry_after' => $limitInfo['reset'] - time()
                ], 429);
            }
        }

        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        $sysDb = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();
        $qDatabases = $adapter->quoteName('databases');
        // Support finding by ID or by name
        $stmt = $sysDb->prepare("SELECT * FROM $qDatabases WHERE id = ? OR name = ? OR REPLACE(LOWER(name), ' ', '_') = ?");
        $stmt->execute([$db_id, $db_id, strtolower($db_id)]);
        $database = $stmt->fetch();

        if (!$database) {
            $this->json(['error' => "Database container '$db_id' not found"], 404);
        }

        // Phase 1: Permission Check & IP Whitelisting
        if ($this->apiKeyData['key_value'] !== 'internal') {
            $permManager = new ApiPermissionManager();

            // Check IP whitelist
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!$permManager->checkIpWhitelist($this->apiKeyData['id'], $clientIp)) {
                Logger::log('API_BLOCKED_IP', [
                    'api_key' => $this->apiKeyData['name'],
                    'ip' => $clientIp,
                    'database' => $database['name'],
                    'table' => $table
                ]);
                $this->json([
                    'error' => 'Access denied',
                    'message' => 'Your IP address is not whitelisted for this API key'
                ], 403);
            }

            // Determine operation from HTTP method
            $method = $_SERVER['REQUEST_METHOD'];
            if ($method === 'POST' && isset($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            }

            $operation = match ($method) {
                'GET' => 'read',
                'POST' => 'create',
                'PUT', 'PATCH' => 'update',
                'DELETE' => 'delete',
                default => 'read'
            };

            // Check table-level permission
            if (!$permManager->hasPermission($this->apiKeyData['id'], $database['id'], $table, $operation)) {
                Logger::log('API_PERMISSION_DENIED', [
                    'api_key' => $this->apiKeyData['name'],
                    'database' => $database['name'],
                    'table' => $table,
                    'operation' => $operation
                ]);
                $this->json([
                    'error' => 'Permission denied',
                    'message' => "API key does not have '$operation' permission for table '$table'"
                ], 403);
            }
        }

        try {
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $targetDb = $adapter->getConnection();
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
                    Logger::log('API_GET', "Table: $table" . ($id ? " ID: $id" : ""), $db_id);
                    $this->handleGetRequest($targetDb, $table, $id, $fieldsConfig, $db_id, $adapter);
                    break;

                case 'POST':
                    Logger::log('API_POST', ['table' => $table], $database['id']);
                    $this->handlePostRequest($targetDb, $table, $database['id'], $adapter);
                    break;

                case 'PUT':
                case 'PATCH':
                    Logger::log('API_UPDATE', ['table' => $table, 'id' => $id, 'method' => $method], $database['id']);
                    $this->handleUpdateRequest($targetDb, $table, $id, $database['id'], $adapter);
                    break;

                case 'DELETE':
                    Logger::log('API_DELETE', ['table' => $table, 'id' => $id], $database['id']);
                    $this->handleDeleteRequest($targetDb, $table, $id, $database['id'], $adapter);
                    break;

                default:
                    $this->json(['error' => 'Method not allowed'], 405);
            }
        } catch (\PDOException $e) {
            $this->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            $this->json(['error' => 'System error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper to get column names for any supported database
     */
    private function getDbColumns($targetDb, $table, $adapter)
    {
        $driver = $adapter->getType();
        if ($driver === 'sqlite') {
            $stmt = $targetDb->query("PRAGMA table_info(" . $adapter->quoteName($table) . ")");
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 1); // name
        } elseif ($driver === 'mysql') {
            $stmt = $targetDb->query("SHOW COLUMNS FROM " . $adapter->quoteName($table));
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Field
        } elseif ($driver === 'pgsql') {
            $stmt = $targetDb->query($adapter->getTableStructureSQL($table));
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // name
        }
        return [];
    }


    private function handleGetRequest($targetDb, $table, $id, $fieldsConfig, $db_id, $adapter)
    {
        // Request parameters
        $params = $_GET;
        unset($params['api_key']);

        // Phase 2: Version Manager
        $versionManager = new ApiVersionManager();
        $versionManager->setVersionHeaders();

        // Phase 2: Cache Check (DISABLED as per user request for fresh data)
        // $cacheManager = new ApiCacheManager();
        // $endpoint = "db_{$db_id}_table_{$table}" . ($id ? "_id_{$id}" : "_list");
        // $cacheKey = $cacheManager->getCacheKey($endpoint, $params);
        // $cachedResponse = $cacheManager->get($cacheKey);

        // if ($cachedResponse) {
        //     $etag = $cacheManager->generateETag($cachedResponse);
        //     if ($cacheManager->isClientCacheValid($etag)) {
        //         $cacheManager->send304NotModified();
        //     }
        //     $cacheManager->setCacheHeaders($etag);
        //     // Ensure response is consistent with version
        //     $this->json($versionManager->transformResponse($cachedResponse));
        //     return;
        // }

        // 1. Base Select and Joins
        $selectFields = ["t.*"];
        $joins = [];

        foreach ($fieldsConfig as $field) {
            if ($field['is_foreign_key'] && !empty($field['related_table'])) {
                $alias = "ref_" . $field['field_name'];
                $relTable = $field['related_table'];

                // Determine display field for the related table
                $displayField = $this->getDisplayField($targetDb, $db_id, $relTable, $field['related_field']);

                $qRelTable = $adapter->quoteName($relTable);
                $qField = $adapter->quoteName($field['field_name']);
                $qDisplay = $adapter->quoteName($displayField);

                $joins[] = "LEFT JOIN $qRelTable $alias ON t.$qField = $alias.id";
                $selectFields[] = "$alias.$qDisplay AS " . $adapter->quoteName($field['field_name'] . "_label");
            }
        }

        // 2. Column selection override
        if (!empty($params['fields'])) {
            $requested = explode(',', preg_replace('/[^a-zA-Z0-9_,]/', '', $params['fields']));
            $selectFields = [];
            foreach ($requested as $r) {
                // If it's a basic field, we prefix with t.
                $selectFields[] = "t." . $adapter->quoteName($r);
                // Also try to find if it has a label from our joins
                foreach ($fieldsConfig as $f) {
                    if ($f['field_name'] === $r && $f['is_foreign_key']) {
                        $labelDisplay = $this->getDisplayField($targetDb, $db_id, $f['related_table'], $f['related_field']);
                        $selectFields[] = "ref_$r." . $adapter->quoteName($labelDisplay) . " AS " . $adapter->quoteName($r . "_label");
                    }
                }
            }
            unset($params['fields']);
        }

        $sqlSelect = implode(', ', $selectFields);
        $sqlJoins = implode(' ', $joins);

        if ($id) {
            $qTable = $adapter->quoteName($table);
            $sql = "SELECT $sqlSelect FROM $qTable t $sqlJoins WHERE t.id = ?";
            $stmt = $targetDb->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            if (!$result)
                $this->json(['error' => 'Record not found'], 404);

            // Phase 2: Save to Cache (DISABLED)
            // $cacheManager->store($cacheKey, $result);
            // $etag = $cacheManager->generateETag($result);
            // $cacheManager->setCacheHeaders($etag);

            $this->json($versionManager->transformResponse($result));
        } else {
            // Check max limit based on version
            $maxLimit = $versionManager->getVersionConfig('max_limit', 100);

            // Pagination
            $limit = (int) ($params['limit'] ?? $versionManager->getVersionConfig('default_limit', 50));
            $limit = min($limit, $maxLimit);
            $offset = (int) ($params['offset'] ?? 0);

            // Validate columns for filtering
            $validCols = $this->getDbColumns($targetDb, $table, $adapter);

            // Phase 1: Advanced Filtering
            $filterResult = QueryFilterBuilder::buildFilters($params, $validCols, $adapter);
            $where = $filterResult['where'];
            $values = $filterResult['values'];

            $qTable = $adapter->quoteName($table);
            $sql = "SELECT $sqlSelect FROM $qTable t $sqlJoins";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }

            // Phase 1: Advanced Sorting
            $sortClause = QueryFilterBuilder::buildSort($params['sort'] ?? null, $validCols, $adapter);
            if ($sortClause) {
                $sql .= $sortClause;
            } else {
                $sql .= " ORDER BY t.id DESC";
            }

            $sql .= " LIMIT ? OFFSET ?";
            $values[] = $limit;
            $values[] = $offset;

            $stmt = $targetDb->prepare($sql);
            $stmt->execute($values);
            $results = $stmt->fetchAll();

            // Metadata for response
            $qCountTable = $adapter->quoteName($table);
            $countSql = "SELECT COUNT(*) FROM $qCountTable t";
            if (!empty($where))
                $countSql .= " WHERE " . implode(' AND ', $where);
            $total = $targetDb->prepare($countSql);
            // Only use filter values for count
            $filterValues = array_slice($values, 0, count(QueryFilterBuilder::buildFilters($params, $validCols, $adapter)['values']));
            $total->execute($filterValues);
            $totalCount = $total->fetchColumn();

            $response = [
                'metadata' => [
                    'total_records' => (int) $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => count($results)
                ],
                'data' => $results
            ];

            // Phase 2: Save to Cache (DISABLED)
            // $cacheManager->store($cacheKey, $response);
            // $etag = $cacheManager->generateETag($response);
            // $cacheManager->setCacheHeaders($etag);

            $this->json($versionManager->transformResponse($response));
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
            $adapter = Database::getInstance()->getAdapter();
            $qDatabases = $adapter->quoteName('databases');
            $db_stmt = $sysDb->prepare("SELECT * FROM $qDatabases WHERE id = ?");
            $db_stmt->execute([$db_id]);
            $database_config = $db_stmt->fetch();
            if (!$database_config)
                return 'id';
            $adapter = \App\Core\DatabaseManager::getAdapter($database_config);

            $columns = $this->getDbColumns($targetDb, $tableName, $adapter);
            foreach ($columns as $colName) {
                // getDbColumns returns array of names directly now
                if (in_array(strtolower($colName), ['nombre', 'name', 'title', 'titulo', 'label'])) {
                    return $colName;
                }
            }
        } catch (\Exception $e) {
        }

        return 'id';
    }

    private function handlePostRequest($targetDb, $table, $db_id, $adapter)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // Process file uploads if present (images only for pages, general for others)
        $allowedExt = ($table === 'web_pages') ? ['jpg', 'jpeg', 'png', 'webp', 'avif'] : ['jpg', 'jpeg', 'png', 'webp', 'avif', 'pdf', 'txt', 'doc', 'docx', 'odt', 'md', 'rar', 'zip'];
        $filesData = $this->processUploads($db_id, $table, $allowedExt);
        $input = array_merge($input, $filesData);

        // Filter input to only include actual table columns
        $validCols = $this->getDbColumns($targetDb, $table, $adapter);
        $input = array_intersect_key($input, array_flip($validCols));

        if (empty($input))
            $this->json(['error' => 'No valid data provided'], 400);

        if (!isset($input['fecha_de_creacion']) && in_array('fecha_de_creacion', $validCols))
            $input['fecha_de_creacion'] = Auth::getCurrentTime();
        if (!isset($input['fecha_edicion']) && in_array('fecha_edicion', $validCols))
            $input['fecha_edicion'] = Auth::getCurrentTime();

        $keys = array_keys($input);
        // Quote keys
        $cols = implode(', ', array_map(function ($k) use ($adapter) {
            return $adapter->quoteName($k);
        }, $keys));
        $vals = implode(', ', array_fill(0, count($keys), '?'));

        $qTable = $adapter->quoteName($table);
        $stmt = $targetDb->prepare("INSERT INTO $qTable ($cols) VALUES ($vals)");
        $stmt->execute(array_values($input));
        $newId = $targetDb->lastInsertId();

        // Audit Trail: Log Insert
        try {
            $sysDb = Database::getInstance()->getConnection();
            $stmtLog = $sysDb->prepare("INSERT INTO data_versions (database_id, table_name, record_id, action, old_data, new_data, user_id, api_key_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtLog->execute([
                $db_id,
                $table,
                $newId,
                'INSERT',
                null,
                json_encode($input),
                0, // No session user
                $this->apiKeyData['id'] ?? null
            ]);

            // Log specifically which API Key did this in activity logs
            Logger::log('API_INSERT', [
                'table' => $table,
                'id' => $newId,
                'api_key' => $this->apiKeyData['name'] ?? 'Unknown'
            ], $db_id);
        } catch (\Exception $e) {
        }

        // Webhook Trigger
        try {
            $sysDb = Database::getInstance()->getConnection();
            $adapter = Database::getInstance()->getAdapter();
            $qDatabases = $adapter->quoteName('databases');
            $projectStmt = $sysDb->prepare("SELECT project_id FROM $qDatabases WHERE id = ?");
            $projectStmt->execute([$db_id]);
            $projectId = $projectStmt->fetchColumn();

            if ($projectId) {
                WebhookDispatcher::dispatch($projectId, 'record.created', [
                    'database_id' => $db_id,
                    'table' => $table,
                    'id' => $newId,
                    'data' => $input
                ]);
            }
        } catch (\Exception $e) {
            // Ignore webhook failures
        }

        // Phase 2: Invalidate Cache
        $cacheManager = new ApiCacheManager();
        $cacheManager->invalidate("db_{$db_id}_table_{$table}");

        $this->json(['success' => true, 'id' => $newId], 201);
    }

    private function handleUpdateRequest($targetDb, $table, $id, $db_id, $adapter)
    {
        if (!$id)
            $this->json(['error' => 'ID required'], 400);

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // Process file uploads
        $allowedExt = ($table === 'web_pages') ? ['jpg', 'jpeg', 'png', 'webp'] : ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt', 'doc', 'docx', 'odt', 'md', 'rar', 'zip'];
        $filesData = $this->processUploads($db_id, $table, $allowedExt);
        $input = array_merge($input, $filesData);

        // Filter input to only include actual table columns
        $validCols = $this->getDbColumns($targetDb, $table, $adapter);
        $input = array_intersect_key($input, array_flip($validCols));

        // id should not be updated manually
        unset($input['id']);

        if (empty($input))
            $this->json(['error' => 'No valid data to update'], 400);

        if (in_array('fecha_edicion', $validCols)) {
            $input['fecha_edicion'] = Auth::getCurrentTime();
        }

        if ($id) {
            $qTable = $adapter->quoteName($table);
            $stmtFetch = $targetDb->prepare("SELECT * FROM $qTable WHERE id = ?");
            $stmtFetch->execute([$id]);
            $oldData = $stmtFetch->fetch(PDO::FETCH_ASSOC);

            if ($oldData) {
                try {
                    $sysDb = Database::getInstance()->getConnection();
                    $stmtLog = $sysDb->prepare("INSERT INTO data_versions (database_id, table_name, record_id, action, old_data, new_data, user_id, api_key_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtLog->execute([
                        $db_id,
                        $table,
                        $id,
                        'UPDATE',
                        json_encode($oldData),
                        json_encode($input),
                        0,
                        $this->apiKeyData['id'] ?? null
                    ]);

                    // Extra activity log with key attribution
                    Logger::log('API_UPDATE_DETAILED', [
                        'table' => $table,
                        'id' => $id,
                        'api_key' => $this->apiKeyData['name'] ?? 'Unknown'
                    ], $db_id);
                } catch (\Exception $e) { /* Ignore log failure */
                }
            }
        }

        $sets = [];
        foreach ($input as $key => $val) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $sets[] = $adapter->quoteName($safeKey) . " = ?";
        }
        $qTable = $adapter->quoteName($table);
        $sql = "UPDATE $qTable SET " . implode(', ', $sets) . " WHERE id = ?";

        $stmt = $targetDb->prepare($sql);
        $stmt->execute(array_merge(array_values($input), [$id]));

        // Webhook Trigger
        try {
            $sysDb = Database::getInstance()->getConnection();
            $adapter = Database::getInstance()->getAdapter();
            $qDatabases = $adapter->quoteName('databases');
            $projectStmt = $sysDb->prepare("SELECT project_id FROM $qDatabases WHERE id = ?");
            $projectStmt->execute([$db_id]);
            $projectId = $projectStmt->fetchColumn();

            if ($projectId) {
                WebhookDispatcher::dispatch($projectId, 'record.updated', [
                    'database_id' => $db_id,
                    'table' => $table,
                    'id' => $id,
                    'changes' => $input
                ]);
            }
        } catch (\Exception $e) {
        }

        // Phase 2: Invalidate Cache
        $cacheManager = new ApiCacheManager();
        $cacheManager->invalidate("db_{$db_id}_table_{$table}");

        $this->json(['success' => true]);
    }

    private function handleDeleteRequest($targetDb, $table, $id, $db_id, $adapter)
    {
        if (!$id)
            $this->json(['error' => 'ID required'], 400);

        // Fetch data before delete for webhook payload (optional but good practice)
        $qTable = $adapter->quoteName($table);
        $stmtFetch = $targetDb->prepare("SELECT * FROM $qTable WHERE id = ?");
        $stmtFetch->execute([$id]);
        $oldData = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if ($oldData) {
            try {
                $sysDb = Database::getInstance()->getConnection();
                $stmtLog = $sysDb->prepare("INSERT INTO data_versions (database_id, table_name, record_id, action, old_data, new_data, user_id, api_key_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtLog->execute([
                    $db_id,
                    $table,
                    $id,
                    'DELETE',
                    json_encode($oldData),
                    null,
                    0,
                    $this->apiKeyData['id'] ?? null
                ]);

                // Extra activity log with key attribution
                Logger::log('API_DELETE_DETAILED', [
                    'table' => $table,
                    'id' => $id,
                    'api_key' => $this->apiKeyData['name'] ?? 'Unknown'
                ], $db_id);
            } catch (\Exception $e) { /* Ignore log failure */
            }
        }

        $qTable = $adapter->quoteName($table);
        $stmt = $targetDb->prepare("DELETE FROM $qTable WHERE id = ?");
        $stmt->execute([$id]);

        // Webhook Trigger
        try {
            if ($oldData) {
                $sysDb = Database::getInstance()->getConnection();
                $adapter = Database::getInstance()->getAdapter();
                $qDatabases = $adapter->quoteName('databases');
                $projectStmt = $sysDb->prepare("SELECT project_id FROM $qDatabases WHERE id = ?");
                $projectStmt->execute([$db_id]);
                $projectId = $projectStmt->fetchColumn();

                if ($projectId) {
                    WebhookDispatcher::dispatch($projectId, 'record.deleted', [
                        'database_id' => $db_id,
                        'table' => $table,
                        'id' => $id,
                        'data' => $oldData
                    ]);
                }
            }
        } catch (\Exception $e) {
        }

        // Phase 2: Invalidate Cache
        $cacheManager = new ApiCacheManager();
        $cacheManager->invalidate("db_{$db_id}_table_{$table}");

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
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf', 'txt', 'doc', 'docx', 'odt', 'md', 'rar', 'zip'];
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

                $imageService = new ImageService();
                $safeName = $imageService->process($file['tmp_name'], $absoluteDir, $safeName);

                if (file_exists($absoluteDir . $safeName)) {
                    $data[$field] = Auth::getFullBaseUrl() . 'uploads/' . str_replace('//', '/', $relativeDir . $safeName);
                }
            }
        }
        return $data;
    }

    /**
     * Handle bulk operations (Phase 2)
     * 
     * POST /api/db/{db_id}/{table}/bulk
     * 
     * @param int $db_id Database ID
     * @param string $table Table name
     * @return void
     */
    public function handleBulk($db_id, $table)
    {
        $this->apiKeyData = $this->authenticate();

        // Check API version supports bulk
        $versionManager = new ApiVersionManager();
        if (!$versionManager->getVersionConfig('supports_bulk', false)) {
            $this->json([
                'error' => 'Bulk operations not supported in this API version',
                'message' => 'Please use API v2 or higher for bulk operations'
            ], 400);
        }

        // Rate limiting
        if ($this->apiKeyData['key_value'] !== 'internal') {
            $rateLimiter = new RateLimiter();
            $endpoint = "db_{$db_id}_table_{$table}_bulk";
            $limit = $this->apiKeyData['rate_limit'] ?? RateLimiter::DEFAULT_LIMIT;
            $limitInfo = $rateLimiter->checkLimit($this->apiKeyData['id'], $endpoint, $limit);
            $rateLimiter->setHeaders($limitInfo);

            if (!$limitInfo['allowed']) {
                $this->json([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $limitInfo['reset'] - time()
                ], 429);
            }
        }

        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        // Get database
        $sysDb = Database::getInstance()->getConnection();
        $sysDb = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();
        $qDatabases = $adapter->quoteName('databases');
        $stmt = $sysDb->prepare("SELECT * FROM $qDatabases WHERE id = ? OR name = ?");
        $stmt->execute([$db_id, $db_id]);
        $database = $stmt->fetch();

        if (!$database) {
            $this->json(['error' => "Database '$db_id' not found"], 404);
        }

        // Get request body
        $input = file_get_contents('php://input');
        $requestData = json_decode($input, true);

        if (!isset($requestData['operations']) || !is_array($requestData['operations'])) {
            $this->json([
                'error' => 'Invalid request format',
                'message' => 'Expected JSON with "operations" array'
            ], 400);
        }

        try {
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $targetDb = $adapter->getConnection();

            $bulkManager = new BulkOperationsManager($targetDb);
            $result = $bulkManager->execute($requestData['operations'], $table, $adapter);

            // Invalidate cache for this table
            $cacheManager = new ApiCacheManager();
            $cacheManager->invalidate("db_{$db_id}_table_{$table}");

            Logger::log('API_BULK_OPERATION', [
                'database' => $database['name'],
                'table' => $table,
                'operations_count' => count($requestData['operations']),
                'success_count' => $result['summary']['success'],
                'failed_count' => $result['summary']['failed']
            ]);

            $statusCode = $result['success'] ? 200 : 207; // 207 = Multi-Status
            $this->json($result, $statusCode);

        } catch (\Exception $e) {
            $this->json([
                'error' => 'Bulk operation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Override json response to log analytics (Phase 3)
     */
    protected function json($data, $status = 200)
    {
        // Calculate response time
        $start = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $time = microtime(true) - $start;

        // Attempt to capture API Key ID even if not fully authenticated yet
        $apiKeyId = $this->apiKeyData['id'] ?? null;

        // If not authenticated, try to find the key provided in headers to log "Attempted Access"
        if (!$apiKeyId) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $apiKey = $headers['X-API-KEY'] ?? $headers['X-API-Key'] ?? $headers['x-api-key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

            if ($apiKey) {
                try {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT id FROM api_keys WHERE key_value = ?");
                    $stmt->execute([$apiKey]);
                    $apiKeyId = $stmt->fetchColumn();
                } catch (\Exception $e) {
                }
            }
        }

        // Log to api_access_logs
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO api_access_logs (api_key_id, method, endpoint, status_code, ip_address, response_time_ms, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");

            // Truncate URI if too long
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            if (strlen($uri) > 255)
                $uri = substr($uri, 0, 255);

            $stmt->execute([
                $apiKeyId, // Can be null if unknown key
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $uri,
                $status,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                round($time * 1000, 2),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {
            // Ignore logging errors to prevent API failure
        }

        // CSV/Excel Export (Phase 3)
        if (isset($_GET['format']) && $status == 200 && isset($data['data'])) {
            $format = strtolower($_GET['format']);
            if ($format === 'csv') {
                $this->exportCsv($data['data']);
                return;
            }
            if ($format === 'xlsx' || $format === 'excel') {
                $this->exportExcel($data['data']);
                return;
            }
        }

        parent::json($data, $status);
    }

    /**
     * Export data to CSV
     */
    private function exportCsv($data)
    {
        if (empty($data)) {
            parent::json(['error' => 'No data to export'], 404);
            return;
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // Header row
        if (isset($data[0])) {
            fputcsv($out, array_keys($data[0]));
        }

        foreach ($data as $row) {
            // Flatten arrays if any
            $flatRow = array_map(function ($item) {
                return is_array($item) ? json_encode($item) : $item;
            }, $row);
            fputcsv($out, $flatRow);
        }

        fclose($out);
        exit;
    }

    /**
     * Export data to XML Excel (Simple)
     */
    private function exportExcel($data)
    {
        if (empty($data)) {
            parent::json(['error' => 'No data to export'], 404);
            return;
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="export_' . date('Y-m-d_His') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<table border="1">';

        // Header
        if (isset($data[0])) {
            echo '<tr>';
            foreach (array_keys($data[0]) as $key) {
                echo '<th>' . htmlspecialchars($key) . '</th>';
            }
            echo '</tr>';
        }

        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                $val = is_array($cell) ? json_encode($cell) : $cell;
                echo '<td>' . htmlspecialchars($val) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }
}
