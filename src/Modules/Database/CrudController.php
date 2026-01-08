<?php

namespace App\Modules\Database;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
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

        if ($action) {
            Auth::requirePermission("db:$db_id", $action);
        } else {
            Auth::requireDatabaseAccess($db_id);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
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

        try {
            $targetDb = new PDO('sqlite:' . $targetPath);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $targetDb::FETCH_ASSOC);

            $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);
            $stmt = $targetDb->query("SELECT * FROM $tableName ORDER BY id DESC");
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
        unset($data['db_id'], $data['table'], $data['id']);

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
                    $relativeDir = "$dateFolder/$tableName/";
                    $absoluteDir = $uploadBase . $relativeDir;
                    if (!is_dir($absoluteDir))
                        mkdir($absoluteDir, 0777, true);

                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newName = uniqid() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $absoluteDir . $newName)) {
                        $data[$field] = Auth::getFullBaseUrl() . 'uploads/' . $relativeDir . $newName;
                    }
                }
            }
        }

        try {
            $targetDb = new PDO('sqlite:' . $ctx['database']['path']);
            $now = date('Y-m-d H:i:s');
            $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);

            if ($id) {
                foreach ($ctx['fields'] as $field) {
                    if ($field['field_name'] === 'fecha_edicion')
                        $data['fecha_edicion'] = $now;
                }
                $sets = [];
                $values = [];
                foreach ($data as $key => $value) {
                    $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
                    $sets[] = "$safeKey = ?";
                    $values[] = $value;
                }
                $values[] = $id;
                $stmt = $targetDb->prepare("UPDATE $tableName SET " . implode(', ', $sets) . " WHERE id = ?");
                $stmt->execute($values);
            } else {
                foreach ($ctx['fields'] as $field) {
                    if ($field['field_name'] === 'fecha_de_creacion')
                        $data['fecha_de_creacion'] = $now;
                    if ($field['field_name'] === 'fecha_edicion')
                        $data['fecha_edicion'] = $now;
                }
                $keys = array_map(function ($k) {
                    return preg_replace('/[^a-zA-Z0-9_]/', '', $k);
                }, array_keys($data));
                $placeholders = array_fill(0, count($keys), '?');
                $stmt = $targetDb->prepare("INSERT INTO $tableName (" . implode(', ', $keys) . ") VALUES (" . implode(
                    ', ',
                    $placeholders
                ) . ")");
                $stmt->execute(array_values($data));
            }

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
        $ctx = $this->getContext('crud_delete');
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $targetDb = new PDO('sqlite:' . $ctx['database']['path']);
                $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $ctx['table']);
                $stmt = $targetDb->prepare("DELETE FROM $tableName WHERE id = ?");
                $stmt->execute([$id]);
            } catch (\PDOException $e) {
                die("Error deleting record: " . $e->getMessage());
            }
        }
        header('Location: ' . Auth::getBaseUrl() . "admin/crud/list?db_id={$ctx['db_id']}&table={$ctx['table']}");
    }

    /**
     * Lists media files (images, documents) for the media library.
     */
    public function mediaList()
    {
        // Media list is accessible to anyone logged in for now, but let's at least check login
        Auth::requireLogin();
        $uploadBase = Config::get('upload_dir');
        $fullBaseUrl = Auth::getFullBaseUrl();
        $files = [];
        $dates = [];
        $tables = [];

        if (is_dir($uploadBase)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadBase));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $path = $file->getPathname();
                    $relativePath = str_replace($uploadBase, '', $path);
                    $parts = explode(DIRECTORY_SEPARATOR, $relativePath);
                    if (count($parts) >= 3) {
                        $dateFolder = $parts[0];
                        $tableFolder = $parts[1];
                        $dates[] = $dateFolder;
                        $tables[] = $tableFolder;
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                            $files[] = [
                                'url' => $fullBaseUrl . 'uploads/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath),
                                'name' => $file->getFilename(),
                                'date_folder' => $dateFolder,
                                'table_folder' => $tableFolder,
                                'mtime' => $file->getMTime()
                            ];
                        }
                    }
                }
            }
        }
        usort($files, function ($a, $b) {
            return $b['mtime'] - $a['mtime'];
        });
        header('Content-Type: application/json');
        echo json_encode([
            'files' => $files,
            'available_dates' => array_values(array_unique($dates)),
            'available_tables' =>
                array_values(array_unique($tables))
        ]);
        exit;
    }
    /**
     * Handles file uploads to the system.
     */
    public function mediaUpload()
    {
        Auth::requireLogin();
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded or upload error'], 400);
        }

        $uploadBase = Config::get('upload_dir');
        $dateFolder = date('Y-m-d');
        $tableFolder = 'explorer'; // Default target for explorer uploads
        $relativeDir = "$dateFolder/$tableFolder/";
        $absoluteDir = $uploadBase . $relativeDir;

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $file = $_FILES['file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = uniqid() . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $absoluteDir . $newName)) {
            $url = Auth::getFullBaseUrl() . 'uploads/' . $relativeDir . $newName;
            $this->json([
                'url' => $url,
                'name' => $newName,
                'date_folder' => $dateFolder,
                'table_folder' => $tableFolder
            ]);
        }

        $this->json(['error' => 'Failed to move uploaded file'], 500);
    }
}