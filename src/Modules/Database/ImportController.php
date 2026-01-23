<?php

namespace App\Modules\Database;

use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Database;
use App\Core\DatabaseManager;
use App\Core\Logger;
use PDO;

/**
 * Import Controller
 * 
 * Handles JSON-based import for database schema and content.
 * Allows quick prototyping by defining tables and data in a single JSON payload.
 */
class ImportController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
        Auth::requirePermission('module:databases.create_table'); // Reusing a sensible permission
    }

    /**
     * Show the Import JSON form
     */
    public function index()
    {
        $dbId = $_GET['db_id'] ?? null;
        if (!$dbId) {
            $this->redirect('admin/databases');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$dbId]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Database not found.");
            $this->redirect('admin/databases');
            exit;
        }

        $this->view('admin/databases/import_json', [
            'title' => 'Import from JSON',
            'database' => $database,
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                ($database['name'] ?? 'Database') => 'admin/databases/view?id=' . $dbId,
                'Import JSON' => null
            ]
        ]);
    }

    /**
     * Process the JSON import
     */
    public function process()
    {
        $dbId = $_POST['db_id'] ?? null;
        $jsonContent = $_POST['json_payload'] ?? '';

        if (!$dbId || empty($jsonContent)) {
            Auth::setFlashError("Missing database ID or JSON content.");
            $this->redirect('admin/databases');
            exit;
        }

        // 1. Verify Database
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$dbId]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Database not found.");
            $this->redirect('admin/databases');
            exit;
        }

        // 2. Parse JSON
        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Auth::setFlashError("Invalid JSON: " . json_last_error_msg());
            $this->redirect('admin/databases/import-json?db_id=' . $dbId);
            exit;
        }

        // Check if it has schema or if we need to INFER it (Schema-less Mode)
        if (!isset($data['database_schema']['tables'])) {
            // Check if it's a simple key-value payload (Schema-less)
            $inferredSchema = $this->inferSchema($data);
            if ($inferredSchema) {
                // Transform to expected format
                $data = [
                    'database_schema' => ['tables' => $inferredSchema],
                    'content_payload' => $data
                ];
            } else {
                Auth::setFlashError("Invalid structure: missing 'database_schema.tables' and could not infer schema.");
                $this->redirect('admin/databases/import-json?db_id=' . $dbId);
                exit;
            }
        }

        try {
            // Get Adapter
            $adapter = DatabaseManager::getAdapter($database);
            $conn = $adapter->getConnection();

            $logs = [];
            $createdTables = 0;
            $insertedRows = 0;
            $errors = [];

            // 3. Create Tables (DDL is often implicit commit in MySQL, so let's do it outside main transaction or just handle it)
            // We won't wrap DDL in the same transaction as DML to verify progress incrementally.

            // 3. Create Tables
            foreach ($data['database_schema']['tables'] as $tableDef) {
                $tableName = $tableDef['name'];
                $fields = $tableDef['fields'];

                // Build Columns Definition
                $columnsSql = [];
                $pkSet = false;

                foreach ($fields as $field) {
                    // Heuristic Type Inference based on field name
                    $type = 'TEXT'; // Default for SQLite/MySQL mostly okay for this simple importer

                    if ($field === 'id') {
                        if ($adapter->getType() === 'sqlite') {
                            $type = 'INTEGER PRIMARY KEY AUTOINCREMENT';
                        } elseif ($adapter->getType() === 'mysql') {
                            $type = 'INT AUTO_INCREMENT PRIMARY KEY';
                        } else { // pgsql
                            $type = 'SERIAL PRIMARY KEY';
                        }
                        $pkSet = true;
                    } elseif (strpos($field, 'rating') !== false || strpos($field, 'price') !== false) {
                        $type = 'DECIMAL(10,2)';
                    } elseif (strpos($field, 'count') !== false) {
                        $type = 'INTEGER';
                    }

                    $columnsSql[] = $adapter->quoteName($field) . " $type";
                }

                // If no ID found, maybe add one? The user request schema includes 'id' in services/products but not site_config
                // We trust the schema provided.

                // Add default audit columns if they don't exist in the JSON definition
                // NOTE: 'id' might be explicitly defined in JSON. We check before adding.
                // But generally, for consistency with the system, we should ensure they exist.

                // Check for ID
                $hasId = false;
                foreach ($fields as $f) {
                    if ($f === 'id')
                        $hasId = true;
                }

                if (!$hasId) {
                    $columnDef = "";
                    if ($adapter->getType() === 'sqlite') {
                        $columnDef = "INTEGER PRIMARY KEY AUTOINCREMENT";
                    } elseif ($adapter->getType() === 'mysql') {
                        $columnDef = "INT AUTO_INCREMENT PRIMARY KEY";
                    } else { // pgsql
                        $columnDef = "SERIAL PRIMARY KEY";
                    }
                    array_unshift($columnsSql, $adapter->quoteName('id') . " " . $columnDef);
                }

                // Check for timestamps
                $hasCreated = false;
                $hasUpdated = false;
                foreach ($fields as $f) {
                    if ($f === 'fecha_de_creacion')
                        $hasCreated = true;
                    if ($f === 'fecha_edicion')
                        $hasUpdated = true;
                }

                if (!$hasCreated) {
                    $columnsSql[] = $adapter->quoteName('fecha_de_creacion') . " DATETIME DEFAULT CURRENT_TIMESTAMP";
                }
                if (!$hasUpdated) {
                    $columnsSql[] = $adapter->quoteName('fecha_edicion') . " DATETIME DEFAULT CURRENT_TIMESTAMP";
                    // Note: SQLite doesn't support ON UPDATE CURRENT_TIMESTAMP easily in create table without triggers
                }

                $sql = "CREATE TABLE IF NOT EXISTS " . $adapter->quoteName($tableName) . " (";
                $sql .= implode(", ", $columnsSql);
                $sql .= ")";

                $conn->exec($sql);
                $createdTables++;
                $logs[] = "Created table: $tableName";

                // Register in data2rest metadata if needed (fields_config)
                // We'll skip detailed metadata registration to keep it "addon" and lightweight, 
                // but usually the "Sync" function handles this. 
                // We can trigger a sync after import or user can click sync.
            }

            // 4. Populate Data
            if (isset($data['content_payload'])) {
                $payload = $data['content_payload'];

                foreach ($payload as $key => $records) {

                    // Start Heuristic Mapping
                    $targetTable = $key;
                    $schemaTable = null;

                    // 1. Direct Match
                    foreach ($data['database_schema']['tables'] as $t) {
                        if ($t['name'] === $targetTable) {
                            $schemaTable = $t;
                            break;
                        }
                    }

                    // 2. Heuristic Mappings (Suffixes, specific keys)
                    if (!$schemaTable) {
                        $aliases = [
                            'info' => 'site_config', // Common alias
                            'brand' => 'brand_config', // Specific alias for this user payload
                            'about' => 'about_section',
                            'contact' => 'contact_section',
                            'hero' => 'hero_section',
                            'gallery' => 'projects_gallery'
                        ];

                        // Check exact alias
                        if (isset($aliases[$key])) {
                            $targetTable = $aliases[$key];
                        }
                        // Check suffix match (e.g. 'gallery' -> 'projects_gallery')
                        else {
                            foreach ($data['database_schema']['tables'] as $t) {
                                if (str_ends_with($t['name'], $key) || str_ends_with($key, $t['name'])) {
                                    $targetTable = $t['name'];
                                    break;
                                }
                            }
                        }

                        // Try to find the ALIASHED table in schema
                        foreach ($data['database_schema']['tables'] as $t) {
                            if ($t['name'] === $targetTable) {
                                $schemaTable = $t;
                                break;
                            }
                        }
                    }

                    if (!$schemaTable) {
                        // Loose search: if key is in table name (e.g. 'brand' in 'brand_config')
                        foreach ($data['database_schema']['tables'] as $t) {
                            if (strpos($t['name'], $key) !== false || strpos($key, $t['name']) !== false) {
                                $schemaTable = $t;
                                $targetTable = $t['name'];
                                break;
                            }
                        }
                    }

                    if (!$schemaTable) {
                        // Log locally or debug, but don't stop everything. Just skip this key.
                        continue;
                    }

                    // Normalize records: 
                    // Case 1: Sequential array of items (standard list)
                    // Case 2: Object with an 'items' or 'projects' key that contains the list (e.g. services: { items: [...] })
                    // Case 3: Single Associative array (single record)

                    $rowsToInsert = [];

                    if (isset($records['items']) && is_array($records['items'])) {
                        $rowsToInsert = $records['items'];
                    } elseif (isset($records['projects']) && is_array($records['projects'])) {
                        $rowsToInsert = $records['projects'];
                    } elseif (array_keys($records) !== range(0, count($records) - 1)) {
                        // Associative array -> Single Record
                        $rowsToInsert[] = $records;
                    } else {
                        // Sequential array -> Multiple Records
                        $rowsToInsert = $records;
                    }

                    foreach ($rowsToInsert as $row) {
                        $columns = [];
                        $values = [];
                        $placeholders = [];

                        foreach ($row as $col => $val) {
                            // Only insert if column exists in schema
                            if (in_array($col, $schemaTable['fields'])) {
                                $columns[] = $adapter->quoteName($col);

                                // Automatic JSON encoding for arrays/objects
                                if (is_array($val) || is_object($val)) {
                                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                                }

                                $values[] = $val;
                                $placeholders[] = '?';
                            }
                        }

                        if (empty($columns))
                            continue;

                        try {
                            $sql = "INSERT INTO " . $adapter->quoteName($targetTable) . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute($values);
                            $insertedRows++;
                        } catch (\Exception $e) {
                            // Catch mostly duplicate key errors and continue
                            $errors[] = "Row in '$targetTable' failed: " . $e->getMessage();
                        }
                    }
                    $logs[] = "Populated table '$targetTable' with " . count($rowsToInsert) . " rows.";
                }
            }

            // Report results
            $msg = "Import Completed. Created $createdTables tables. Inserted $insertedRows records.";
            if (count($errors) > 0) {
                // Limit errors in flash message
                $preview = implode("; ", array_slice($errors, 0, 3));
                if (count($errors) > 3)
                    $preview .= "... and " . (count($errors) - 3) . " more.";
                $msg .= " (Warnings: $preview)";
                Auth::setFlashError($msg, 'warning'); // Use warning style if partial success
            } else {
                Auth::setFlashError($msg, 'success');
            }

            // Auto Update Metadata (System Sync) logic reuse if possible
            // update last edit
            $db->prepare("UPDATE databases SET last_edit_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$dbId]);

            Auth::setFlashError("Import Successful! Created $createdTables tables and inserted $insertedRows records.", 'success');

            // Redirect to Table View (Sync will likely happen automatically or be requested)
            $this->redirect("admin/databases/view?id=$dbId");

        } catch (\Exception $e) {
            if (isset($conn))
                $conn->rollBack();
            Logger::log('IMPORT_ERROR', ['error' => $e->getMessage(), 'db_id' => $dbId]);
            Auth::setFlashError("Import Error: " . $e->getMessage());
            $this->redirect('admin/databases/import-json?db_id=' . $dbId);
        }
    }

    /**
     * Infer database schema from a data payload
     */
    private function inferSchema($data)
    {
        $tables = [];

        foreach ($data as $key => $value) {
            $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key)); // CamelCase to snake_case
            $fields = [];

            // Determine sample for field extraction
            $sample = null;

            if (is_array($value)) {
                // Check if it is a list of items
                if (isset($value[0]) && is_array($value[0])) {
                    $sample = $value[0];
                }
                // Check for 'items', 'projects', 'plans' wrapper
                elseif (isset($value['items']) && is_array($value['items']) && isset($value['items'][0])) {
                    $sample = $value['items'][0];
                } elseif (isset($value['projects']) && is_array($value['projects']) && isset($value['projects'][0])) {
                    $sample = $value['projects'][0];
                } elseif (isset($value['plans']) && is_array($value['plans']) && isset($value['plans'][0])) {
                    $sample = $value['plans'][0];
                }
                // Associative array = Single Row Table
                elseif (!isset($value[0])) {
                    $sample = $value;
                }
            }

            if ($sample) {
                // Recursively check for "items" inside the sample? No, simple level 1 for now.
                $fields = array_keys($sample);

                // Exclude complex nested arrays from becoming columns if we want flat tables
                // But current logic simply makes them columns (TEXT type for JSON). This is fine.

                $tables[] = [
                    'name' => $tableName,
                    'fields' => $fields
                ];
            }
        }

        return count($tables) > 0 ? $tables : null;
    }
}
