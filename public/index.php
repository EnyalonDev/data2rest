<?php

use App\Core\Auth;
use App\Core\Router;
use App\Core\Installer;

require_once __DIR__ . '/../src/autoload.php';

Installer::check();
Auth::init();

$router = new Router();

// --- Auth ---
$router->add('GET', '/login', 'Auth\\LoginController@showLoginForm');
$router->add('POST', '/login', 'Auth\\LoginController@login');
$router->add('GET', '/logout', 'Auth\\LoginController@logout');

$router->add('GET', '/', function() {
    Auth::requireLogin();
    require_once __DIR__ . '/../src/Views/admin/dashboard.php';
});

// --- Module: Database Management ---
$router->add('GET', '/admin/databases', 'Database\\DatabaseController@index');
$router->add('POST', '/admin/databases/create', 'Database\\DatabaseController@create');
$router->add('GET', '/admin/databases/delete', 'Database\\DatabaseController@delete');
$router->add('GET', '/admin/databases/view', 'Database\\DatabaseController@viewTables');
$router->add('GET', '/admin/databases/sync', 'Database\\DatabaseController@syncDatabase');
$router->add('POST', '/admin/databases/table/create', 'Database\\DatabaseController@createTable');
$router->add('GET', '/admin/databases/table/delete', 'Database\\DatabaseController@deleteTable');
$router->add('GET', '/admin/databases/fields', 'Database\\DatabaseController@manageFields');
$router->add('POST', '/admin/databases/fields/add', 'Database\\DatabaseController@addField');
$router->add('POST', '/admin/databases/fields/update', 'Database\\DatabaseController@updateFieldConfig');

// --- Dynamic CRUD ---
$router->add('GET', '/admin/crud/list', 'Database\\CrudController@list');
$router->add('GET', '/admin/crud/new', 'Database\\CrudController@form');
$router->add('GET', '/admin/crud/edit', 'Database\\CrudController@form');
$router->add('POST', '/admin/crud/save', 'Database\\CrudController@save');
$router->add('GET', '/admin/crud/delete', 'Database\\CrudController@delete');
$router->add('GET', '/admin/media/list', 'Database\\CrudController@mediaList');

// --- Module: API REST Panel ---
$router->add('GET', '/admin/api', 'Api\\ApiDocsController@index');
$router->add('POST', '/admin/api/keys/create', 'Api\\ApiDocsController@createKey');
$router->add('GET', '/admin/api/keys/delete', 'Api\\ApiDocsController@deleteKey');
$router->add('GET', '/admin/api/docs', 'Api\\ApiDocsController@docs');

// --- Module: User & Role Management ---
$router->add('GET', '/admin/users', 'Auth\\UserController@index');
$router->add('GET', '/admin/users/new', 'Auth\\UserController@form');
$router->add('GET', '/admin/users/edit', 'Auth\\UserController@form');
$router->add('POST', '/admin/users/save', 'Auth\\UserController@save');
$router->add('GET', '/admin/users/delete', 'Auth\\UserController@delete');

$router->add('GET', '/admin/roles', 'Auth\\RoleController@index');
$router->add('GET', '/admin/roles/new', 'Auth\\RoleController@form');
$router->add('GET', '/admin/roles/edit', 'Auth\\RoleController@form');
$router->add('POST', '/admin/roles/save', 'Auth\\RoleController@save');
$router->add('GET', '/admin/roles/delete', 'Auth\\RoleController@delete');

// --- REST API Engine ---
$router->add('GET', '/api/v1/{db}/{table}', 'Api\\RestController@handle');
$router->add('GET', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');
$router->add('POST', '/api/v1/{db}/{table}', 'Api\\RestController@handle');
$router->add('PUT', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');
$router->add('PATCH', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');
$router->add('DELETE', '/api/v1/{db}/{table}/{id}', 'Api\\RestController@handle');

// Dispatch
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$router->dispatch($method, $uri);
