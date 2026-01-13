<?php

// DEBUG MODE: FORCE DISPLAY ERRORS
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
use App\Core\Auth;
use App\Core\Router;
use App\Core\Installer;
use App\Core\Config;

require_once __DIR__ . '/../src/autoload.php';

// Load ENV variables
Config::loadEnv();

Installer::check();
Auth::init();

// Apply Time Offset if exists
try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT value FROM system_settings WHERE key = 'time_offset_total'");
    $offset = $stmt->fetchColumn();
    if ($offset !== false) {
        $offset = (int) $offset;
        // Convert minutes to a timezone string like +HH:MM or -HH:MM
        $hours = floor(abs($offset) / 60);
        $minutes = abs($offset) % 60;
        $sign = $offset >= 0 ? '+' : '-';
        $tzString = sprintf('%s%02d:%02d', $sign, $hours, $minutes);

        // We can't easily use date_default_timezone_set with offsets directly in all PHP versions 
        // without a name, but we can set the default timezone to UTC and then adjust our needs 
        // or use a generic GMT offset name. Better: just set the environment or use it in a helper.
        // For now, let's set a global constant for the app to use.
        define('APP_TIME_OFFSET', $offset);
    } else {
        define('APP_TIME_OFFSET', 0);
    }
} catch (\Exception $e) {
    define('APP_TIME_OFFSET', 0);
}

$router = new Router();

// Handle CORS for all API requests
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/v1/') !== false) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-API-KEY, X-API-Key, Authorization");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// --- Auth ---
$router->add('GET', '/login', 'Auth\\LoginController@showLoginForm');
$router->add('POST', '/login', 'Auth\\LoginController@login');
$router->add('GET', '/logout', 'Auth\\LoginController@logout');

$router->add('GET', '/lang/{lang}', function ($lang) {
    \App\Core\Lang::set($lang);
    $referer = $_SERVER['HTTP_REFERER'] ?? (\App\Core\Auth::getBaseUrl() . 'admin/dashboard');
    header('Location: ' . $referer);
    exit;
});

// System routes
$router->add('GET', '/admin/system/info', 'System\\SystemController@info');
$router->add('POST', '/admin/system/dev-mode', 'System\\SystemController@toggleDevMode');
$router->add('POST', '/admin/system/clear-cache', 'System\\SystemController@clearCache');
$router->add('POST', '/admin/system/clear-sessions', 'System\\SystemController@clearSessions');
$router->add('POST', '/admin/system/time-offset', 'System\\SystemController@updateTimeOffset');
$router->add('POST', '/admin/system/dismiss-banner', 'System\\SystemController@dismissBanner');
$router->add('POST', '/admin/system/global-search', 'System\\SystemController@globalSearch');

$router->add('GET', '/', 'Auth\\DashboardController@index');
$router->add('GET', '/admin/dashboard', 'Auth\\DashboardController@index');

// --- Module: Database Management ---
$router->add('GET', '/admin/databases', 'Database\\DatabaseController@index');
$router->add('POST', '/admin/databases/create', 'Database\\DatabaseController@create');
$router->add('GET', '/admin/databases/delete', 'Database\\DatabaseController@delete');
$router->add('GET', '/admin/databases/edit', 'Database\\DatabaseController@edit');
$router->add('POST', '/admin/databases/config/save', 'Database\\DatabaseController@saveConfig');
$router->add('GET', '/admin/databases/view', 'Database\\DatabaseController@viewTables');
$router->add('GET', '/admin/databases/sync', 'Database\\DatabaseController@syncDatabase');
$router->add('POST', '/admin/databases/import', 'Database\\DatabaseController@importSql');
$router->add('GET', '/admin/databases/export', 'Database\\DatabaseController@exportSql');
$router->add('POST', '/admin/databases/table/create', 'Database\\DatabaseController@createTable');
$router->add('POST', '/admin/databases/table/create-sql', 'Database\\DatabaseController@createTableSql');
$router->add('GET', '/admin/databases/table/delete', 'Database\\DatabaseController@deleteTable');
$router->add('GET', '/admin/databases/fields', 'Database\\DatabaseController@manageFields');
$router->add('POST', '/admin/databases/fields/add', 'Database\\DatabaseController@addField');
$router->add('GET', '/admin/databases/fields/delete', 'Database\\DatabaseController@deleteField');
$router->add('POST', '/admin/databases/fields/update', 'Database\\DatabaseController@updateFieldConfig');
$router->add('GET', '/admin/demo/load', 'Database\\MaintenanceController@loadDemo');
$router->add('GET', '/admin/system/reset', 'Database\\MaintenanceController@resetSystem');

