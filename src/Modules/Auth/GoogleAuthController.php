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
            die("Google Login is not configured.");
        }
        $authUrl = $client->createAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    public function handleCallback()
    {
        $client = $this->getClient();
        if (!$client) {
            $this->redirect('login');
        }

        if (isset($_GET['code'])) {
            try {
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                if (isset($token['error'])) {
                    throw new \Exception($token['error_description'] ?? 'Error fetching token');
                }
                $client->setAccessToken($token['access_token']);

                // Get User Info
                $google_oauth = new GoogleServiceOauth2($client);
                $google_account_info = $google_oauth->userinfo->get();

                $email = $google_account_info->email;
                $googleId = $google_account_info->id;
                $name = $google_account_info->name;
                $avatar = $google_account_info->picture;

                // Check if user exists
                $db = Database::getInstance()->getConnection();

                // 1. Check by Google ID
                $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ?");
                $stmt->execute([$googleId]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($user) {
                    // Start session
                    $this->loginUser($user);
                } else {
                    // 2. Check by Email (Link account if exists)
                    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($user) {
                        // Link account
                        $update = $db->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                        $update->execute([$googleId, $user['id']]);
                        $this->loginUser($user);
                    } else {
                        // 3. Create new user (Standard/Guest)
                        // If no username, use email prefix or safe version of name
                        $username = explode('@', $email)[0];
                        // Ensure unique username
                        $check = $db->prepare("SELECT count(*) FROM users WHERE username = ?");
                        $check->execute([$username]);
                        if ($check->fetchColumn() > 0) {
                            $username .= rand(100, 999);
                        }

                        $roleId = 4; // Default role ID (Usuario/Standard)
                        // If roles table structure isn't guaranteed, we should query it, but 4 is safe based on listing.

                        $insert = $db->prepare("INSERT INTO users (username, email, password, role_id, google_id, created_at, status) VALUES (?, ?, NULL, ?, ?, datetime('now'), 1)");
                        // Note: Datetime syntax might vary by DB, using standard
                        // MySQL uses NOW(), SQLite uses datetime('now')
                        $adapter = Database::getInstance()->getAdapter();
                        $now = ($adapter->getType() === 'sqlite') ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s'); // PHP Generated is safest

                        $insert->execute([$username, $email, $roleId, $googleId]);
                        $newId = $db->lastInsertId();

                        // Fetch new user
                        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$newId]);
                        $newUser = $stmt->fetch(\PDO::FETCH_ASSOC);

                        $this->loginUser($newUser);
                    }
                }

            } catch (\Exception $e) {
                // Log error
                error_log("Google Login Error: " . $e->getMessage());
                // FORCE DEBUG ON SCREEN
                echo "<div style='padding:50px;font-family:sans-serif;'>";
                echo "<h1>Google Login Error</h1>";
                echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
                echo "</div>";
                exit; // Stop redirect
            }
        } else {
            die("Google did not return an authorization code.");
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
        if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'administrator' || $_SESSION['permissions']['all'] === true) {
            $this->redirect('admin/dashboard');
        } else {
            $this->redirect('welcome-pending');
        }
    }
}
