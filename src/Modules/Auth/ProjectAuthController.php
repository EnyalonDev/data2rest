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
    public function verifyGoogleCode($routeProjectId = null)
    {
        $projectId = $routeProjectId ?? ($_SERVER['HTTP_X_PROJECT_ID'] ?? null);
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
                $now = date('Y-m-d H:i:s');
                $stmt = $db->prepare("INSERT INTO users (username, email, google_id, role_id, status, created_at) VALUES (?, ?, ?, ?, 1, ?)");
                $stmt->execute([$username, $email, $googleId, $userRoleId, $now]);
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

                $now = date('Y-m-d H:i:s');
                $stmt = $db->prepare("INSERT INTO project_users (project_id, user_id, external_access_enabled, external_permissions, assigned_at) VALUES (?, ?, 1, ?, ?)");
                try {
                    $stmt->execute([$projectId, $userId, json_encode($defaultPermissions), $now]);
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
                        'email_verified_at' => $user['email_verified_at'] ?? ($user['google_id'] ? date('Y-m-d H:i:s') : null), // Google users are verified by definition
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
     * Registro tradicional (Email/Password)
     * POST /api/projects/{projectId}/auth/register
     */
    public function register($routeProjectId = null)
    {
        // HARD DEBUGS
        header('X-Debug-Check: I_AM_THE_NEW_CODE');

        $projectId = $routeProjectId ?? ($_SERVER['HTTP_X_PROJECT_ID'] ?? null);
        if (!$projectId) {
            return $this->json(['error' => 'Project ID required'], 400);
        }

        $project = $this->getProject($projectId);
        if (!$project || !$project['external_auth_enabled']) {
            return $this->json(['error' => 'External authentication not enabled for this project'], 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $name = $data['name'] ?? null;

        if (!$email || !$password || !$name) {
            return $this->json(['error' => 'Email, password and name are required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], 400);
        }

        if (strlen($password) < 6) {
            return $this->json(['error' => 'Password must be at least 6 characters'], 400);
        }

        $db = Database::getInstance()->getConnection();

        // 1. Verificar si el usuario ya existe
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return $this->json(['error' => 'Email already registered'], 409);
        }

        try {
            // 2. Crear usuario y token de verificación
            $username = strtolower(explode('@', $email)[0]) . rand(100, 999);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $verificationToken = bin2hex(random_bytes(32)); // Nuevo Token Seguro
            $now = date('Y-m-d H:i:s');

            // Buscar el rol "Usuario"
            $roleStmt = $db->prepare("SELECT id FROM roles WHERE name = 'Usuario' LIMIT 1");
            $roleStmt->execute();
            $userRoleId = $roleStmt->fetchColumn() ?: null;

            // INSERT actualizado con verification_token
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role_id, status, verification_token, created_at) VALUES (?, ?, ?, ?, 1, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $userRoleId, $verificationToken, $now]);
            $userId = $db->lastInsertId();

            // 3. Asignar acceso al proyecto
            $defaultPermissions = [
                'role' => 'client',
                'pages' => [],
                'data_access' => ['scope' => 'own', 'filters' => []],
                'actions' => ['read']
            ];

            $stmt = $db->prepare("INSERT INTO project_users (project_id, user_id, external_access_enabled, external_permissions, assigned_at) VALUES (?, ?, 1, ?, ?)");
            $stmt->execute([$projectId, $userId, json_encode($defaultPermissions), $now]);

            // 4. Generar Token y Sesión (Login automático)
            $token = $this->generateJWT($userId, $projectId, $defaultPermissions);
            $expiresAt = date('Y-m-d H:i:s', time() + Config::getSetting('jwt_expiration', 86400));

            $stmt = $db->prepare("INSERT INTO project_sessions (project_id, user_id, token, expires_at, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$projectId, $userId, $token, $expiresAt, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $now]);

            ActivityLogger::logAuth($userId, $projectId, 'external_register_success', true);

            /* DELETE START - Premature response prevents email logic
            $response = $this->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => ['id' => $userId, 'email' => $email, 'name' => $name, 'permissions' => $defaultPermissions],
                    'project' => ['id' => $project['id'], 'name' => $project['name']],
                    'expires_at' => $expiresAt
                ]
            ]);
            DELETE END */

            // 5. Enviar Email de Bienvenida
            $emailDebug = ['attempted' => false, 'success' => false, 'error' => null];
            try {
                // Construir enlace de verificación (Backend -> Frontend Redirect)
                $backendUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

                // Detectar Frontend URL para redirección
                $frontendUrl = $_SERVER['HTTP_ORIGIN'] ?? '';
                // Si no hay Origin, intentar referer o dejar vacío
                if (!$frontendUrl && isset($_SERVER['HTTP_REFERER'])) {
                    $parsed = parse_url($_SERVER['HTTP_REFERER']);
                    $frontendUrl = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
                }

                $redirectParam = $frontendUrl ? "&redirect_to=" . urlencode($frontendUrl) : "";
                $verifyLink = "$backendUrl/api/v1/projects/$projectId/auth/verify-email?token=$verificationToken$redirectParam";

                $mailService = new \App\Services\MailService();
                $projectName = $project['name'] ?? 'Mundo Jácome\'s';

                $emailDebug['attempted'] = true;
                $sent = $mailService->sendWelcome($email, $name, $verifyLink, $projectName);
                $emailDebug['success'] = $sent;

                if (!$sent) {
                    $emailDebug['error'] = 'MailService returned false (check server logs)';
                }

                ActivityLogger::logAuth($userId, $projectId, 'email_sent_welcome', true);
            } catch (\Exception $e) {
                ActivityLogger::logAuth($userId, $projectId, 'email_failed', false, $e->getMessage());
                \App\Core\Logger::log('MAIL_EXCEPTION', ['error' => $e->getMessage()]);
                $emailDebug['error'] = $e->getMessage();
            }

            // Append debug info to response
            $response = $this->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $userId,
                        'email' => $email,
                        'name' => $name,
                        'email_verified_at' => null, // Newly registered via email is not verified
                        'permissions' => $defaultPermissions
                    ],
                    'project' => ['id' => $project['id'], 'name' => $project['name']],
                    'expires_at' => $expiresAt,
                    'debug_email' => $emailDebug
                ]
            ]);
            return $response;

        } catch (Exception $e) {
            return $this->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Login tradicional (Email/Password)
     * POST /api/projects/{projectId}/auth/login
     */
    public function login($routeProjectId = null)
    {
        $projectId = $routeProjectId ?? ($_SERVER['HTTP_X_PROJECT_ID'] ?? null);
        if (!$projectId) {
            return $this->json(['error' => 'Project ID required'], 400);
        }

        $project = $this->getProject($projectId);
        if (!$project || !$project['external_auth_enabled']) {
            return $this->json(['error' => 'External authentication not enabled for this project'], 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password are required'], 400);
        }

        $db = Database::getInstance()->getConnection();

        // 1. Buscar usuario
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            ActivityLogger::logAuth(0, $projectId, 'external_login_failed_credentials', false, "Email: $email");
            return $this->json(['error' => 'Invalid email or password'], 401);
        }

        // 2. Verificar acceso al proyecto
        $access = $this->hasExternalAccessToProject($user['id'], $projectId);
        if (!$access || !$access['external_access_enabled']) {
            return $this->json(['error' => 'Access denied for this project'], 403);
        }

        try {
            // 3. Generar JWT
            $permissions = json_decode($access['external_permissions'] ?? '{}', true);
            $token = $this->generateJWT($user['id'], $projectId, $permissions);
            $expiresAt = date('Y-m-d H:i:s', time() + Config::getSetting('jwt_expiration', 86400));
            $now = date('Y-m-d H:i:s');

            // 4. Registrar sesión
            $stmt = $db->prepare("INSERT INTO project_sessions (project_id, user_id, token, expires_at, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$projectId, $user['id'], $token, $expiresAt, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $now]);

            ActivityLogger::logAuth($user['id'], $projectId, 'external_login_success', true);

            return $this->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['username'], // Podríamos mejorar guardando nombres reales
                        'email_verified_at' => $user['email_verified_at'],
                        'permissions' => $permissions
                    ],
                    'project' => ['id' => $project['id'], 'name' => $project['name']],
                    'expires_at' => $expiresAt
                ]
            ]);

        } catch (Exception $e) {
            return $this->json(['error' => 'Login failed: ' . $e->getMessage()], 500);
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

        // Get user details
        $stmt = $db->prepare("SELECT public_name, phone, address, tax_id FROM users WHERE id = ?");
        $stmt->execute([$decoded->sub]);
        $user = $stmt->fetch();

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
                'email_verified_at' => $this->getUserVerificationStatus($decoded->sub),
                'public_name' => $user['public_name'],
                'phone' => $user['phone'],
                'address' => $user['address'],
                'tax_id' => $user['tax_id'],
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

    /**
     * Verificar email mediante token
     * GET /api/v1/projects/{projectId}/auth/verify-email
     */
    public function verifyEmail($routeProjectId = null)
    {
        $projectId = $routeProjectId ?? ($_GET['project_id'] ?? null);
        $projectId = $routeProjectId ?? ($_GET['project_id'] ?? null);
        $token = $_GET['token'] ?? null;
        $redirectTo = $_GET['redirect_to'] ?? null;

        if (!$projectId || !$token) {
            return $this->json(['error' => 'Invalid verification link'], 400);
        }

        $db = Database::getInstance()->getConnection();

        // Buscar usuario por token de verificación
        // Nota: verification_token debe ser único globalmente o por proyecto. 
        // Aquí asumimos global en la tabla users para simplificar.
        $stmt = $db->prepare("SELECT * FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            // Token inválido o ya usado (si lo limpiamos)
            return $this->redirectToFrontend($projectId, 'error', 'invalid_token', $redirectTo);
        }

        // Verificar si ya estaba verificado
        if ($user['email_verified_at']) {
            // Ya verificado, redirigir al éxito de todas formas
            return $this->redirectToFrontend($projectId, 'success', 'already_verified', $redirectTo);
        }

        try {
            // Marcar como verificado
            $now = date('Y-m-d H:i:s');

            // Usamos driver-agnostic query
            $update = $db->prepare("UPDATE users SET email_verified_at = ?, verification_token = NULL WHERE id = ?");
            $update->execute([$now, $user['id']]);

            // Log
            ActivityLogger::logAuth($user['id'], $projectId, 'email_verified', true);

            // Redirigir al Frontend
            return $this->redirectToFrontend($projectId, 'success', null, $redirectTo);

        } catch (Exception $e) {
            return $this->json(['error' => 'Verification failed'], 500);
        }
    }

    private function redirectToFrontend($projectId, $status, $code = null, $customFrontendUrl = null)
    {
        // Obtener URL del frontend desde el parámetro, configuración o default
        $frontendUrl = $customFrontendUrl ?? ($_SERVER['HTTP_ORIGIN'] ?? 'https://d2r.nestorovallos.com');

        $url = "$frontendUrl/verify?status=$status" . ($code ? "&code=$code" : "");

        header("Location: $url");
        exit;
    }

    /**
     * Log CLIENT-SIDE debug info (Dev Only)
     * POST /api/v1/external/{projectId}/client-debug
     */
    public function logExternalClientDebug($projectIdToLog)
    {
        // Only allow if IS_LOCAL equivalent logic or specific permission
        // For now, open but logged

        $data = json_decode(file_get_contents('php://input'), true);
        $message = $data['message'] ?? 'No message';
        $level = $data['level'] ?? 'INFO';

        // __DIR__ is src/Modules/Auth.
        // We need to go up: Auth -> Modules -> src -> [root] -> data -> logs
        // That is 4 levels up.

        $logDir = __DIR__ . '/../../../../data/logs';
        if (!is_dir($logDir))
            mkdir($logDir, 0777, true);

        $date = date('Y-m-d');
        $logFile = "$logDir/client_debug_{$projectIdToLog}_$date.log";

        $ts = date('H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $entry = "[$ts] [$ip] [$level] $message" . PHP_EOL;

        file_put_contents($logFile, $entry, FILE_APPEND);

        return $this->json(['success' => true]);
    }

    /**
     * Actualizar perfil de usuario
     * POST /api/v1/projects/{projectId}/auth/profile
     */
    public function updateProfile($routeProjectId = null)
    {
        $projectId = $routeProjectId ?? ($_SERVER['HTTP_X_PROJECT_ID'] ?? null);
        $token = $this->getBearerToken();

        if (!$projectId || !$token) {
            return $this->json(['error' => 'Project ID and Token required'], 400);
        }

        $decoded = $this->validateJWT($token, $projectId);
        if (!$decoded) {
            return $this->json(['error' => 'Invalid token'], 401);
        }

        $userId = $decoded->sub;
        $data = json_decode(file_get_contents('php://input'), true);

        // Campos permitidos
        $allowedFields = ['public_name', 'phone', 'address', 'tax_id'];
        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return $this->json(['success' => true]); // Nada que actualizar
        }

        // Añadir ID al final
        $params[] = $userId;

        try {
            $db = Database::getInstance()->getConnection();
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Log activity
            ActivityLogger::logAuth($userId, $projectId, 'profile_updated', true);

            return $this->json([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);

        } catch (Exception $e) {
            return $this->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
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
        $key = (string) (Config::getSetting('jwt_secret') ?? 'default_secret_key_change_me_to_something_very_secure_and_long_enough_32chars');
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
            $key = (string) (Config::getSetting('jwt_secret') ?? 'default_secret_key_change_me_to_something_very_secure_and_long_enough_32chars');
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

    private function getUserVerificationStatus($userId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT email_verified_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}