// Table-level Import/Export
$router->add('GET', '/admin/databases/table/export-sql', 'Database\\DatabaseController@exportTableSql');
$router->add('GET', '/admin/databases/table/export-excel', 'Database\\DatabaseController@exportTableExcel');
$router->add('GET', '/admin/databases/table/export-csv', 'Database\\DatabaseController@exportTableCsv');
$router->add('GET', '/admin/databases/table/template-excel', 'Database\\DatabaseController@generateExcelTemplate');
$router->add('GET', '/admin/databases/table/template-csv', 'Database\\DatabaseController@generateCsvTemplate');
$router->add('POST', '/admin/databases/table/import-sql', 'Database\\DatabaseController@importTableSql');
$router->add('POST', '/admin/databases/table/import-sql-text', 'Database\\DatabaseController@importTableSqlText');
$router->add('POST', '/admin/databases/table/import-excel', 'Database\\DatabaseController@importTableExcel');
$router->add('POST', '/admin/databases/table/import-csv', 'Database\\DatabaseController@importTableCsv');


// --- Dynamic CRUD ---
$router->add('GET', '/admin/crud/list', 'Database\\CrudController@list');
$router->add('GET', '/admin/crud/new', 'Database\\CrudController@form');
$router->add('GET', '/admin/crud/edit', 'Database\\CrudController@form');
$router->add('POST', '/admin/crud/save', 'Database\\CrudController@save');
$router->add('POST', '/admin/crud/delete', 'Database\\CrudController@delete');
$router->add('GET', '/admin/crud/delete', 'Database\\CrudController@delete');
$router->add('GET', '/admin/crud/export', 'Database\\CrudController@export');
$router->add('GET', '/admin/crud/history', 'Database\\CrudController@history');
$router->add('POST', '/admin/crud/restore', 'Database\\CrudController@restore'); // Add restore route too
// --- Module: Media Library ---
$router->add('GET', '/admin/media', 'Media\\MediaController@index');
$router->add('GET', '/admin/media/api/list', 'Media\\MediaController@list');
$router->add('GET', '/admin/media/api/usage', 'Media\\MediaController@usage');
$router->add('POST', '/admin/media/api/upload', 'Media\\MediaController@upload');
$router->add('POST', '/admin/media/api/delete', 'Media\\MediaController@delete');
$router->add('POST', '/admin/media/api/bulk-delete', 'Media\\MediaController@bulkDelete');
$router->add('POST', '/admin/media/api/bulk-move', 'Media\\MediaController@bulkMove');
$router->add('POST', '/admin/media/api/rename', 'Media\\MediaController@rename');
$router->add('POST', '/admin/media/api/edit', 'Media\\MediaController@edit');
$router->add('POST', '/admin/media/api/settings', 'Media\\MediaController@updateSettings');
$router->add('POST', '/admin/media/api/restore', 'Media\\MediaController@restore');
$router->add('POST', '/admin/media/api/purge', 'Media\\MediaController@purge');
$router->add('POST', '/admin/media/api/create-folder', 'Media\\MediaController@createFolder');

// Legacy compatibility for CRUD forms
$router->add('GET', '/admin/media/list', 'Media\\MediaController@mediaList');
$router->add('POST', '/admin/media/upload', 'Media\\MediaController@mediaUpload');

// --- Module: API REST Panel ---
$router->add('GET', '/admin/api', 'Api\\ApiDocsController@index');
$router->add('POST', '/admin/api/keys/create', 'Api\\ApiDocsController@createKey');
$router->add('GET', '/admin/api/keys/delete', 'Api\\ApiDocsController@deleteKey');
$router->add('GET', '/admin/api/docs', 'Api\\ApiDocsController@docs');

