<?php

namespace App\Modules\Database;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use App\Core\Logger;
use PDO;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * CRUD Controller
 * Handles the Create, Read, Update, and Delete operations for database records,
 * and also provides media management functionality.
 */
class CrudController extends BaseController
{
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
                $upd = $db->prepare("UPDATE databases SET path = ? WHERE id = ?");
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
                $targetDb = new PDO('sqlite:' . $database['path']);
                $stmtCols = $targetDb->query("PRAGMA table_info($table)");
                $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
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

    /**
     * Renders a list view of records for a specific table.
     */
    public function list()
    {
        $ctx = $this->getContext('crud_view');
        $targetPath = $ctx['database']['path'];
        $search = $_GET['s'] ?? '';

        try {
            $targetDb = new PDO('sqlite:' . $targetPath);
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

            $sql = "SELECT * FROM $tableName $whereSql ORDER BY id DESC";
            $stmt = $targetDb->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll();

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
        } catch (\PDOException $e) {
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
     * Renders the form to create or edit a record.
     */
    public function form()
    {
        $id = $_GET['id'] ?? null;
        $ctx = $this->getContext($id ? 'crud_edit' : 'crud_create');
        $record = null;
        $foreignOptions = [];

        try {
            $targetDb = new PDO('sqlite:' . $ctx['database']['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $targetDb::FETCH_ASSOC);

            if ($id) {
                $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);
                $stmt = $targetDb->prepare("SELECT * FROM $tableName WHERE id = ?");
                $stmt->execute([$id]);
                $record = $stmt->fetch();
            }

            foreach ($ctx['fields'] as $field) {
                if ($field['is_foreign_key'] && !empty($field['related_table'])) {
                    $table = $field['related_table'];
                    $display = $this->getDisplayField($targetDb, $ctx['db_id'], $table, $field['related_field']);
                    try {
                        $stmtRel = $targetDb->query("SELECT id, $display as label FROM $table ORDER BY $display ASC");
                        $foreignOptions[$field['field_name']] = $stmtRel->fetchAll();
                    } catch (\PDOException $e) {
                        $foreignOptions[$field['field_name']] = [];
                    }
                }
            }
        } catch (\PDOException $e) {
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
     * Processes form submission to save (insert or update) a record.
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

                    if (move_uploaded_file($file['tmp_name'], $absoluteDir . $safeName)) {
                        $data[$field] = Auth::getFullBaseUrl() . 'uploads/' . str_replace('//', '/', $relativeDir . $safeName);
                    }
                }
            }
        }

        try {
            $targetDb = new PDO('sqlite:' . $ctx['database']['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $now = Auth::getCurrentTime();
            $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);

            // Fetch target table info to check if timestamp columns exist
            $stmtCols = $targetDb->query("PRAGMA table_info($tableName)");
            $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);

            if ($id) {
                if (in_array('fecha_edicion', $columns)) {
                    $data['fecha_edicion'] = $now;
                }

                // Data Versioning
                $stmtFetch = $targetDb->prepare("SELECT * FROM $tableName WHERE id = ?");
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

                    $sets[] = "$safeKey = ?";
                    $values[] = $value;
                }
                $values[] = $id;
                $stmt = $targetDb->prepare("UPDATE $tableName SET " . implode(', ', $sets) . " WHERE id = ?");
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
                $placeholders = array_fill(0, count($keys), '?');
                $stmt = $targetDb->prepare("INSERT INTO $tableName (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $placeholders) . ")");
                $stmt->execute(array_values($filteredData));
                Logger::log('INSERT_RECORD', ['table' => $tableName, 'id' => $targetDb->lastInsertId()], $ctx['db_id']);
            }

            // Update metadata timestamps
            $this->updateMetadata($ctx['db_id'], $ctx['table']);

            header('Location: ' . Auth::getBaseUrl() . "admin/crud/list?db_id={$ctx['db_id']}&table={$ctx['table']}");
        } catch (\PDOException $e) {
            die("Error saving data: " . $e->getMessage());
        }
    }

    /**
     * Deletes a record from a specific table.
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
                $targetDb = new PDO('sqlite:' . $ctx['database']['path']);
                $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);

                // Data Versioning
                $stmtFetch = $targetDb->prepare("SELECT * FROM $tableName WHERE id = ?");
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

                $stmt = $targetDb->prepare("DELETE FROM $tableName WHERE id = ?");
                $stmt->execute([$id]);

                Auth::setFlashError("Registro eliminado exitosamente.", 'success');
                Logger::log('DELETE_RECORD', ['table' => $tableName, 'id' => $id], $ctx['db_id']);

                // Update metadata timestamps
                $this->updateMetadata($ctx['db_id'], $ctx['table']);
            } catch (\PDOException $e) {
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
        $now = Auth::getCurrentTime();

        // Update database last edit
        $db->prepare("UPDATE databases SET last_edit_at = ? WHERE id = ?")->execute([$now, $db_id]);

        // Update or Initialize table metadata
        $stmt = $db->prepare("INSERT INTO table_metadata (db_id, table_name, last_edit_at) 
                             VALUES (?, ?, ?) 
                             ON CONFLICT(db_id, table_name) DO UPDATE SET last_edit_at = excluded.last_edit_at");
        $stmt->execute([$db_id, $table, $now]);
    }
    /**
     * Exports the current table records to a CSV file (Excel compatible).
     */
    public function export()
    {
        $ctx = $this->getContext('crud_view');
        $targetPath = $ctx['database']['path'];

        try {
            $targetDb = new PDO('sqlite:' . $targetPath);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $targetDb::FETCH_ASSOC);

            $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);
            $stmt = $targetDb->query("SELECT * FROM $tableName ORDER BY id DESC");
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
     * View history of a record
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

        // Fetch versions joined with users table to show who made changes
        $sql = "SELECT v.*, u.username 
                FROM data_versions v 
                LEFT JOIN users u ON v.user_id = u.id 
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
     * Restore a version
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

        $oldData = json_decode($version['old_data'], true);
        if (!$oldData)
            die("Corrupt version data");

        // Context for target DB
        $_GET['db_id'] = $version['database_id'];
        $_GET['table'] = $version['table_name'];
        $ctx = $this->getContext('crud_update');

        try {
            $targetDb = new PDO('sqlite:' . $ctx['database']['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Prepare update
            $sets = [];
            $values = [];

            // Filter columns to ensure we only update columns that still exist
            $stmtCols = $targetDb->query("PRAGMA table_info({$version['table_name']})");
            $validCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);

            foreach ($oldData as $key => $val) {
                if ($key === 'id')
                    continue;
                if (!in_array($key, $validCols))
                    continue;

                $sets[] = "$key = ?";
                $values[] = $val;
            }

            // Update
            $sql = "UPDATE {$version['table_name']} SET " . implode(', ', $sets) . " WHERE id = ?";
            $values[] = $version['record_id'];

            $stmtUpd = $targetDb->prepare($sql);
            $stmtUpd->execute($values);

            Logger::log('RESTORE_VERSION', ['table' => $version['table_name'], 'id' => $version['record_id'], 'version_restored' => $version_id], $ctx['db_id']);
            Auth::setFlashError('Version restored successfully', 'success');

            header('Location: ' . Auth::getBaseUrl() . "admin/crud/history?db_id={$version['database_id']}&table={$version['table_name']}&id={$version['record_id']}");

        } catch (\PDOException $e) {
            die("Restore failed: " . $e->getMessage());
        }
    }
}

