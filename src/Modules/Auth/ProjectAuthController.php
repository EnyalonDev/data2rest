<?php

namespace App\Modules\Auth;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Config;
use App\Core\ActivityLogger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class ProjectAuthController extends BaseController
{
    /**
     * Iniciar flujo OAuth de Google
     * GET /api/projects/{projectId}/auth/google
     */
    public function initiateGoogleAuth($projectId)
    {
        // $projectId viene de la URL (ruta)

        $project = $this->getProject($projectId);
        if (!$project || !$project['external_auth_enabled']) {
            die('External authentication not enabled for this project');
        }

        if (empty($project['google_client_id'])) {
            die('Google Client ID not configured for this project');
        }

        // Detectar redirect_uri desde query param o usar default
        $redirectUri = $_GET['redirect_uri'] ?? null;

        // Construir URL de Google
        $params = [
            'client_id' => $project['google_client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile openid',
            'access_type' => 'online',
            'prompt' => 'select_account' // Forzar selector de cuenta
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Verificar código de Google y crear sesión
     * POST /api/v1/auth/google/verify
     */
    public function verifyGoogleCode()
    {
        $projectId = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;
        if (!$projectId) {
            return $this->json(['error' => 'Project ID required'], 400);
        }

        $project = $this->getProject($projectId);
        if (!$project || !$project['external_auth_enabled']) {
            return $this->json(['error' => 'External authentication not enabled for this project'], 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $code = $data['code'] ?? null;
        $redirectUri = $data['redirect_uri'] ?? null;

        if (!$code || !$redirectUri) {
            return $this->json(['error' => 'Code and redirect_uri required'], 400);
        }

        try {
            // 1. Intercambiar código por token con Google
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $project['google_client_id'],
                    'client_secret' => $project['google_client_secret'],
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                ]
            ]);

            $tokenData = json_decode($response->getBody(), true);
            $idToken = $tokenData['id_token'];

            // 2. Decodificar ID Token para obtener info del usuario
            // En producción, verificar firma con claves públicas de Google
            // Aquí confiamos en la respuesta directa de Google sobre HTTPS
            $payload = json_decode(base64_decode(explode('.', $idToken)[1]), true);

            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'] ?? explode('@', $email)[0];

            $db = Database::getInstance()->getConnection();

            // 3. Buscar o crear usuario
            $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
            $stmt->execute([$googleId, $email]);
            $user = $stmt->fetch();

            if (!$user) {
                // Buscar el rol "Usuario" (rol más básico)
                $roleStmt = $db->prepare("SELECT id FROM roles WHERE name = 'Usuario' LIMIT 1");
                $roleStmt->execute();
                $userRoleId = $roleStmt->fetchColumn();

                // Fallback: si no existe el rol "Usuario", usar NULL (sin rol)
                if (!$userRoleId) {
                    $userRoleId = null;
                }

                // Crear usuario si no existe
                $username = strtolower(explode('@', $email)[0]) . rand(100, 999);
                $stmt = $db->prepare("INSERT INTO users (username, email, google_id, role_id, status, created_at) VALUES (?, ?, ?, ?, 1, datetime('now'))");
                $stmt->execute([$username, $email, $googleId, $userRoleId]);
                $userId = $db->lastInsertId();
                $user = ['id' => $userId, 'email' => $email, 'username' => $username];
            } else {
                // Actualizar google_id si faltaba
                if (empty($user['google_id'])) {
                    $db->prepare("UPDATE users SET google_id = ? WHERE id = ?")->execute([$googleId, $user['id']]);
                }
                $userId = $user['id'];
            }

            // 4. Verificar acceso al proyecto
            $access = $this->hasExternalAccessToProject($userId, $projectId);
            if (!$access) {
                // Auto-aprobar con permisos mínimos de cliente
                $defaultPermissions = [
                    'role' => 'client',
                    'pages' => [], // Sin páginas específicas, se usará la configuración del frontend
                    'data_access' => [
                        'scope' => 'own',
                        'filters' => []
                    ],
                    'actions' => ['read'] // Solo lectura por defecto
                ];

                $stmt = $db->prepare("INSERT INTO project_users (project_id, user_id, external_access_enabled, external_permissions, assigned_at) VALUES (?, ?, 1, ?, datetime('now'))");
                try {
                    $stmt->execute([$projectId, $userId, json_encode($defaultPermissions)]);
                    // Continuar con el flujo normal de autenticación
                    $access = $this->hasExternalAccessToProject($userId, $projectId);
                } catch (Exception $e) {
                    // Ya existía pero estaba deshabilitado
                    return $this->json(['error' => 'User not authorized for this project'], 403);
                }
            }

            if (!$access['external_access_enabled']) {
                return $this->json(['error' => 'User access is disabled'], 403);
            }

            // 5. Generar JWT
            $permissions = json_decode($access['external_permissions'] ?? '{}', true);
            $token = $this->generateJWT($userId, $projectId, $permissions);

            // 6. Registrar sesión
            $createdAt = date('Y-m-d H:i:s');
            $stmt = $db->prepare("INSERT INTO project_sessions (project_id, user_id, token, expires_at, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $expiresAt = date('Y-m-d H:i:s', time() + Config::getSetting('jwt_expiration', 86400));
            $stmt->execute([$projectId, $userId, $token, $expiresAt, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $createdAt]);

            // Log
            ActivityLogger::logAuth($userId, $projectId, 'external_login_success', true);

            return $this->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $userId,
                        'email' => $email,
                        'name' => $name,
                        'permissions' => $permissions
                    ],
                    'project' => [
                        'id' => $project['id'],
                        'name' => $project['name']
                    ],
                    'expires_at' => $expiresAt
                ]
            ]);

        } catch (Exception $e) {
            ActivityLogger::logAuth(0, $projectId, 'external_login_failed', false, $e->getMessage());
            return $this->json(['error' => 'Authentication failed: ' . $e->getMessage()], 401);
        }
    }

    /**
     * Validar token de sesión
     * POST /api/v1/auth/verify-token
     */
    public function verifyToken()
    {
        $projectId = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;
        $token = $this->getBearerToken();

        if (!$projectId || !$token) {
            return $this->json(['error' => 'Project ID and Token required'], 400);
        }

        $decoded = $this->validateJWT($token, $projectId);
        if (!$decoded) {
            return $this->json(['success' => false, 'error' => 'Invalid or expired token'], 401);
        }

        // Verificar sesión activa en BD
        $db = Database::getInstance()->getConnection();
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("SELECT * FROM project_sessions WHERE token = ? AND expires_at > ?");
        $stmt->execute([$token, $now]);
        if (!$stmt->fetch()) {
            return $this->json(['success' => false, 'error' => 'Session terminated'], 401);
        }

        // Obtener permisos actualizados
        $access = $this->hasExternalAccessToProject($decoded->sub, $projectId);
        if (!$access || !$access['external_access_enabled']) {
            return $this->json(['success' => false, 'error' => 'Access revoked'], 403);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'user_id' => $decoded->sub,
                'project_id' => $projectId,
                'permissions' => json_decode($access['external_permissions'] ?? '{}', true),
                'expires_at' => date('c', $decoded->exp)
            ]
        ]);
    }

    /**
     * Cerrar sesión
     * POST /api/v1/auth/logout
     */
    public function logout()
    {
        $token = $this->getBearerToken();
        if ($token) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM project_sessions WHERE token = ?");
            $stmt->execute([$token]);
        }
        return $this->json(['success' => true]);
    }

    /**
     * Registrar actividad externa
     * POST /api/v1/external/{project_id}/log-activity
     */
    public function logExternalActivity($projectIdToLog)
    {
        // $projectIdToLog viene de la URL (ruta)
        $projectIdHeader = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;

        if ($projectIdToLog != $projectIdHeader) {
            return $this->json(['error' => 'Project ID mismatch'], 400);
        }

        $token = $this->getBearerToken();
        $decoded = $this->validateJWT($token, $projectIdToLog);

        if (!$decoded) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        ActivityLogger::logExternal(
            $decoded->sub,
            $projectIdToLog,
            $data['action'] ?? 'unknown',
            $data['resource'] ?? 'unknown',
            $data['resource_id'] ?? 0,
            $data['details'] ?? []
        );

        return $this->json(['success' => true]);
    }

    // --- Helpers ---

    private function getProject($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function hasExternalAccessToProject($userId, $projectId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM project_users WHERE user_id = ? AND project_id = ?");
        $stmt->execute([$userId, $projectId]);
        return $stmt->fetch();
    }

    private function generateJWT($userId, $projectId, $permissions)
    {
        $key = Config::getSetting('jwt_secret');
        $expiration = Config::getSetting('jwt_expiration', 86400);

        $payload = [
            'iss' => 'data2rest',
            'sub' => $userId,
            'project_id' => $projectId,
            'permissions' => $permissions,
            'iat' => time(),
            'exp' => time() + $expiration
        ];

        return JWT::encode($payload, $key, 'HS256');
    }

    private function validateJWT($token, $projectId)
    {
        try {
            $key = Config::getSetting('jwt_secret');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            if ($decoded->project_id != $projectId) {
                return false;
            }

            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getBearerToken()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
