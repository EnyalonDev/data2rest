<?php

namespace App\Core;

use PDO;
use Exception;

/**
 * Authentication and Authorization Manager
 * Handles user sessions, login/logout logic, and permission verification.
 */
class Auth
{
    /**
     * Initializes the session if it hasn't been started yet.
     */
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Gets the current server time adjusted by the user-defined offset.
     */
    public static function getCurrentTime($format = 'Y-m-d H:i:s')
    {
        $offset = defined('APP_TIME_OFFSET') ? APP_TIME_OFFSET : 0;
        $now = new \DateTime();
        if ($offset !== 0) {
            $now->modify(($offset >= 0 ? '+' : '') . $offset . ' minutes');
        }
        return $now->format($format);
    }

    /**
     * Attempts to log in a user.
     * 
     * @param string $username
     * @param string $password
     * @return bool True if login successful, false otherwise
     */
    public static function login($username, $password)
    {
        $db = Database::getInstance()->getConnection();
        // Fetch user with their role and group permissions
        $stmt = $db->prepare("SELECT u.*, r.permissions as role_perms, g.permissions as group_perms FROM users u 
                             LEFT JOIN roles r ON u.role_id = r.id 
                             LEFT JOIN " . Database::getInstance()->getAdapter()->quoteName('groups') . " g ON u.group_id = g.id
                             WHERE u.username = ? AND u.status = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['group_id'] = $user['group_id'] ?? null;

            // Decode and merge permissions from role and group
            $rolePerms = json_decode($user['role_perms'] ?? '[]', true);
            $groupPerms = json_decode($user['group_perms'] ?? '[]', true);
            $_SESSION['permissions'] = self::mergePermissions($rolePerms, $groupPerms);

            // Fetch Projects assigned to the user
            self::loadUserProjects();

            return true;
        }
        return false;
    }

    /**
     * Loads projects assigned to the current user into the session.
     */
    public static function loadUserProjects()
    {
        $db = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];

