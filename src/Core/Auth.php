<?php

namespace App\Core;

class Auth
{
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login($username, $password)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT u.*, r.permissions as role_perms, g.permissions as group_perms FROM users u 
                             LEFT JOIN roles r ON u.role_id = r.id 
                             LEFT JOIN groups g ON u.group_id = g.id
                             WHERE u.username = ? AND u.status = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['group_id'] = $user['group_id'] ?? null;

            $rolePerms = json_decode($user['role_perms'] ?? '[]', true);
            $groupPerms = json_decode($user['group_perms'] ?? '[]', true);
            $_SESSION['permissions'] = self::mergePermissions($rolePerms, $groupPerms);

            return true;
        }
        return false;
    }

    public static function logout()
    {
        session_destroy();
        $baseUrl = self::getBaseUrl();
        header("Location: {$baseUrl}login");
        exit;
    }

    public static function check()
    {
        return isset($_SESSION['user_id']);
    }

    public static function hasPermission($resource, $action = null)
    {
        if (!isset($_SESSION['permissions']))
            return false;
        $perms = $_SESSION['permissions'];

        if (isset($perms['all']) && $perms['all'] === true)
            return true;

        if (strpos($resource, 'module:') === 0) {
            $module = substr($resource, 7);
            if (!isset($perms['modules'][$module]))
                return false;
            if ($action === null)
                return true;
            return in_array($action, $perms['modules'][$module]);
        }

        if (strpos($resource, 'db:') === 0) {
            $db_id = substr($resource, 3);
            if (!isset($perms['databases'][$db_id]))
                return false;
            if ($action === null)
                return true;

            $dbPerms = $perms['databases'][$db_id];
            // If checking 'view', it's always allowed if the DB is in the list
            if ($action === 'view')
                return true;
            return in_array($action, $dbPerms);
        }

        return false;
    }

    public static function isAdmin()
    {
        if (!isset($_SESSION['permissions']))
            return false;
        return isset($_SESSION['permissions']['all']) && $_SESSION['permissions']['all'] === true;
    }

    public static function requireLogin()
    {
        if (!self::check()) {
            $baseUrl = self::getBaseUrl();
            header("Location: {$baseUrl}login");
            exit;
        }
    }

    public static function requireAdmin()
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            self::setFlashError("Access Denied: You do not have sufficient clearance for this operation.", 'error');
            $baseUrl = self::getBaseUrl();
            header("Location: {$baseUrl}admin");
            exit;
        }
    }

    public static function setFlashError($msg, $type = 'error')
    {
        $_SESSION['flash_msg'] = ['text' => $msg, 'type' => $type];
    }

    public static function getFlashMsg()
    {
        $msg = $_SESSION['flash_msg'] ?? null;
        unset($_SESSION['flash_msg']);
        return $msg;
    }

    public static function requirePermission($resource, $action = null)
    {
        self::requireLogin();
        if (!self::hasPermission($resource, $action)) {
            self::setFlashError("System Alert: Node access denied for requested operation.", 'modal');
            self::redirectBack();
        }
    }

    public static function requireDatabaseAccess($db_id)
    {
        if (!self::hasPermission("db:$db_id")) {
            self::setFlashError("Access Denied: Your node is not authorized to interface with this database.", 'modal');
            self::redirectBack();
        }
    }

    public static function redirectBack()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? self::getBaseUrl();
        // Prevent redirect loops
        if (strpos($referer, $_SERVER['REQUEST_URI']) !== false) {
            $referer = self::getBaseUrl();
        }
        header("Location: $referer");
        exit;
    }

    public static function getBaseUrl()
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseDir = dirname($scriptName);
        return rtrim($baseDir, '/') . '/';
    }

    public static function getFullBaseUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = self::getBaseUrl();
        return $protocol . $host . $baseUrl;
    }
    private static function mergePermissions($p1, $p2)
    {
        $merged = [
            'all' => ($p1['all'] ?? false) || ($p2['all'] ?? false),
            'modules' => [],
            'databases' => []
        ];

        if ($merged['all'])
            return $merged;

        // Merge Modules
        $allModules = array_unique(array_merge(array_keys($p1['modules'] ?? []), array_keys($p2['modules'] ?? [])));
        foreach ($allModules as $mod) {
            $actions1 = $p1['modules'][$mod] ?? [];
            $actions2 = $p2['modules'][$mod] ?? [];
            $merged['modules'][$mod] = array_unique(array_merge($actions1, $actions2));
        }

        // Merge Databases
        $allDbs = array_unique(array_merge(array_keys($p1['databases'] ?? []), array_keys($p2['databases'] ?? [])));
        foreach ($allDbs as $dbId) {
            $actions1 = $p1['databases'][$dbId] ?? [];
            $actions2 = $p2['databases'][$dbId] ?? [];
            $merged['databases'][$dbId] = array_unique(array_merge($actions1, $actions2));
        }

        return $merged;
    }
}
