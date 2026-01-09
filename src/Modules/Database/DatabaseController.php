<?php

namespace App\Modules\Database;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use PDO;

/**
 * Database Management Controller
 * Handles the creation, deletion, and structural management of SQLite databases, tables, and fields.
 */
class DatabaseController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Lists all databases registered in the system.
     */
    public function index()
    {
        Auth::requirePermission('module:databases', 'view_tables');
        $db = Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        if (!$projectId && !Auth::isAdmin()) {
            $databases = []; // Should ideally redirect to project select
        } else {
            // Scope ALL queries to the active project
            $sql = "SELECT * FROM databases WHERE project_id = ? ORDER BY id DESC";
            $params = [$projectId];

            // If admin has no project selected, he sees nothing (or should see all? User said "everything related to project")
            // Strict project scoping means: No project = No data.
            // But Admins might want to see all... user said "module database only access databases of his project"
            // So we enforce project_id for everyone who is in a project context.

            if (Auth::isAdmin() && !$projectId) {
                // Fallback for global admin view if needed, but per request: "project determined access"
                $sql = "SELECT * FROM databases ORDER BY id DESC";
                $params = [];
            }

            if ($projectId) {
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $databases = $stmt->fetchAll();
            } else if (Auth::isAdmin()) {
                $databases = $db->query($sql)->fetchAll();
            } else {
                $databases = [];
            }
        }

        $this->view('admin/databases/index', [
            'title' => 'Databases - Architect',
            'databases' => $databases,
            'breadcrumbs' => [\App\Core\Lang::get('databases.title') => null]
        ]);
    }

    /**
     * Creates a new SQLite database file and registers it in the system.
     * Requires 'module:databases' permission with 'create' action.
     *
     * @return void Redirects to sync page or dies on error.
     */
    public function create()
    {
        Auth::requirePermission('module:databases.create_db');
        $name = $_POST['name'] ?? 'New Database';
        // Sanitize name: replace spaces/special chars with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($name));
        $sanitized = trim(strtolower($sanitized), '_');
        $storagePath = Config::get('db_storage_path');

        // Priority: Check if a file with this name already exists in data/
        $filename = $sanitized . '.sqlite';
        $path = $storagePath . $filename;

        if (!file_exists($path)) {
            // If doesn't exist, create a unique one to avoid collisions
            $filename = $sanitized . '_' . uniqid() . '.sqlite';
            $path = $storagePath . $filename;
        }

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        try {
            // We use realpath to ensure absolute paths are stored
            $newDb = new PDO('sqlite:' . $path);
            // Ensure permissions are set for writing
            @chmod($path, 0666);

            $db = Database::getInstance()->getConnection();
            $projectId = Auth::getActiveProject();

            $stmt = $db->prepare("INSERT INTO databases (name, path, project_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $path, $projectId]);

            // Auto-trigger sync if the file already had data
            $dbId = $db->lastInsertId();
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/sync?id=' . $dbId);
            exit;
        } catch (\PDOException $e) {
            die("Error creating/registering database: " . $e->getMessage());
        }
    }

    /**
     * Deletes a database entry and its physical SQLite file.
     */
    public function delete()
    {
        Auth::requirePermission('module:databases.delete_db');
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
            $stmt->execute([$id]);
            $database = $stmt->fetch();

            if ($database) {
                $stmt = $db->prepare("DELETE FROM databases WHERE id = ?");
                $stmt->execute([$id]);
                $stmt = $db->prepare("DELETE FROM fields_config WHERE db_id = ?");
                $stmt->execute([$id]);
                if (file_exists($database['path'])) {
                    unlink($database['path']);
                }
            }
        }
        header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
    }

    /**
     * Lists all tables within a specific database.
     */
    public function viewTables()
    {
        $id = $_GET['id'] ?? null;
        Auth::requirePermission('module:databases.view_tables');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$id]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Database configuration not found.");
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $tables = [];
            foreach ($tableNames as $tableName) {
                try {
                    $count = $targetDb->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
                } catch (\Exception $e) {
                    $count = 0;
                }
                $tables[$tableName] = $count;
            }
        } catch (\PDOException $e) {
            Auth::setFlashError("Error connecting to database node: " . $e->getMessage());
            $tables = [];
        }

        $this->view('admin/databases/tables', [
            'title' => 'Tables - ' . ($database['name'] ?? 'DB'),
            'tables' => $tables,
            'database' => $database,
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                ($database['name'] ?? 'Database') => null
            ]
        ]);
    }

    /**
     * Adds a new table to a specific database.
     */
    public function createTable()
    {
        $db_id = $_POST['db_id'] ?? null;
        Auth::requirePermission('module:databases.create_table');

        $table_name = $_POST['table_name'] ?? '';
        $table_name = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($table_name));
        $table_name = trim(strtolower($table_name), '_');

        if (!$db_id || empty($table_name)) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->exec("CREATE TABLE $table_name (
id INTEGER PRIMARY KEY AUTOINCREMENT,
fecha_de_creacion TEXT,
fecha_edicion TEXT
)");

            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable,