// --- Module: User & Role Management ---
$router->add('GET', '/admin/profile', 'Auth\\ProfileController@index');
$router->add('POST', '/admin/profile/save', 'Auth\\ProfileController@save');
$router->add('GET', '/admin/users', 'Auth\\UserController@index');
$router->add('GET', '/admin/users/new', 'Auth\\UserController@form');
$router->add('GET', '/admin/users/edit', 'Auth\\UserController@form');
$router->add('POST', '/admin/users/save', 'Auth\\UserController@save');
$router->add('GET', '/admin/users/delete', 'Auth\\UserController@delete');

$router->add('GET', '/admin/groups', 'Auth\\GroupController@index');
$router->add('GET', '/admin/groups/new', 'Auth\\GroupController@form');
$router->add('GET', '/admin/groups/edit', 'Auth\\GroupController@form');
$router->add('POST', '/admin/groups/save', 'Auth\\GroupController@save');
$router->add('GET', '/admin/groups/delete', 'Auth\\GroupController@delete');

$router->add('GET', '/admin/roles', 'Auth\\RoleController@index');
$router->add('GET', '/admin/roles/new', 'Auth\\RoleController@form');
$router->add('GET', '/admin/roles/edit', 'Auth\\RoleController@form');
$router->add('POST', '/admin/roles/save', 'Auth\\RoleController@save');
$router->add('GET', '/admin/roles/delete', 'Auth\\RoleController@delete');

// --- Module: Projects & Plans ---
$router->add('GET', '/admin/projects', 'Projects\\ProjectController@index');
$router->add('GET', '/admin/projects/new', 'Projects\\ProjectController@form');
$router->add('GET', '/admin/projects/edit', 'Projects\\ProjectController@form');
$router->add('POST', '/admin/projects/save', 'Projects\\ProjectController@save');
$router->add('GET', '/admin/projects/delete', 'Projects\\ProjectController@delete');
$router->add('GET', '/admin/projects/select', 'Projects\\ProjectController@select');
$router->add('GET', '/admin/projects/switch', 'Projects\\ProjectController@switch');
$router->add('POST', '/admin/projects/plan/update', 'Projects\\ProjectController@updatePlan');

// --- Module: Activity Logs ---
$router->add('GET', '/admin/logs', 'Logs\\LogController@index');

// --- Module: Backups ---
$router->add('GET', '/admin/backups', 'Backups\\BackupController@index');
$router->add('POST', '/admin/backups/create', 'Backups\\BackupController@create');
$router->add('POST', '/admin/backups/config', 'Backups\\BackupController@saveConfig');
$router->add('GET', '/admin/backups/download', 'Backups\\BackupController@download');
$router->add('GET', '/admin/backups/delete', 'Backups\\BackupController@delete');
$router->add('POST', '/admin/backups/upload', 'Backups\\BackupController@uploadToCloud');

// --- Module: Webhooks ---
$router->add('GET', '/admin/webhooks', 'Webhooks\\WebhookController@index');
$router->add('GET', '/admin/webhooks/new', 'Webhooks\\WebhookController@form');
$router->add('GET', '/admin/webhooks/edit', 'Webhooks\\WebhookController@form');
$router->add('POST', '/admin/webhooks/save', 'Webhooks\\WebhookController@save');
$router->add('GET', '/admin/webhooks/delete', 'Webhooks\\WebhookController@delete');
$router->add('GET', '/admin/webhooks/logs', 'Webhooks\\WebhookController@logs');
$router->add('POST', '/admin/webhooks/test', 'Webhooks\\WebhookController@test');

// --- REST API Engine ---
$router->add('GET', '/api/v1/{db}/{table}', 'Api\\RestController@handle');
$router->add('GET', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');
$router->add('POST', '/api/v1/{db}/{table}', 'Api\\RestController@handle');
$router->add('POST', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');
$router->add('PUT', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');
$router->add('PATCH', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');
$router->add('DELETE', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');
$router->add('OPTIONS', '/api/v1/{db}/{table}', 'Api\\RestController@handle');
$router->add('OPTIONS', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');

// Dispatch
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$router->dispatch($method, $uri);
