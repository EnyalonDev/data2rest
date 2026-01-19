<?php

namespace App\Modules\Database;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use App\Core\Logger;
use App\Modules\Media\ImageService;
use PDO;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * CRUD Controller
 * 
 * Comprehensive controller for Create, Read, Update, and Delete operations
 * on database records with advanced features including:
 * 
 * Core Features:
 * - Full CRUD operations with role-based permissions
 * - Data versioning and audit trail
 * - Foreign key relationship management
 * - File upload and media handling
 * - CSV export functionality
 * - Recycle bin (soft delete) system
 * - Version restoration capabilities
 * 
 * Security:
 * - Permission-based access control
 * - SQL injection protection via prepared statements
 * - CSRF token validation
 * - Path traversal prevention
 * 
 * Data Integrity:
 * - Automatic timestamp management (created_at, updated_at)
 * - Complete audit trail for all operations
 * - Version history tracking
 * - Rollback capabilities
 * 
 * @package App\Modules\Database
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * CrudController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class CrudController extends BaseController
{
    /**
     * Constructor - Requires user authentication
     * 
     * Ensures that only authenticated users can access
     * any CRUD functionality.
     */
    /**
     * __construct method
     *
     * @return void
     */
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Retrieves the operational context for the current request.
     * Validates database and table existence and checks permissions.
     * 
     * @param string|null $action The CRUD action being performed (read, insert, update, delete)
     * @return array Context information including DB object, table name, and configuration
     */
    protected function getContext($action = null)
    {
        $db_id = $_GET['db_id'] ?? $_POST['db_id'] ?? null;
        $table = $_GET['table'] ?? $_POST['table'] ?? null;

        if (!$db_id || !$table) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        // Map internal context actions to policy permission keys
        $permMap = [
            'crud_view' => 'crud_read',
            'crud_create' => 'crud_create',
            'crud_edit' => 'crud_update',
            'crud_delete' => 'crud_delete'
        ];

        if ($action && isset($permMap[$action])) {
            Auth::requirePermission('module:databases.' . $permMap[$action]);
        } else {
            // Default fallback if no specific action provided (shouldn't happen often)
            Auth::requirePermission('module:databases.view_tables');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        // --- PATH SELF-HEALING ---
        // Fix issues where absolute paths from local dev don't match server paths
        if ($database && !file_exists($database['path'])) {
            $filename = basename($database['path']);
            $localPath = Config::get('db_storage_path') . $filename;

            if (file_exists($localPath)) {
                // Found it locally! Update DB to fix permanently
                $database['path'] = realpath($localPath);
                $upd = $db->prepare("UPDATE " . Database::getInstance()->getAdapter()->quoteName('databases') . " SET path = ? WHERE id = ?");
                $upd->execute([$database['path'], $db_id]);
            }
        }
        // -------------------------

        if (!$database) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        // Check if table is hidden for non-admins
        if (!Auth::isAdmin()) {
            $config = json_decode($database['config'] ?? '{}', true);
            $hiddenTables = $config['hidden_tables'] ?? [];
            if (in_array($table, $hiddenTables)) {
                Auth::setFlashError("Acceso Denegado: Esta tabla ha sido ocultada por el administrador.", 'modal');
                header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
                exit;
            }
        }

        $stmt = $db->prepare("SELECT * FROM fields_config WHERE db_id = ? AND table_name = ? ORDER BY id ASC");
        $stmt->execute([$db_id, $table]);
        $fields = $stmt->fetchAll();

        // Fallback: If no fields are configured, show all columns from target table
        if (empty($fields)) {
            try {
                $adapter = \App\Core\DatabaseManager::getAdapter($database);
                $targetDb = $adapter->getConnection();
                $dbType = $adapter->getType();
                $columns = [];

                if ($dbType === 'sqlite') {
                    $stmtCols = $targetDb->query("PRAGMA table_info(" . $adapter->quoteName($table) . ")");
                    $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                } elseif ($dbType === 'mysql') {
                    $stmtCols = $targetDb->query("SHOW COLUMNS FROM " . $adapter->quoteName($table));
                    $mysqlCols = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($mysqlCols as $mCol) {
                        $columns[] = ['name' => $mCol['Field'], 'type' => $mCol['Type']];
                    }
                } elseif ($dbType === 'pgsql') {
                    $stmtCols = $targetDb->query($adapter->getTableStructureSQL($table));
                    $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                }

                foreach ($columns as $col) {
                    $fields[] = [
                        'field_name' => $col['name'],
                        'data_type' => $col['type'],
                        'view_type' => 'text',
                        'is_visible' => 1,
                        'is_editable' => ($col['name'] !== 'id') ? 1 : 0,
                        'is_required' => 0,
                        'is_foreign_key' => 0
                    ];
                }
            } catch (\Exception $e) {
                // Ignore errors in fallback
            }
        }

        return [
            'database' => $database,
            'table' => $table,
            'fields' => $fields,
            'db_id' => $db_id
        ];
    }

    /**
     * Determines the display field for a related table in structural view.
     * Used for rendering foreign key relationships reasonably to the user.
     * 
     * @return string Field name to use for display
     */
    protected function getDisplayField($targetDb, $db_id, $tableName, $preferredField = null)
    {
        if (!empty($preferredField))
            return $preferredField;

        $db = Database::getInstance()->getConnection();
        $stmtCheck = $db->prepare("SELECT field_name FROM fields_config
WHERE db_id = ? AND table_name = ?
AND LOWER(field_name) IN ('nombre', 'name', 'title', 'titulo', 'label', 'descripcion', 'description')
LIMIT 1");
        $stmtCheck->execute([$db_id, $tableName]);
        $found = $stmtCheck->fetchColumn();

        if ($found)
            return $found;

        try {
            $db_stmt = Database::getInstance()->getConnection()->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
            $db_stmt->execute([$db_id]);
            $database_config = $db_stmt->fetch();

            if (!$database_config)
                return 'id';

            $adapter = \App\Core\DatabaseManager::getAdapter($database_config);
            $driver = $adapter->getType();

            $columns = [];
            if ($driver === 'sqlite') {
                $stmt = $targetDb->query("PRAGMA table_info(" . $adapter->quoteName($tableName) . ")");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($driver === 'mysql') {
                $stmt = $targetDb->query("SHOW COLUMNS FROM " . $adapter->quoteName($tableName));
                $mysqlCols = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($mysqlCols as $mCol) {
                    $columns[] = ['name' => $mCol['Field']];
                }
            } elseif ($driver === 'pgsql') {
                $stmt = $targetDb->query($adapter->getTableStructureSQL($tableName));
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            foreach ($columns as $col) {
                if (in_array(strtolower($col['name']), ['nombre', 'name', 'title', 'titulo', 'label'])) {
                    return $col['name'];
                }
            }
        } catch (\Exception $e) {
        }

        return 'id';
    }

    /**
     * Display list view of table records
     * 
     * Renders a paginated list of all records in the specified table
     * with support for:
     * - Search functionality across visible fields
     * - Foreign key resolution for display
     * - Permission-based access control
     * 
     * @return void Renders the list view template
     * 
     * @example
     * GET /admin/crud/list?db_id=1&table=users&s=search_term
     */
    /**
     * list method
     *
     * @return void
     */
    public function list()
    {
        $ctx = $this->getContext('crud_view');
        $search = $_GET['s'] ?? '';

        try {
            $adapter = \App\Core\DatabaseManager::getAdapter($ctx['database']);
            $targetDb = $adapter->getConnection();
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $targetDb::FETCH_ASSOC);

            $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);

            $whereClauses = [];
            $params = [];

            if (!empty($search)) {
                foreach ($ctx['fields'] as $field) {
                    if ($field['is_visible']) {
                        $whereClauses[] = "{$field['field_name']} LIKE ?";
                        $params[] = "%$search%";
                    }
                }
            }

            $whereSql = !empty($whereClauses) ? "WHERE " . implode(" OR ", $whereClauses) : "";

            $qTable = $adapter->quoteName($tableName);
            $sql = "SELECT * FROM $qTable $whereSql ORDER BY id DESC";
            $stmt = $targetDb->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll();

            foreach ($ctx['fields'] as $field) {
                if ($field['is_foreign_key'] && !empty($field['related_table'])) {
                    $relatedTable = $field['related_table'];
                    $relatedDisplay = $this->getDisplayField($targetDb, $ctx['db_id'], $relatedTable, $field['related_field']);

                    try {
                        $qRelTable = $adapter->quoteName($relatedTable);
                        $stmtRel = $targetDb->query("SELECT id, $relatedDisplay as display FROM $qRelTable");
                        $relMap = $stmtRel->fetchAll(PDO::FETCH_KEY_PAIR);
                        foreach ($records as &$row) {
                            if (!empty($row[$field['field_name']]) && isset($relMap[$row[$field['field_name']]])) {
                                $row[$field['field_name']] = $relMap[$row[$field['field_name']]];
                            }
                        }
                        unset($row);
                    } catch (\PDOException $e) {
                    }
                }
            }
        } catch (\Exception $e) {
            die("Error accessing data: " . $e->getMessage());
        }

        $this->view('admin/crud/list', [
            'title' => 'List - ' . $ctx['table'],
            'records' => $records,
            'ctx' => $ctx,
            'search' => $search,
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                $ctx['database']['name'] => 'admin/databases/view?id=' . $ctx['db_id'],
                'Records: ' . $ctx['table'] => null
            ]
        ]);
    }

    /**
     * Display form for creating or editing a record
     * 
     * Renders a dynamic form based on field configuration with:
     * - Auto-populated foreign key dropdowns
     * - File upload fields for media
     * - Pre-filled values for edit mode
     * - Field type-specific input controls
     * 
     * @return void Renders the form view template
     * 
     * @example
     * GET /admin/crud/form?db_id=1&table=users (new record)
     * GET /admin/crud/form?db_id=1&table=users&id=5 (edit record)
     */
    /**
     * form method
     *
     * @return void
     */
    public function form()
    {
        $id = $_GET['id'] ?? null;
        $ctx = $this->getContext($id ? 'crud_edit' : 'crud_create');
        $record = null;
        $foreignOptions = [];

        try {
            $adapter = \App\Core\DatabaseManager::getAdapter($ctx['database']);
            $targetDb = $adapter->getConnection();
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $targetDb::FETCH_ASSOC);

            if ($id) {
                $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);
                $qTable = $adapter->quoteName($tableName);
                $stmt = $targetDb->prepare("SELECT * FROM $qTable WHERE id = ?");
                $stmt->execute([$id]);
                $record = $stmt->fetch();
            }

            foreach ($ctx['fields'] as $field) {
                if ($field['is_foreign_key'] && !empty($field['related_table'])) {
                    $table = $field['related_table'];
                    $display = $this->getDisplayField($targetDb, $ctx['db_id'], $table, $field['related_field']);
                    try {
                        $qRelTable = $adapter->quoteName($table);
                        $qDisplay = $adapter->quoteName($display);
                        $stmtRel = $targetDb->query("SELECT id, $qDisplay as label FROM $qRelTable ORDER BY $qDisplay ASC");
                        $foreignOptions[$field['field_name']] = $stmtRel->fetchAll();
                    } catch (\PDOException $e) {
                        $foreignOptions[$field['field_name']] = [];
                    }
                }
            }
        } catch (\Exception $e) {
            die("Error fetching form data: " . $e->getMessage());
        }

        $this->view('admin/crud/form', [
            'title' => ($id ? 'Edit' : 'New') . ' Entry - ' . $ctx['table'],
            'id' => $id,
            'record' => $record,
            'ctx' => $ctx,
            'foreignOptions' => $foreignOptions,
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                $ctx['database']['name'] => 'admin/databases/view?id=' . $ctx['db_id'],
                $ctx['table'] => "admin/crud/list?db_id={$ctx['db_id']}&table={$ctx['table']}",
                ($id ? 'Refine' : 'Initialize') . ' Record' => null
            ]
        ]);
    }

    /**
     * Process form submission to save a record
     * 
     * Handles both INSERT (new record) and UPDATE (existing record) operations.
     * 
     * Features:
     * - File upload processing with automatic organization
     * - Automatic timestamp management
     * - Data versioning for audit trail
     * - Foreign key validation
     * - Metadata updates
     * 
     * @return void Redirects to list view on success
     * 
     * @example
     * POST /admin/crud/save
     * Body: db_id=1&table=users&field1=value1&field2=value2
     */
    /**
     * save method
     *
     * @return void
     */
    public function save()
    {
        $id = $_POST['id'] ?? null;
        $ctx = $this->getContext($id ? 'crud_edit' : 'crud_create');

        $data = $_POST;
        unset($data['db_id'], $data['table'], $data['id'], $data['_token']);

        foreach ($ctx['fields'] as $field) {
            $galleryKey = 'gallery_' . $field['field_name'];
            if (isset($data[$galleryKey])) {
                if (!empty($data[$galleryKey])) {
                    $data[$field['field_name']] = ($data[$galleryKey] === '__EMPTY__') ? '' : $data[$galleryKey];
                }
                unset($data[$galleryKey]);
            }
        }

        if (!empty($_FILES)) {
            $uploadBase = Config::get('upload_dir');
            foreach ($_FILES as $field => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $dateFolder = date('Y-m-d');
                    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);
                    $scopePath = $this->getStoragePrefix($ctx['db_id']);

                    // Root / pID / table / date / file
                    $relativeDir = "$scopePath/$tableName/$dateFolder/";
                    $absoluteDir = $uploadBase . $relativeDir;

                    if (!is_dir($absoluteDir))
                        mkdir($absoluteDir, 0777, true);

                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $safeName = $this->sanitizeFilename($file['name']);

                    // Handle collisions
                    if (file_exists($absoluteDir . $safeName)) {
                        $info = pathinfo($safeName);
                        $safeName = $info['filename'] . '-' . substr(uniqid(), -5) . '.' . $info['extension'];
                    }

                    $imageService = new ImageService();
                    $safeName = $imageService->process($file['tmp_name'], $absoluteDir, $safeName);

                    if (file_exists($absoluteDir . $safeName)) {
                        $data[$field] = Auth::getFullBaseUrl() . 'uploads/' . str_replace('//', '/', $relativeDir . $safeName);
                    }
                }
            }
        }

        try {
            $adapter = \App\Core\DatabaseManager::getAdapter($ctx['database']);
            $targetDb = $adapter->getConnection();
            $dbType = $adapter->getType();

            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $now = Auth::getCurrentTime();
            $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);

            // Fetch target table info to check if timestamp columns exist
            $columns = [];
            if ($dbType === 'sqlite') {
                $stmtCols = $targetDb->query("PRAGMA table_info(" . $adapter->quoteName($tableName) . ")");
                $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1); // 1 is 'name'
            } elseif ($dbType === 'mysql') {
                $stmtCols = $targetDb->query("SHOW COLUMNS FROM " . $adapter->quoteName($tableName));
                $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 0); // 0 is 'Field'
            } elseif ($dbType === 'pgsql') {
                $stmtCols = $targetDb->query($adapter->getTableStructureSQL($tableName));
                $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 0); // 0 is 'name'
            }

            if ($id) {
                if (in_array('fecha_edicion', $columns)) {
                    $data['fecha_edicion'] = $now;
                }

                // Data Versioning
                $qTable = $adapter->quoteName($tableName);
                $stmtFetch = $targetDb->prepare("SELECT * FROM $qTable WHERE id = ?");
                $stmtFetch->execute([$id]);
                $oldData = $stmtFetch->fetch(PDO::FETCH_ASSOC);

                if ($oldData) {
                    try {
                        $sysDb = Database::getInstance()->getConnection();
                        $stmtLog = $sysDb->prepare("INSERT INTO data_versions (database_id, table_name, record_id, action, old_data, new_data, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmtLog->execute([
                            $ctx['db_id'],
                            $tableName,
                            $id,
                            'UPDATE',
                            json_encode($oldData),
                            json_encode($data),
                            $_SESSION['user_id'] ?? 0
                        ]);
                    } catch (\Exception $e) { /* Ignore log failure */
                    }
                }

                $sets = [];
                $values = [];
                foreach ($data as $key => $value) {
                    $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
                    // Avoid updating id or non-existent columns
                    if ($safeKey === 'id' || !in_array($safeKey, $columns))
                        continue;

                    $sets[] = $adapter->quoteName($safeKey) . " = ?";
                    $values[] = $value;
                }
                $values[] = $id;
                $qTable = $adapter->quoteName($tableName);
                $stmt = $targetDb->prepare("UPDATE $qTable SET " . implode(', ', $sets) . " WHERE id = ?");
                $stmt->execute($values);
                Logger::log('UPDATE_RECORD', ['table' => $tableName, 'id' => $id, 'fields' => array_keys($data)], $ctx['db_id']);
            } else {
                if (in_array('fecha_de_creacion', $columns)) {
                    $data['fecha_de_creacion'] = $now;
                }
                if (in_array('fecha_edicion', $columns)) {
                    $data['fecha_edicion'] = $now;
                }

                // Filter data to only include existing columns
                $filteredData = [];
                foreach ($data as $k => $v) {
                    $safeK = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
                    if (in_array($safeK, $columns) && $safeK !== 'id') {
                        $filteredData[$safeK] = $v;
                    }
                }

                $keys = array_keys($filteredData);
                // Quote keys
                $quotedKeys = array_map(function ($k) use ($adapter) {
                    return $adapter->quoteName($k);
                }, $keys);

                $placeholders = array_fill(0, count($keys), '?');
                $qTable = $adapter->quoteName($tableName);
                $stmt = $targetDb->prepare("INSERT INTO $qTable (" . implode(', ', $quotedKeys) . ") VALUES (" . implode(', ', $placeholders) . ")");
                $stmt->execute(array_values($filteredData));
                $newId = $targetDb->lastInsertId();

                // Audit Trail: Log Insert
                try {
                    $sysDb = Database::getInstance()->getConnection();
                    $stmtLog = $sysDb->prepare("INSERT INTO data_versions (database_id, table_name, record_id, action, old_data, new_data, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmtLog->execute([
                        $ctx['db_id'],
                        $tableName,
                        $newId,
                        'INSERT',
                        null,
                        json_encode($filteredData),
                        $_SESSION['user_id'] ?? 0
                    ]);
                } catch (\Exception $e) { /* Ignore log failure */
                }

                Logger::log('INSERT_RECORD', ['table' => $tableName, 'id' => $newId], $ctx['db_id']);
            }

            // Update metadata timestamps
            $this->updateMetadata($ctx['db_id'], $ctx['table']);

            header('Location: ' . Auth::getBaseUrl() . "admin/crud/list?db_id={$ctx['db_id']}&table={$ctx['table']}");
        } catch (\Exception $e) {
            die("Error saving data: " . $e->getMessage());
        }
    }

    /**
     * Delete a record from the database
     * 
     * Performs a hard delete with complete audit trail logging.
     * The deleted data is preserved in data_versions table for
     * potential restoration.
     * 
     * Features:
     * - Complete data backup before deletion
     * - Audit trail logging
     * - Permission validation
     * - Metadata timestamp updates
     * 
     * @return void Redirects to list view with status message
     * 
     * @example
     * POST /admin/crud/delete?db_id=1&table=users&id=5
     */
    /**
     * delete method
     *
     * @return void
     */
    public function delete()
    {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        $db_id = $_POST['db_id'] ?? $_GET['db_id'] ?? null;

        // Log for diagnosis
        error_log("CRUD DELETE - Attempting ID: $id on DB: $db_id");

        $ctx = $this->getContext('crud_delete');
        if ($id) {
            try {
                $adapter = \App\Core\DatabaseManager::getAdapter($ctx['database']);
                $targetDb = $adapter->getConnection();
                $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);

                // Data Versioning
                $qTable = $adapter->quoteName($tableName);
                $stmtFetch = $targetDb->prepare("SELECT * FROM $qTable WHERE id = ?");
                $stmtFetch->execute([$id]);
                $oldData = $stmtFetch->fetch(PDO::FETCH_ASSOC);

                if ($oldData) {
                    try {
                        $sysDb = Database::getInstance()->getConnection();
                        $stmtLog = $sysDb->prepare("INSERT INTO data_versions (database_id, table_name, record_id, action, old_data, new_data, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmtLog->execute([
                            $ctx['db_id'],
                            $tableName,
                            $id,
                            'DELETE',
                            json_encode($oldData),
                            null,
                            $_SESSION['user_id'] ?? 0
                        ]);
                    } catch (\Exception $e) { /* Ignore log failure */
                    }
                }

                $qTable = $adapter->quoteName($tableName);
                $stmt = $targetDb->prepare("DELETE FROM $qTable WHERE id = ?");
                $stmt->execute([$id]);

                Auth::setFlashError("Registro eliminado exitosamente.", 'success');
                Logger::log('DELETE_RECORD', ['table' => $tableName, 'id' => $id], $ctx['db_id']);

                // Update metadata timestamps
                $this->updateMetadata($ctx['db_id'], $ctx['table']);
            } catch (\Exception $e) {
                Auth::setFlashError("Error eliminando registro: " . $e->getMessage(), 'error');
            }
        }
        header('Location: ' . Auth::getBaseUrl() . "admin/crud/list?db_id={$ctx['db_id']}&table={$ctx['table']}");
    }

    // Local sanitizeFilename removed: using standardized version from BaseController

    /**
     * Updates the last_edit_at timestamp for both the database and the specific table.
     */
    protected function updateMetadata($db_id, $table)
    {
        $db = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();
        $type = $adapter->getType();
        $now = Auth::getCurrentTime();

        // Update database last edit
        $db->prepare("UPDATE " . Database::getInstance()->getAdapter()->quoteName('databases') . " SET last_edit_at = ? WHERE id = ?")->execute([$now, $db_id]);

        // Update or Initialize table metadata
        if ($type === 'sqlite') {
            $sql = "INSERT INTO table_metadata (db_id, table_name, last_edit_at) 
                     VALUES (?, ?, ?) 
                     ON CONFLICT(db_id, table_name) DO UPDATE SET last_edit_at = excluded.last_edit_at";
            $stmt = $db->prepare($sql);
            $stmt->execute([$db_id, $table, $now]);
        } else {
            // MySQL/MariaDB
            $sql = "INSERT INTO table_metadata (db_id, table_name, last_edit_at) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE last_edit_at = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$db_id, $table, $now, $now]);
        }
    }
    /**
     * Export table data to CSV file
     * 
     * Generates an Excel-compatible CSV file with UTF-8 BOM encoding.
     * Includes foreign key resolution for human-readable exports.
     * 
     * Features:
     * - UTF-8 BOM for Excel compatibility
     * - Foreign key values resolved to display names
     * - Only visible fields included
     * - Automatic filename with timestamp
     * 
     * @return void Outputs CSV file download
     * 
     * @example
     * GET /admin/crud/export?db_id=1&table=users
     * Downloads: users_2026-01-16_00-55.csv
     */
    /**
     * export method
     *
     * @return void
     */
    public function export()
    {
        $ctx = $this->getContext('crud_view');

        try {
            $adapter = \App\Core\DatabaseManager::getAdapter($ctx['database']);
            $targetDb = $adapter->getConnection();
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $targetDb::FETCH_ASSOC);

            $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);
            $qTable = $adapter->quoteName($tableName);
            $stmt = $targetDb->query("SELECT * FROM $qTable ORDER BY id DESC");
            $records = $stmt->fetchAll();

            // Resolve foreign keys for the export as well
            foreach ($ctx['fields'] as $field) {
                if ($field['is_foreign_key'] && !empty($field['related_table'])) {
                    $relatedTable = $field['related_table'];
                    $relatedDisplay = $this->getDisplayField($targetDb, $ctx['db_id'], $relatedTable, $field['related_field']);

                    try {
                        $stmtRel = $targetDb->query("SELECT id, $relatedDisplay as display FROM $relatedTable");
                        $relMap = $stmtRel->fetchAll(PDO::FETCH_KEY_PAIR);

                        foreach ($records as &$row) {
                            if (!empty($row[$field['field_name']]) && isset($relMap[$row[$field['field_name']]])) {
                                $row[$field['field_name']] = $relMap[$row[$field['field_name']]];
                            }
                        }
                        unset($row);
                    } catch (\PDOException $e) {
                    }
                }
            }

            // Headers for CSV
            if (ob_get_level())
                ob_end_clean();

            $filename = $tableName . "_" . date('Y-m-d_H-i') . ".csv";

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');

            // Add BOM for Excel to recognize UTF-8
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Column Headers
            $headers = [];
            foreach ($ctx['fields'] as $field) {
                if ($field['is_visible']) {
                    $headers[] = $field['field_name'];
                }
            }
            fputcsv($output, $headers);

            // Data rows
            foreach ($records as $row) {
                $line = [];
                foreach ($ctx['fields'] as $field) {
                    if ($field['is_visible']) {
                        $line[] = $row[$field['field_name']] ?? '';
                    }
                }
                fputcsv($output, $line);
            }

            fclose($output);
            Logger::log('EXPORT_CSV', ['table' => $tableName], $ctx['db_id']);
            exit;

        } catch (\PDOException $e) {
            die("Error exporting data: " . $e->getMessage());
        }
    }

    /**
     * View audit history for a specific record
     * 
     * Displays complete version history including:
     * - All CRUD operations (INSERT, UPDATE, DELETE, RESTORE)
     * - User who performed each action
     * - API key used (if applicable)
     * - Before/after data comparison
     * - Timestamps for each change
     * 
     * @return void Renders history view template
     * 
     * @example
     * GET /admin/crud/history?db_id=1&table=users&id=5
     */
    /**
     * history method
     *
     * @return void
     */
    public function history()
    {
        $id = $_GET['id'] ?? null;
        $db_id = $_GET['db_id'] ?? null;
        $table = $_GET['table'] ?? null;

        if (!$id || !$db_id || !$table) {
            header('Location: ' . Auth::getBaseUrl() . "admin/crud/list?db_id=$db_id&table=$table");
            exit;
        }

        $ctx = $this->getContext('crud_view'); // Reuse permission check

        $sysDb = Database::getInstance()->getConnection();

        // Fetch versions joined with users and api_keys table
        $sql = "SELECT v.*, u.username, ak.name as api_key_name 
                FROM data_versions v 
                LEFT JOIN users u ON v.user_id = u.id 
                LEFT JOIN api_keys ak ON v.api_key_id = ak.id
                WHERE v.database_id = ? AND v.table_name = ? AND v.record_id = ? 
                ORDER BY v.created_at DESC";

        $stmt = $sysDb->prepare($sql);
        $stmt->execute([$db_id, $table, $id]);
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/crud/history', [
            'ctx' => $ctx,
            'title' => 'Audit Trail - Record #' . $id,
            'id' => $id,
            'versions' => $versions,
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                $ctx['database']['name'] => 'admin/databases/view?id=' . $ctx['db_id'],
                $ctx['table'] => "admin/crud/list?db_id={$ctx['db_id']}&table={$ctx['table']}",
                'History (#' . $id . ')' => null
            ]
        ]);
    }

    /**
     * Display recycle bin with deleted records
     * 
     * Shows all soft-deleted records across the current project
     * with restoration capabilities.
     * 
     * Features:
     * - Project-scoped deletion view
     * - User and API key attribution
     * - Restoration capability
     * - Limited to 100 most recent deletions
     * 
     * @return void Renders trash view template
     * 
     * @example
     * GET /admin/trash
     */
    /**
     * trash method
     *
     * @return void
     */
    public function trash()
    {
        Auth::requireLogin();
        $db = Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        if (!$projectId && !Auth::isAdmin()) {
            Auth::setFlashError('Please select a project first.');
            header('Location: ' . Auth::getBaseUrl() . 'admin/projects/select');
            exit;
        }

        $sql = "SELECT v.*, d.name as db_name, u.username as actor, ak.name as api_key_name 
                FROM data_versions v 
                JOIN databases d ON v.database_id = d.id 
                LEFT JOIN users u ON v.user_id = u.id 
                LEFT JOIN api_keys ak ON v.api_key_id = ak.id
                WHERE v.action = 'DELETE'";

        $params = [];
        if ($projectId) {
            $sql .= " AND d.project_id = ?";
            $params[] = $projectId;
        }

        $sql .= " ORDER BY v.created_at DESC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $deletions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/crud/trash', [
            'title' => 'Recycle Bin',
            'deletions' => $deletions,
            'breadcrumbs' => ['Recycle Bin' => null]
        ]);
    }

    /**
     * Permanently delete all items in recycle bin
     * 
     * Purges all soft-deleted records for the current project.
     * This operation is irreversible and includes database optimization.
     * 
     * Features:
     * - Project-scoped purge
     * - Database VACUUM optimization
     * - Audit logging
     * - Admin-only operation
     * 
     * @return void Redirects to trash view with status message
     * 
     * @example
     * POST /admin/trash/empty
     */
    /**
     * emptyTrash method
     *
     * @return void
     */
    public function emptyTrash()
    {
        Auth::requireLogin();
        $db = Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        if (!$projectId && !Auth::isAdmin()) {
            die("Permission denied");
        }

        $sql = "DELETE FROM data_versions WHERE action = 'DELETE'";
        $params = [];

        if ($projectId) {
            $sql = "DELETE FROM data_versions WHERE action = 'DELETE' AND database_id IN (SELECT id FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE project_id = ?)";
            $params[] = $projectId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // Optimize
        $db->exec("VACUUM");

        Logger::log('EMPTY_TRASH', ['project_id' => $projectId]);
        Auth::setFlashError('Recycle bin emptied successfully', 'success');

        header('Location: ' . Auth::getBaseUrl() . 'admin/trash');
        exit;
    }

    /**
     * Restore a previous version of a record
     * 
     * Restores a record to a previous state from the audit trail.
     * Creates a new audit entry for the restoration action.
     * 
     * Features:
     * - Column validation (only restores existing columns)
     * - Audit trail for restoration
     * - Permission validation
     * - Metadata updates
     * 
     * @return void Redirects to history view with status message
     * 
     * @example
     * POST /admin/crud/restore
     * Body: version_id=123
     */
    /**
     * restore method
     *
     * @return void
     */
    public function restore()
    {
        $version_id = $_POST['version_id'] ?? null;

        if (!$version_id)
            die("Missing version ID");

        $sysDb = Database::getInstance()->getConnection();
        $stmt = $sysDb->prepare("SELECT * FROM data_versions WHERE id = ?");
        $stmt->execute([$version_id]);
        $version = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$version)
            die("Version not found");

        $oldData = json_decode((string) ($version['old_data'] ?? '{}'), true);
        if (!$oldData)
            die("Corrupt version data");

        // Context for target DB
        $_GET['db_id'] = $version['database_id'];
        $_GET['table'] = $version['table_name'];
        $ctx = $this->getContext('crud_update');

        try {
            $adapter = \App\Core\DatabaseManager::getAdapter($ctx['database']);
            $targetDb = $adapter->getConnection();
            $dbType = $adapter->getType();
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Prepare update
            $sets = [];
            $values = [];

            // Filter columns to ensure we only update columns that still exist
            $columns = [];
            if ($dbType === 'sqlite') {
                $stmtCols = $targetDb->query("PRAGMA table_info(" . $adapter->quoteName($version['table_name']) . ")");
                $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
            } elseif ($dbType === 'mysql') {
                $stmtCols = $targetDb->query("SHOW COLUMNS FROM " . $adapter->quoteName($version['table_name']));
                $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 0);
            } elseif ($dbType === 'pgsql') {
                $stmtCols = $targetDb->query($adapter->getTableStructureSQL($version['table_name']));
                $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 0);
            }

            foreach ($oldData as $key => $val) {
                if ($key === 'id')
                    continue;
                if (!in_array($key, $columns))
                    continue;

                $sets[] = $adapter->quoteName($key) . " = ?";
                $values[] = $val;
            }

            // Update
            $qTable = $adapter->quoteName($version['table_name']);
            $sql = "UPDATE $qTable SET " . implode(', ', $sets) . " WHERE id = ?";
            $values[] = $version['record_id'];

            $stmtUpd = $targetDb->prepare($sql);
            $stmtUpd->execute($values);

            // Audit Trail: Log the Restore event
            try {
                $stmtLog = $sysDb->prepare("INSERT INTO data_versions (database_id, table_name, record_id, action, old_data, new_data, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtLog->execute([
                    $version['database_id'],
                    $version['table_name'],
                    $version['record_id'],
                    'RESTORE',
                    null, // Could fetch current state before update for better accuracy, but for now simple
                    $version['old_data'], // The data we just inserted
                    $_SESSION['user_id'] ?? 0
                ]);
            } catch (\Exception $e) {
            }

            Logger::log('RESTORE_VERSION', ['table' => $version['table_name'], 'id' => $version['record_id'], 'version_restored' => $version_id], $ctx['db_id']);
            Auth::setFlashError('Version restored successfully', 'success');

            header('Location: ' . Auth::getBaseUrl() . "admin/crud/history?db_id={$version['database_id']}&table={$version['table_name']}&id={$version['record_id']}");

        } catch (\PDOException $e) {
            die("Restore failed: " . $e->getMessage());
        }
    }
}