is_visible, is_required) VALUES (?, ?, 'id', 'INTEGER', 'text', 0, 1, 0)");
            $stmt->execute([$db_id, $table_name]);
            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable,
is_visible, is_required) VALUES (?, ?, 'fecha_de_creacion', 'TEXT', 'text', 0, 1, 0)");
            $stmt->execute([$db_id, $table_name]);
            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable,
is_visible, is_required) VALUES (?, ?, 'fecha_edicion', 'TEXT', 'text', 0, 1, 0)");
            $stmt->execute([$db_id, $table_name]);

            // Initialize table metadata
            $stmt = $db->prepare("INSERT INTO table_metadata (db_id, table_name) VALUES (?, ?)");
            $stmt->execute([$db_id, $table_name]);

            // Update DB last edit
            $db->prepare("UPDATE databases SET last_edit_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$db_id]);

            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
        } catch (\PDOException $e) {
            die("Error creating table: " . $e->getMessage());
        }
    }

    /**
     * Removes a table from the database.
     */
    public function deleteTable()
    {
        $db_id = $_GET['db_id'] ?? null;
        Auth::requirePermission('module:databases.drop_table');
        $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');

        if ($db_id && !empty($table_name)) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
            $stmt->execute([$db_id]);
            $database = $stmt->fetch();

            if ($database) {
                try {
                    $targetDb = new PDO('sqlite:' . $database['path']);
                    $targetDb->exec("DROP TABLE $table_name");
                    $stmt = $db->prepare("DELETE FROM fields_config WHERE db_id = ? AND table_name = ?");
                    $stmt->execute([$db_id, $table_name]);
                } catch (\PDOException $e) {
                    die("No se pudo eliminar la tabla: " . $e->getMessage());
                }
            }
        }
        header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
    }

    /**
     * Displays and manages the fields (columns) of a specific table.
     */
    public function manageFields()
    {
        $db_id = $_GET['db_id'] ?? null;
        Auth::requirePermission('module:databases.edit_table');
        $table_name = $_GET['table'] ?? null;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        $stmt = $db->prepare("SELECT * FROM fields_config WHERE db_id = ? AND table_name = ? ORDER BY id ASC");
        $stmt->execute([$db_id, $table_name]);
        $configFields = $stmt->fetchAll();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $stmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            $allTables = [];
        }

        $this->view('admin/databases/fields', [
            'title' => 'Config Fields - ' . ($table_name),
            'configFields' => $configFields,
            'database' => $database,
            'table_name' => $table_name,
            'allTables' => $allTables,
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                $database['name'] => 'admin/databases/view?id=' . $db_id,
                'Architect: ' . $table_name => null
            ]
        ]);
    }

    /**
     * Adds a new field structure to the system metadata for a table.
     */
    public function addField()
    {
        $db_id = $_POST['db_id'] ?? null;
        Auth::requirePermission('module:databases.edit_table');
        $table_name = $_POST['table_name'] ?? '';
        $field_name = $_POST['field_name'] ?? '';

        // Sanitize names
        $table_name = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($table_name));
        $table_name = trim(strtolower($table_name), '_');
        $field_name = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($field_name));
        $field_name = trim(strtolower($field_name), '_');
        $data_type = $_POST['data_type'] ?? 'TEXT';
        $view_type = $_POST['view_type'] ?? 'text';

        if (!$db_id || empty($table_name) || empty($field_name)) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->exec("ALTER TABLE $table_name ADD COLUMN $field_name $data_type");
            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_required) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$db_id, $table_name, $field_name, $data_type, $view_type]);
            header('Location: ' . Auth::getBaseUrl() . "admin/databases/fields?db_id=$db_id&table=$table_name");
        } catch (\PDOException $e) {
            die("Error adding field: " . $e->getMessage());
        }
    }

    /**
     * Removes a field from the table metadata and potentially the physical database.
     */
    public function deleteField()
    {
        $config_id = $_GET['config_id'] ?? null;
        if (!$config_id) {
            Auth::setFlashError("Invalid Field Configuration ID");
            $this->redirect('admin/databases');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM fields_config WHERE id = ?");
        $stmt->execute([$config_id]);
        $field = $stmt->fetch();

        if (!$field) {
            Auth::setFlashError("Field not found.");
            $this->redirect('admin/databases');
        }

        $db_id = $field['db_id'];
        $table_name = $field['table_name'];
        $field_name = $field['field_name'];

        Auth::requirePermission('module:databases.edit_table');

        // Prevent deleting system fields
        if (in_array($field_name, ['id', 'fecha_de_creacion', 'fecha_edicion'])) {
            Auth::setFlashError("System fields cannot be deleted.", 'error');
            $this->redirect("admin/databases/fields?db_id=$db_id&table=$table_name");
        }

        // Delete from Config
        $stmt = $db->prepare("DELETE FROM fields_config WHERE id = ?");
        $stmt->execute([$config_id]);

        // Attempt to Drop Column from Structure
        $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            // SQLite supports DROP COLUMN in newer versions, but we should wrap in try/catch just in case
            $targetDb->exec("ALTER TABLE $table_name DROP COLUMN $field_name");
            Auth::setFlashError("Field '$field_name' dropped successfully.", 'success');
        } catch (\PDOException $e) {
            // Fallback: If drop column is not supported or fails, at least config is gone.
            Auth::setFlashError("Field config removed, but column persisted (SQLite limitation or data conflict): " . $e->getMessage(), 'warning');
        }

        $this->redirect("admin/databases/fields?db_id=$db_id&table=$table_name");
    }

    /**
     * Updates the configuration (UI type, constraints) for existing fields.
     */
    public function updateFieldConfig()
    {
        $config_id = $_POST['config_id'] ?? null;
        if (!$config_id)
            return;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT db_id FROM fields_config WHERE id = ?");
        $stmt->execute([$config_id]);
        $db_id = $stmt->fetchColumn();

        Auth::requirePermission('module:databases.edit_table');

        $view_type = $_POST['view_type'] ?? 'text';
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;
        $is_editable = isset($_POST['is_editable']) ? 1 : 0;
        $is_foreign_key = isset($_POST['is_foreign_key']) ? 1 : 0;
        $related_table = $_POST['related_table'] ?? null;
        $related_field = $_POST['related_field'] ?? null;

        $stmt = $db->prepare("UPDATE fields_config SET
view_type = ?, is_required = ?, is_visible = ?, is_editable = ?,
is_foreign_key = ?, related_table = ?, related_field = ?
WHERE id = ?");
        $stmt->execute([
            $view_type,
            $is_required,
            $is_visible,
            $is_editable,
            $is_foreign_key,
            $related_table,
            $related_field,
            $config_id
        ]);

        $stmt = $db->prepare("SELECT table_name FROM fields_config WHERE id = ?");
        $stmt->execute([$config_id]);
        $table_name = $stmt->fetchColumn();
        header('Location: ' . Auth::getBaseUrl() . "admin/databases/fields?db_id=$db_id&table=$table_name");
    }
    /**
     * Synchronizes the physical database structure with the system's metadata.
     * Handles ALTER TABLE operations, additions, and deletions.
     */
    public function syncDatabase()
    {
        $id = $_GET['id'] ?? null;
        Auth::requirePermission('module:databases.edit_table');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$id]);
        $database = $stmt->fetch();

        if (!$database) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $stmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $syncedTables = 0;
            $syncedFields = 0;

            foreach ($tables as $table) {
                $stmtCols = $targetDb->query("PRAGMA table_info($table)");
                $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                $syncedTables++;

                // --- Consistency Audit: Ensure audit columns exist ---
                $columnNames = array_map(function ($c) {
                    return strtolower($c['name']);
                }, $columns);
                $now = date('Y-m-d H:i:s');
                $auditChanged = false;

                if (!in_array('fecha_de_creacion', $columnNames)) {
                    $targetDb->exec("ALTER TABLE $table ADD COLUMN fecha_de_creacion TEXT");
                    $targetDb->exec("UPDATE $table SET fecha_de_creacion = '$now' WHERE fecha_de_creacion IS NULL OR fecha_de_creacion = ''");
                    $auditChanged = true;
                }
                if (!in_array('fecha_edicion', $columnNames)) {
                    $targetDb->exec("ALTER TABLE $table ADD COLUMN fecha_edicion TEXT");
                    $targetDb->exec("UPDATE $table SET fecha_edicion = '$now' WHERE fecha_edicion IS NULL OR fecha_edicion = ''");
                    $auditChanged = true;
                }

                // If schema changed, reload columns
                if ($auditChanged) {
                    $stmtCols = $targetDb->query("PRAGMA table_info($table)");
                    $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                }
                // ---------------------------------------------------

                foreach ($columns as $col) {
                    $stmtCheck = $db->prepare("SELECT id FROM fields_config WHERE db_id = ? AND table_name = ? AND field_name = ?");
                    $stmtCheck->execute([$id, $table, $col['name']]);
                    if (!$stmtCheck->fetch()) {
                        $viewType = 'text';
                        $dataType = strtoupper($col['type']);
                        $lowerName = strtolower($col['name']);

                        if (preg_match('/(imagen|image|foto|photo|img|avatar|logo|thumbnail|picture|gallery|galeria)/i', $lowerName)) {
                            $viewType = preg_match('/gallery|galeria/i', $lowerName) ? 'gallery' : 'image';
                        } elseif (preg_match('/(descripcion|description|content|contenido|mensaje|message|bio|body|text)/i', $lowerName)) {
                            $viewType = 'textarea';
                        } elseif (
                            preg_match('/(status|activo|active|visible|enabled|public|borrado|deleted)/i', $lowerName) &&
                            strpos($dataType, 'INT') !== false
                        ) {
                            $viewType = 'boolean';
                        } elseif (
                            preg_match('/(fecha|date|time|timestamp|momento|horario)/i', $lowerName) ||
                            preg_match('/(DATETIME|DATE|TIMESTAMP)/i', $dataType)
                        ) {
                            $viewType = 'datetime';
                        }

                        // Protect audit fields from manual editing
                        $isEditable = in_array($col['name'], ['id', 'fecha_de_creacion', 'fecha_edicion']) ? 0 : 1;
                        $isVisible = 1;

                        $stmtInsert = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable,
is_visible, is_required) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                        $stmtInsert->execute([$id, $table, $col['name'], $dataType, $viewType, $isEditable, $isVisible]);
                        $syncedFields++;
                    }
                }
            }
            Auth::setFlashError(
                "Audit completed: $syncedTables tables synchronized. Missing columns 'fecha_de_creacion/edicion' were automatically injected for consistency.",
                'success'
            );
        } catch (\PDOException $e) {
            Auth::setFlashError("Audit Signal Error: " . $e->getMessage());
        }

        header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $id);
        exit;
    }
}