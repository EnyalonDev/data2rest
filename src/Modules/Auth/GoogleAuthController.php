<?php

namespace App\Modules\Auth;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Auth;
use Google\Client as GoogleClient;
use Google\Service\Oauth2 as GoogleServiceOauth2;

class GoogleAuthController extends BaseController
{
    private function getClient()
    {
        // Fetch settings from DB
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM system_settings WHERE key_name IN ('google_client_id', 'google_client_secret', 'google_redirect_uri', 'google_login_enabled')");
        $settings = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $settings[$row['key_name']] = $row['value'];
        }

        if (empty($settings['google_login_enabled']) || empty($settings['google_client_id']) || empty($settings['google_client_secret'])) {
            return null;
        }

        $client = new GoogleClient();
        $client->setClientId($settings['google_client_id']);
        $client->setClientSecret($settings['google_client_secret']);
        $client->setRedirectUri($settings['google_redirect_uri'] ?? 'https://nestorovallos.dev/auth/google/callback');
        $client->addScope("email");
        $client->addScope("profile");

        return $client;
    }

    public function redirectToGoogle()
    {
        $client = $this->getClient();
        if (!$client) {
            error_log("Google OAuth Error: Client not configured");

            // Check what's missing
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT key_name, value FROM system_settings WHERE key_name IN ('google_client_id', 'google_client_secret', 'google_redirect_uri', 'google_login_enabled')");
            $settings = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $settings[$row['key_name']] = $row['value'];
            }

            $missing = [];
            if (empty($settings['google_login_enabled']))
                $missing[] = 'Google Login no está habilitado';
            if (empty($settings['google_client_id']))
                $missing[] = 'Client ID no configurado';
            if (empty($settings['google_client_secret']))
                $missing[] = 'Client Secret no configurado';
            if (empty($settings['google_redirect_uri']))
                $missing[] = 'Redirect URI no configurado';

            Auth::setFlashError('Error de configuración de Google OAuth: ' . implode(', ', $missing), 'error');
            $this->redirect('login');
            return;
        }

        try {
            $authUrl = $client->createAuthUrl();
            error_log("Google OAuth: Redirecting to " . $authUrl);
            header('Location: ' . $authUrl);
            exit;
        } catch (\Exception $e) {
            error_log("Google OAuth Error creating auth URL: " . $e->getMessage());
            Auth::setFlashError('Error al crear URL de autenticación: ' . $e->getMessage(), 'error');
            $this->redirect('login');
        }
    }

    public function handleCallback()
    {
        error_log("Google OAuth Callback: Started");

        $client = $this->getClient();
        if (!$client) {
            error_log("Google OAuth Callback Error: Client not configured");
            Auth::setFlashError('Error: Google OAuth no está configurado correctamente', 'error');
            $this->redirect('login');
            return;
        }

        if (isset($_GET['code'])) {
            error_log("Google OAuth Callback: Received code");

            try {
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

                if (isset($token['error'])) {
                    $errorMsg = $token['error_description'] ?? $token['error'] ?? 'Error desconocido';
                    error_log("Google OAuth Token Error: " . $errorMsg);
                    Auth::setFlashError('Error al obtener token de Google: ' . $errorMsg, 'error');
                    $this->redirect('login');
                    return;
                }

                error_log("Google OAuth: Token obtained successfully");
                $client->setAccessToken($token['access_token']);

                // Get User Info
                $google_oauth = new GoogleServiceOauth2($client);
                $google_account_info = $google_oauth->userinfo->get();

                $email = $google_account_info->email;
                $googleId = $google_account_info->id;
                $name = $google_account_info->name;
                $avatar = $google_account_info->picture;

                error_log("Google OAuth: User info retrieved - Email: $email, Google ID: $googleId");

                // Check if user exists
                $db = Database::getInstance()->getConnection();

                // 1. Check by Google ID
                $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ?");
                $stmt->execute([$googleId]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($user) {
                    error_log("Google OAuth: Existing user found by Google ID: " . $user['username']);
                    $this->loginUser($user);
                } else {
                    // 2. Check by Email (Link account if exists)
                    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($user) {
                        error_log("Google OAuth: Linking existing user by email: " . $user['username']);
                        // Link account
                        $update = $db->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                        $update->execute([$googleId, $user['id']]);
                        $this->loginUser($user);
                    } else {
                        error_log("Google OAuth: Creating new user for email: $email");

                        // 3. Create new user (Standard/Guest)
                        $username = explode('@', $email)[0];
                        // Ensure unique username
                        $check = $db->prepare("SELECT count(*) FROM users WHERE username = ?");
                        $check->execute([$username]);
                        if ($check->fetchColumn() > 0) {
                            $username .= rand(100, 999);
                        }

                        // Get "Usuario" role ID dynamically
                        $roleStmt = $db->prepare("SELECT id FROM roles WHERE name = 'Usuario' LIMIT 1");
                        $roleStmt->execute();
                        $roleId = $roleStmt->fetchColumn();

                        if (!$roleId) {
                            error_log("Google OAuth Warning: 'Usuario' role not found, using default role_id = 4");
                            $roleId = 4; // Fallback
                        }

                        $insert = $db->prepare("INSERT INTO users (username, email, password, role_id, google_id, created_at, status) VALUES (?, ?, NULL, ?, ?, ?, 1)");
                        $now = date('Y-m-d H:i:s');
                        $insert->execute([$username, $email, $roleId, $googleId, $now]);
                        $newId = $db->lastInsertId();

                        error_log("Google OAuth: New user created with ID: $newId, username: $username");

                        // Fetch new user
                        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$newId]);
                        $newUser = $stmt->fetch(\PDO::FETCH_ASSOC);

                        $this->loginUser($newUser);
                    }
                }

            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                error_log("Google OAuth Exception: " . $errorMsg);
                error_log("Stack trace: " . $e->getTraceAsString());

                // Handle Expired/Invalid Code (Bad Request)
                if (strpos($errorMsg, 'Bad Request') !== false || strpos($errorMsg, 'invalid_grant') !== false) {
                    Auth::setFlashError('El código de autorización ha expirado. Por favor, intenta nuevamente.', 'warning');
                    $this->redirect('login');
                    return;
                }

                // Self-Healing: Check for missing 'google_id' column
                if (strpos($errorMsg, 'no such column: google_id') !== false || strpos($errorMsg, "Unknown column 'google_id'") !== false) {
                    error_log("Google OAuth: Attempting to add missing google_id column");
                    try {
                        $db = Database::getInstance()->getConnection();
                        $adapter = Database::getInstance()->getAdapter();
                        $type = $adapter->getType();

                        if ($type === 'sqlite') {
                            $db->exec("ALTER TABLE users ADD COLUMN google_id TEXT");
                        } elseif ($type === 'mysql') {
                            $db->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255)");
                        } elseif ($type === 'pgsql' || $type === 'postgresql') {
                            $db->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255)");
                        }

                        error_log("Google OAuth: google_id column added successfully, retrying");
                        Auth::setFlashError('Configuración actualizada. Por favor, intenta iniciar sesión nuevamente.', 'success');
                        $this->redirect('auth/google');
                        return;
                    } catch (\Exception $migErr) {
                        error_log("Google OAuth Migration Failed: " . $migErr->getMessage());
                        Auth::setFlashError('Error al actualizar la base de datos: ' . $migErr->getMessage(), 'error');
                    }
                }

                // Generic error
                Auth::setFlashError('Error al autenticar con Google: ' . $errorMsg, 'error');
                $this->redirect('login');
            }
        } else {
            // Check for error from Google
            if (isset($_GET['error'])) {
                $error = $_GET['error'];
                $errorDesc = $_GET['error_description'] ?? 'Sin descripción';
                error_log("Google OAuth Error from Google: $error - $errorDesc");
                Auth::setFlashError("Error de Google: $error - $errorDesc", 'error');
            } else {
                error_log("Google OAuth Error: No code received");
                Auth::setFlashError('Google no devolvió un código de autorización', 'error');
            }
            $this->redirect('login');
        }
    }

    private function loginUser($user)
    {
        // Use Auth core to login manually
        // We need to bypass password check. Auth::login uses password.
        // We'll set session manually, matching functionality of Auth::login logic.

        // Fetch user permissions like Auth::login does
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT r.name as role_name, r.permissions as role_perms, g.permissions as group_perms 
                             FROM roles r 
                             LEFT JOIN " . Database::getInstance()->getAdapter()->quoteName('groups') . " g ON g.id = ? 
                             WHERE r.id = ?");
        $stmt->execute([$user['group_id'] ?? 0, $user['role_id']]);
        $meta = $stmt->fetch(\PDO::FETCH_ASSOC);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['group_id'] = $user['group_id'] ?? null;

        // Define human readable role for logic checks (admin/standard)
        // Map ID 1 -> admin, Others -> standard/guest
        $roleName = strtolower($meta['role_name'] ?? 'guest');
        if ($user['role_id'] == 1)
            $roleName = 'admin'; // Force admin mapping

        $_SESSION['user_role'] = $roleName;
        $_SESSION['logged_in'] = true;

        // Permissions logic from Auth::login
        // We need reflection or a public helper in Auth ideally, but let's replicate/call it safe
        // Decode and merge permissions from role and group
        // Since Auth::mergePermissions is private, we'll do a simplified approach or need to make it public?
        // Actually, we can rely on role_id mostly for the welcome check.
        // But for dashboard access we need permissions.

        $rolePerms = json_decode($meta['role_perms'] ?? '[]', true);
        $groupPerms = json_decode($meta['group_perms'] ?? '[]', true);

        // Simple merge for now
        $_SESSION['permissions'] = [
            'all' => ($rolePerms['all'] ?? false) || ($groupPerms['all'] ?? false),
            'modules' => array_merge_recursive($rolePerms['modules'] ?? [], $groupPerms['modules'] ?? []),
            'databases' => array_merge_recursive($rolePerms['databases'] ?? [], $groupPerms['databases'] ?? [])
        ];

        // Load Projects
        Auth::loadUserProjects();

        // Check role for redirection
        // Logic: Admin OR User with Active Projects -> Dashboard
        //        Otherwise -> Welcome Pending
        $hasProjects = !empty($_SESSION['user_projects']);

        if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'administrator' || $_SESSION['permissions']['all'] === true || $hasProjects) {
            $this->redirect('admin/dashboard');
        } else {
            $this->redirect('welcome-pending');
        }
    }
}
