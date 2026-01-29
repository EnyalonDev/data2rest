<?php
ob_start();

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
// Load ENV variables
Config::loadEnv();

// Only show installer if config doesn't exist.
$configExists = file_exists(__DIR__ . '/../data/config.json');
$needsInstallation = !$configExists;

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Normalize URI
$normalizedUri = $uri;
if ($basePath !== '' && strpos($normalizedUri, $basePath) === 0) {
    $normalizedUri = substr($normalizedUri, strlen($basePath));
}
// Strip index.php if present
if (strpos($normalizedUri, '/index.php') === 0) {
    $normalizedUri = substr($normalizedUri, 10);
}
if ($normalizedUri === '')
    $normalizedUri = '/';

if ($needsInstallation) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    // Simple router for installation
    if ($normalizedUri === '/install' || $normalizedUri === '/install/' || strpos($normalizedUri, '/install/') === 0) {
        if ($normalizedUri === '/install/check') {
            (new \App\Modules\Install\InstallController())->checkConnection();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new \App\Modules\Install\InstallController())->install();
        } else {
            (new \App\Modules\Install\InstallController())->index();
        }
        exit;
    } else {
        // Redirect to /install
        $target = $basePath . '/install';
        header("Location: $target");
        exit;
    }
} elseif ($normalizedUri === '/install' || $normalizedUri === '/install/' || strpos($normalizedUri, '/install/') === 0) {
    // Already installed, but hitting /install - redirect to dashboard
    $target = $basePath . '/';
    header("Location: $target");
    exit;
}

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
if (
    isset($_SERVER['REQUEST_URI']) && (
        strpos($_SERVER['REQUEST_URI'], '/api/v1/') !== false ||
        strpos($_SERVER['REQUEST_URI'], '/api/system/') !== false ||
        strpos($_SERVER['REQUEST_URI'], '/api/projects/') !== false
    )
) {
    // Dynamic Origin Check based on Allowed Origins (Simple version for now: Allow All)
    // Production Security Note: You should restrict this to specific domains in the future
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: $origin");

    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-API-KEY, X-API-Key, Authorization, X-Project-ID");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// --- Auth ---
$router->add('GET', '/login', 'Auth\\LoginController@showLoginForm');
$router->add('POST', '/login', 'Auth\\LoginController@login');
$router->add('GET', '/logout', 'Auth\\LoginController@logout');

// Google Auth
$router->add('GET', '/auth/google', 'Auth\\GoogleAuthController@redirectToGoogle');
$router->add('GET', '/auth/google/callback', 'Auth\\GoogleAuthController@handleCallback');

// Welcome Pending (Waiting Room)
$router->add('GET', '/welcome-pending', 'Auth\\LoginController@welcomePending');

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
$router->add('GET', '/admin/system/migrate', 'System\\MigrationController@showMigrationForm');
$router->add('POST', '/admin/system/migrate/run', 'System\\MigrationController@migrate');



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

// Import JSON Feature
$router->add('GET', '/admin/databases/import-json', 'Database\\ImportController@index');
$router->add('POST', '/admin/databases/import-json-process', 'Database\\ImportController@process');

$router->add('GET', '/admin/databases/table/delete', 'Database\\DatabaseController@deleteTable');
$router->add('GET', '/admin/databases/fields', 'Database\\DatabaseController@manageFields');
$router->add('POST', '/admin/databases/fields/add', 'Database\\DatabaseController@addField');
$router->add('GET', '/admin/databases/fields/delete', 'Database\\DatabaseController@deleteField');
$router->add('POST', '/admin/databases/fields/update', 'Database\\DatabaseController@updateFieldConfig');

// Multi-Database Support Routes
$router->add('GET', '/admin/databases/create-form', 'Database\\DatabaseController@createForm');
$router->add('POST', '/admin/databases/create-multi', 'Database\\DatabaseController@createMulti');
$router->add('POST', '/admin/databases/test-connection', 'Database\\DatabaseController@testConnection');
$router->add('GET', '/admin/databases/connections', 'Database\\DatabaseController@connectionManager');

$router->add('GET', '/admin/demo/load', 'Database\\MaintenanceController@loadDemo');
$router->add('GET', '/admin/system/reset', 'Database\\MaintenanceController@resetSystem');

// --- Module: System Database Administration (Super Admin Only) ---
$router->add('GET', '/admin/system-database', 'SystemDatabase\\SystemDatabaseController@index');
$router->add('GET', '/admin/system-database/tables', 'SystemDatabase\\SystemDatabaseController@tables');
$router->add('GET', '/admin/system-database/table-details', 'SystemDatabase\\SystemDatabaseController@tableDetails');
$router->add('GET', '/admin/system-database/query-executor', 'SystemDatabase\\SystemDatabaseController@queryExecutor');
$router->add('POST', '/admin/system-database/execute-query', 'SystemDatabase\\SystemDatabaseController@executeQuery');
$router->add('POST', '/admin/system-database/optimize', 'SystemDatabase\\SystemDatabaseController@optimize');
$router->add('POST', '/admin/system-database/clean', 'SystemDatabase\\SystemDatabaseController@cleanOldData');

// System Database Backups
$router->add('GET', '/admin/system-database/backups', 'SystemDatabase\\SystemDatabaseController@backupsList');
$router->add('POST', '/admin/system-database/backup/create', 'SystemDatabase\\SystemDatabaseController@createBackup');
$router->add('POST', '/admin/system-database/backup/restore', 'SystemDatabase\\SystemDatabaseController@restoreBackup');
$router->add('GET', '/admin/system-database/backup/delete', 'SystemDatabase\\SystemDatabaseController@deleteBackup');
$router->add('GET', '/admin/system-database/backup/download', 'SystemDatabase\\SystemDatabaseController@downloadBackup');

// System Database Logs
$router->add('GET', '/admin/system-database/logs', 'SystemDatabase\\SystemDatabaseController@viewLogs');
$router->add('GET', '/admin/system-database/logs/export', 'SystemDatabase\\SystemDatabaseController@exportLogs');
$router->add('POST', '/admin/system-database/logs/clear', 'SystemDatabase\\SystemDatabaseController@clearLogs');

// System Database API Explorer
$router->add('GET', '/admin/system-database/api-explorer', 'SystemDatabase\\SystemDatabaseController@apiExplorer');

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
$router->add('GET', '/admin/trash', 'Database\\CrudController@trash');
$router->add('POST', '/admin/trash/empty', 'Database\\CrudController@emptyTrash');
$router->add('POST', '/admin/crud/restore', 'Database\\CrudController@restore');
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
$router->add('GET', '/admin/api/docs', 'Api\\ApiDocsController@docs'); // Legacy Docs

// API Analytics (Phase 3)
$router->add('GET', '/admin/api/analytics', 'Api\\ApiAnalyticsController@index');

// API Permissions & Limits (Phase 1)
$router->add('GET', '/admin/api/permissions', 'Api\\ApiPermissionsController@manage');
$router->add('POST', '/admin/api/permissions/save', 'Api\\ApiPermissionsController@save');
$router->add('POST', '/admin/api/permissions/delete', 'Api\\ApiPermissionsController@delete');
$router->add('POST', '/admin/api/permissions/rate-limit', 'Api\\ApiPermissionsController@updateRateLimit');

// API Swagger UI (Phase 2)
$router->add('GET', '/admin/api/swagger', 'Api\\SwaggerController@index');
$router->add('GET', '/admin/api/swagger/spec', 'Api\\SwaggerController@spec');

// --- Module: User & Role Management ---
$router->add('GET', '/admin/profile', 'Auth\\ProfileController@index');
$router->add('POST', '/admin/profile/save', 'Auth\\ProfileController@save');
$router->add('GET', '/admin/users', 'Auth\\UserController@index');
$router->add('GET', '/admin/users/clients', 'Auth\\UserController@clients');
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

// --- Module: Task Management (Kanban Board) ---
$router->add('GET', '/admin/tasks', 'Tasks\\TaskController@index');
$router->add('POST', '/admin/tasks/create', 'Tasks\\TaskController@create');
$router->add('POST', '/admin/tasks/move', 'Tasks\\TaskController@move');
$router->add('POST', '/admin/tasks/update', 'Tasks\\TaskController@update');
$router->add('POST', '/admin/tasks/delete', 'Tasks\\TaskController@delete');
$router->add('POST', '/admin/tasks/take', 'Tasks\\TaskController@take');
$router->add('POST', '/admin/tasks/assign', 'Tasks\\TaskController@assign');
$router->add('POST', '/admin/tasks/postComment', 'Tasks\\TaskController@postComment');
$router->add('GET', '/admin/tasks/history', 'Tasks\\TaskController@history');
$router->add('GET', '/admin/tasks/getTaskDetails', 'Tasks\\TaskController@getTaskDetails');

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

// --- Auth: External Sites (Google OAuth) ---
$router->add('GET', '/api/projects/{projectId}/auth/google', 'Auth\\ProjectAuthController@initiateGoogleAuth');
$router->add('OPTIONS', '/api/projects/{projectId}/auth/google/callback', function () {
    http_response_code(200);
    exit;
});
$router->add('GET', '/api/projects/{projectId}/auth/google/callback', 'Auth\\ProjectAuthController@verifyGoogleCode');
$router->add('POST', '/api/projects/{projectId}/auth/google/callback', 'Auth\\ProjectAuthController@verifyGoogleCode');
$router->add('POST', '/api/projects/{projectId}/auth/register', 'Auth\\ProjectAuthController@register');
$router->add('OPTIONS', '/api/projects/{projectId}/auth/register', function () {
    http_response_code(200);
    exit;
});
$router->add('POST', '/api/projects/{projectId}/auth/login', 'Auth\\ProjectAuthController@login');
$router->add('OPTIONS', '/api/projects/{projectId}/auth/login', function () {
    http_response_code(200);
    exit;
});
$router->add('POST', '/api/v1/auth/google/verify', 'Auth\\ProjectAuthController@verifyGoogleCode');
$router->add('POST', '/api/v1/auth/verify-token', 'Auth\\ProjectAuthController@verifyToken');
$router->add('POST', '/api/v1/auth/logout', 'Auth\\ProjectAuthController@logout');
$router->add('POST', '/api/v1/external/{projectId}/log-activity', 'Auth\\ProjectAuthController@logExternalActivity');
$router->add('POST', '/api/v1/external/{projectId}/client-debug', 'Auth\\ProjectAuthController@logExternalClientDebug');

// --- Storage API ---
$router->add('POST', '/api/v1/storage/upload', 'Api\\StorageApiController@upload');

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

// --- Billing Module API ---
// NOTE: Client management has been moved to the Users module (users with role_id = 4)

// Projects with Billing
$router->add('POST', '/api/billing/projects', 'Billing\\Controllers\\ProjectController@create');
$router->add('PATCH', '/api/billing/projects/{id}/change-plan', 'Billing\\Controllers\\ProjectController@changePlan');
$router->add('PATCH', '/api/billing/projects/{id}/start-date', 'Billing\\Controllers\\ProjectController@changeStartDate');
$router->add('GET', '/api/billing/projects/{id}/plan-history', 'Billing\\Controllers\\ProjectController@getPlanHistory');

// Payment Plans
$router->add('GET', '/api/billing/payment-plans', 'Billing\\Controllers\\PaymentPlanController@index');
$router->add('POST', '/api/billing/payment-plans', 'Billing\\Controllers\\PaymentPlanController@create');
$router->add('GET', '/api/billing/payment-plans/{id}', 'Billing\\Controllers\\PaymentPlanController@getById');
$router->add('PUT', '/api/billing/payment-plans/{id}', 'Billing\\Controllers\\PaymentPlanController@update');



// Installments
$router->add('GET', '/api/billing/projects/{id}/installments', 'Billing\\Controllers\\InstallmentController@getByProject');
$router->add('GET', '/api/billing/installments/upcoming', 'Billing\\Controllers\\InstallmentController@getUpcoming');
$router->add('GET', '/api/billing/installments/overdue', 'Billing\\Controllers\\InstallmentController@getOverdue');
$router->add('GET', '/api/billing/installments/{id}', 'Billing\\Controllers\\InstallmentController@getById');
$router->add('POST', '/api/billing/installments/{id}/pay', 'Billing\\Controllers\\InstallmentController@pay');
$router->add('POST', '/api/billing/installments/{id}/report', 'Billing\\Controllers\\InstallmentController@report');
$router->add('POST', '/api/billing/payments/{id}/approve', 'Billing\\Controllers\\InstallmentController@approve');
$router->add('POST', '/api/billing/payments/{id}/reject', 'Billing\\Controllers\\InstallmentController@reject');
$router->add('GET', '/api/billing/payments/{id}', 'Billing\\Controllers\\InstallmentController@getPaymentById');

// Services Catalog
$router->add('GET', '/api/billing/services', 'Billing\\Controllers\\ServiceApiController@index');
$router->add('POST', '/api/billing/services', 'Billing\\Controllers\\ServiceApiController@create');
$router->add('PUT', '/api/billing/services/{id}', 'Billing\\Controllers\\ServiceApiController@update');
$router->add('DELETE', '/api/billing/services/{id}', 'Billing\\Controllers\\ServiceApiController@delete');
// Service Templates
$router->add('GET', '/api/billing/services/{id}/templates', 'Billing\\Controllers\\ServiceApiController@getTemplates');
$router->add('POST', '/api/billing/services/{id}/templates', 'Billing\\Controllers\\ServiceApiController@addTemplate');
$router->add('PUT', '/api/billing/services/templates/{id}', 'Billing\\Controllers\\ServiceApiController@updateTemplate');
$router->add('DELETE', '/api/billing/services/templates/{id}', 'Billing\\Controllers\\ServiceApiController@deleteTemplate');
$router->add('GET', '/api/billing/services/{id}/templates/export-data', 'Billing\\Controllers\\ServiceApiController@exportTemplatesData');
$router->add('POST', '/api/billing/services/{id}/templates/import', 'Billing\\Controllers\\ServiceApiController@importTemplates');

// Project Services
$router->add('GET', '/api/billing/projects/{id}/services', 'Billing\\Controllers\\ProjectController@getServices');
$router->add('POST', '/api/billing/projects/{id}/services', 'Billing\\Controllers\\ProjectController@addService');
$router->add('DELETE', '/api/billing/projects/{id}/services/{service_id}', 'Billing\\Controllers\\ProjectController@removeService');

// Reports
$router->add('GET', '/api/billing/reports/financial-summary', 'Billing\\Controllers\\ReportController@financialSummary');
$router->add('GET', '/api/billing/reports/income-comparison', 'Billing\\Controllers\\ReportController@incomeComparison');
$router->add('GET', '/api/billing/reports/upcoming-installments', 'Billing\\Controllers\\ReportController@upcomingInstallments');
$router->add('GET', '/api/billing/reports/client-summary/{id}', 'Billing\\Controllers\\ReportController@clientSummary');

// --- Billing Module Web Views ---
$router->add('GET', '/admin/billing', 'Billing\\Controllers\\BillingWebController@index');
// NOTE: Client management removed - use Users module instead
$router->add('GET', '/admin/billing/projects', 'Billing\\Controllers\\BillingWebController@projects');
$router->add('GET', '/admin/billing/installments', 'Billing\\Controllers\\BillingWebController@installments');
$router->add('GET', '/admin/billing/plans', 'Billing\\Controllers\\BillingWebController@plans');
$router->add('GET', '/admin/billing/reports', 'Billing\\Controllers\\BillingWebController@reports');
$router->add('GET', '/admin/billing/payments', 'Billing\\Controllers\\BillingWebController@payments');
$router->add('GET', '/admin/billing/services', 'Billing\\Controllers\\BillingWebController@services');



// --- System Database API (Super Admin Only) ---
$router->add('GET', '/api/system/info', 'SystemDatabase\\SystemDatabaseApiController@getInfo');
$router->add('POST', '/api/system/backup', 'SystemDatabase\\SystemDatabaseApiController@createBackup');
$router->add('GET', '/api/system/backups', 'SystemDatabase\\SystemDatabaseApiController@listBackups');
$router->add('POST', '/api/system/optimize', 'SystemDatabase\\SystemDatabaseApiController@optimize');
$router->add('POST', '/api/system/query', 'SystemDatabase\\SystemDatabaseApiController@executeQuery');
// --- System Database API (Super Admin Only) ---
$router->add('GET', '/api/system/tables', 'SystemDatabase\\SystemDatabaseApiController@listTables');

// Storage API (Moved up)

// --- System Settings ---
$router->add('GET', '/admin/settings/google', 'System\\SystemController@googleSettings');
$router->add('POST', '/admin/settings/google', 'System\\SystemController@updateGoogleSettings');


// --- Admin: Project Logs ---
$router->add('GET', '/admin/projects/{id}/logs', 'Projects\\ProjectLogsController@index');
$router->add('GET', '/admin/projects/{id}/logs/export-csv', 'Projects\\ProjectLogsController@exportCsv');
$router->add('GET', '/admin/projects/{id}/external-users', 'Projects\\ProjectUsersController@listExternalUsers');
$router->add('POST', '/admin/projects/external-users/update', 'Projects\\ProjectUsersController@updateExternalPermissions');
$router->add('GET', '/admin/projects/external-users/search', 'Projects\\ProjectUsersController@searchUsers');
$router->add('POST', '/admin/projects/external-users/add', 'Projects\\ProjectUsersController@addUserToProject');

// Dispatch
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$router->dispatch($method, $uri);