        if (self::isAdmin()) {
            // Admins see all projects
            $stmt = $db->query("SELECT id, name FROM projects WHERE status = 'active'");
            $_SESSION['user_projects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $db->prepare("SELECT p.id, p.name FROM projects p 
                                 JOIN project_users pu ON p.id = pu.project_id 
                                 WHERE pu.user_id = ? AND p.status = 'active'");
            $stmt->execute([$userId]);
            $_SESSION['user_projects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Set default project if not set
        if (!isset($_SESSION['current_project_id']) && !empty($_SESSION['user_projects'])) {
            $_SESSION['current_project_id'] = $_SESSION['user_projects'][0]['id'];
        }
    }

    /**
     * Switches the active project context.
     */
    public static function setActiveProject($projectId)
    {
        $projects = $_SESSION['user_projects'] ?? [];
        $ids = array_column($projects, 'id');

        if (in_array($projectId, $ids) || self::isAdmin()) {
            $_SESSION['current_project_id'] = $projectId;
            return true;
        }
        return false;
    }

    /**
     * Gets the current active project ID.
     */
    public static function getActiveProject()
    {
        return $_SESSION['current_project_id'] ?? null;
    }

    /**
     * Logs out the current user and clears the session.
     */
    public static function logout()
    {
        session_destroy();
        $baseUrl = self::getBaseUrl();
        header("Location: {$baseUrl}login");
        exit;
    }

    /**
     * Checks if a user is currently logged in.
     * 
     * @return bool
     */
    public static function check()
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Gets the current user ID from session.
     * 
     * @return int|null
     */
    public static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Verifies if the current user has a specific permission.
     * 
     * @param string $resource The resource identifier (e.g., 'module:users' or 'db:1')
     * @param string|null $action The action to check (e.g., 'read', 'write')
     * @return bool
     */
    public static function hasPermission($resource, $action = null)
    {
        if (!isset($_SESSION['permissions']))
            return false;
        $perms = $_SESSION['permissions'];

        // Super-admin bypass
        if (isset($perms['all']) && $perms['all'] === true)
            return true;

        // Check specific resource permission (e.g. module:databases.create_db)
        // Format: module:name.action - Process this FIRST before general module check
        if (strpos($resource, '.') !== false) {
            [$module, $reqAction] = explode('.', $resource, 2);
            if (strpos($module, 'module:') === 0)
                $module = substr($module, 7);

            if (!isset($perms['modules'][$module]))
                return false;

            // Strict check: the action must be explicitly in the array
            return in_array($reqAction, $perms['modules'][$module], true);
        }

        // Check module-level permissions (e.g. module:databases)
        // Only if it doesn't contain a dot (which implies specific action notation)
        if (strpos($resource, 'module:') === 0) {
            $module = substr($resource, 7);
            if (!isset($perms['modules'][$module]))
                return false;

            // If action is null, just checking access to the module generally
            if ($action === null)
                return true;

            // If action is provided as second parameter, check it
            return in_array($action, $perms['modules'][$module], true);
        }

        return false;
    }

    /**
     * Get users that belong to the same group as the current user.
     * 
     * @return array List of user IDs
     */
    public static function getTeamMembers()
    {
        if (!self::check() || !isset($_SESSION['group_id']))
            return [];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE group_id = ?");
        $stmt->execute([$_SESSION['group_id']]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Checks if a user has access to a specific module.
     * Helper wrapper for hasPermission.
     */
    public static function canAccessModule($module)
    {
        return self::hasPermission("module:$module");
    }

    /**
     * Checks if the current user has administrative (all) permissions.
     * 
     * @return bool
     */
    public static function isAdmin()
    {
        if (!isset($_SESSION['permissions']))
            return false;
        return isset($_SESSION['permissions']['all']) && $_SESSION['permissions']['all'] === true;
    }

    /**
     * Checks if development mode is active in system settings.
     * 
     * @return bool
     */
    public static function isDevMode()
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT value FROM system_settings WHERE key = 'dev_mode'");
            $val = $stmt->fetchColumn();
            return $val === 'on';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Redirects to the login page if the user is not authenticated.
     */
    public static function requireLogin()
    {
        if (!self::check()) {
            $baseUrl = self::getBaseUrl();
            header("Location: {$baseUrl}login");
            exit;
        }
    }

    /**
     * Restricts access to administrators only.
     */
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

    /**
     * Sets a temporary notification message in the session.
     * 
     * @param string $msg
     * @param string $type The message type (error, success, modal, etc.)
     */
    public static function setFlashError($msg, $type = 'error')
    {
        $_SESSION['flash_msg'] = ['text' => $msg, 'type' => $type];
    }

    /**
     * Retrieves and clears the flash message from the session.
     * 
     * @return array|null
     */
    public static function getFlashMsg()
    {
        $msg = $_SESSION['flash_msg'] ?? null;
        unset($_SESSION['flash_msg']);
        return $msg;
    }

    /**
     * Enforces a specific permission requirement.
     * 
     * @param string $resource
     * @param string|null $action
     */
    public static function requirePermission($resource, $action = null)
    {
        self::requireLogin();
        if (!self::hasPermission($resource, $action)) {
            self::setFlashError("System Alert: Node access denied for requested operation.", 'modal');
            self::redirectBack();
        }
    }

    /**
     * Enforces access control for a specific database.
     * 
     * @param string $db_id
     */
    public static function requireDatabaseAccess($db_id)
    {
        if (!self::hasPermission("db:$db_id")) {
            self::setFlashError("Access Denied: Your node is not authorized to interface with this database.", 'modal');
            self::redirectBack();
        }
    }

    /**
     * Redirects the user back to the previous page or the base URL.
     */
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

    /**
     * Automatically detects the base URL relative path.
     * 
     * @return string
     */
    public static function getBaseUrl()
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseDir = dirname($scriptName);
        return rtrim($baseDir, '/') . '/';
    }

    /**
     * Automatically detects the full absolute base URL including protocol and host.
     * 
     * @return string
     */
    public static function getFullBaseUrl()
    {
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $port = $_SERVER['SERVER_PORT'] ?? 80;
        $protocol = ($https || $port == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = self::getBaseUrl();
        return $protocol . $host . $baseUrl;
    }

    /**
     * Merges two permission sets, prioritizing 'all' access and combining module/db actions.
     * 
     * @param array $p1 First set of permissions
     * @param array $p2 Second set of permissions
     * @return array Merged permission set
     */
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

