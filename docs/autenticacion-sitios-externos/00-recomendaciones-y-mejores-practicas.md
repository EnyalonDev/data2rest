# 💡 Recomendaciones y Mejores Prácticas

## 🎯 Observaciones Importantes Antes de Implementar

---

## 1️⃣ Seguridad y Producción

### **🔐 Gestión de Secretos**

> [!CAUTION]
> **NUNCA almacenar secretos en código o variables de entorno expuestas**

**Recomendaciones:**

```bash
# ❌ MAL - En .env público
GOOGLE_CLIENT_SECRET=abc123secret

# ✅ BIEN - Usar gestor de secretos
# Vercel: Variables de entorno encriptadas
# AWS: AWS Secrets Manager
# Google Cloud: Secret Manager
```

**Implementación recomendada:**

```php
// En Data2Rest - Almacenar secretos encriptados en BD
class ProjectAuthController 
{
    private function getProjectOAuthConfig($projectId) 
    {
        $project = $this->getProject($projectId);
        
        // Desencriptar client_secret antes de usar
        $clientSecret = $this->decrypt($project['google_client_secret']);
        
        return [
            'client_id' => $project['google_client_id'],
            'client_secret' => $clientSecret
        ];
    }
    
    private function decrypt($encrypted) 
    {
        $key = Config::getSetting('encryption_key');
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key);
    }
}
```

---

### **🛡️ Rate Limiting Agresivo**

**Problema:** Ataques de fuerza bruta en endpoints de autenticación

**Solución:**

```php
// Límites recomendados por IP:
- /auth/google/verify: 10 intentos / 15 minutos
- /auth/login: 5 intentos / 15 minutos
- /auth/register: 3 intentos / hora
- /auth/verify-token: 100 intentos / minuto
```

**Implementación:**

```php
// Usar Redis para rate limiting distribuido
class RateLimiter 
{
    public static function check($key, $maxAttempts, $decayMinutes) 
    {
        $redis = Redis::getInstance();
        $attempts = $redis->get($key) ?? 0;
        
        if ($attempts >= $maxAttempts) {
            throw new TooManyRequestsException();
        }
        
        $redis->incr($key);
        $redis->expire($key, $decayMinutes * 60);
    }
}

// Uso en controlador
RateLimiter::check("auth:verify:{$ip}", 10, 15);
```

---

### **🔒 HTTPS Obligatorio**

> [!WARNING]
> **Rechazar todas las peticiones HTTP en producción**

```php
// En index.php o middleware
if ($_SERVER['HTTPS'] !== 'on' && Config::get('env') === 'production') {
    header('HTTP/1.1 403 Forbidden');
    die('HTTPS requerido');
}
```

---

## 2️⃣ Experiencia de Usuario

### **⏱️ Tokens de Larga Duración con Refresh**

**Problema:** Usuarios deben hacer login cada 24 horas

**Solución:** Implementar refresh tokens

```php
// Generar dos tokens
$accessToken = $this->generateJWT($userId, $projectId, 3600); // 1 hora
$refreshToken = $this->generateRefreshToken($userId, $projectId, 2592000); // 30 días

// Guardar refresh token
$db->prepare("
    INSERT INTO project_sessions (project_id, user_id, token, refresh_token, expires_at)
    VALUES (?, ?, ?, ?, ?)
")->execute([$projectId, $userId, $accessToken, $refreshToken, date('Y-m-d H:i:s', time() + 2592000)]);

return [
    'access_token' => $accessToken,
    'refresh_token' => $refreshToken,
    'expires_in' => 3600
];
```

**Endpoint de refresh:**

```php
// POST /api/v1/auth/refresh
public function refreshToken() 
{
    $refreshToken = $_POST['refresh_token'];
    
    // Validar refresh token
    $session = $this->validateRefreshToken($refreshToken);
    
    // Generar nuevo access token
    $newAccessToken = $this->generateJWT($session['user_id'], $session['project_id'], 3600);
    
    return [
        'access_token' => $newAccessToken,
        'expires_in' => 3600
    ];
}
```

---

### **📧 Verificación de Email**

**Recomendación:** Agregar verificación de email para registro tradicional

```php
// Al registrarse
$verificationToken = bin2hex(random_bytes(32));

$db->prepare("
    INSERT INTO users (email, password, status, verification_token)
    VALUES (?, ?, 0, ?)
")->execute([$email, $hashedPassword, $verificationToken]);

// Enviar email
$this->sendVerificationEmail($email, $verificationToken);

// Endpoint de verificación
// GET /api/v1/auth/verify-email?token=...
public function verifyEmail() 
{
    $token = $_GET['token'];
    
    $db->prepare("
        UPDATE users SET status = 1, verification_token = NULL
        WHERE verification_token = ?
    ")->execute([$token]);
}
```

---

### **🔄 "Recordarme" (Remember Me)**

```tsx
// En frontend
<input type="checkbox" id="remember" />
<label>Mantenerme conectado</label>

// Al hacer login
const rememberMe = document.getElementById('remember').checked;

fetch('/api/auth/login', {
    body: JSON.stringify({
        email,
        password,
        remember_me: rememberMe
    })
});
```

```php
// En backend - ajustar expiración
$expiration = $rememberMe ? 2592000 : 86400; // 30 días vs 24 horas
```

---

## 3️⃣ Escalabilidad

### **📊 Índices de Base de Datos**

**Crítico para rendimiento:**

```sql
-- Índices recomendados
CREATE INDEX idx_project_users_lookup ON project_users(project_id, user_id);
CREATE INDEX idx_project_users_external ON project_users(external_access_enabled);
CREATE INDEX idx_project_sessions_token ON project_sessions(token);
CREATE INDEX idx_project_sessions_expires ON project_sessions(expires_at);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_google_id ON users(google_id);
```

---

### **🗑️ Limpieza Automática de Sesiones**

**Problema:** Tabla `project_sessions` crece indefinidamente

**Solución:** Cron job de limpieza

```php
// scripts/cleanup_sessions.php
<?php
require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

// Eliminar sesiones expiradas (más de 7 días)
$stmt = $db->prepare("
    DELETE FROM project_sessions 
    WHERE expires_at < datetime('now', '-7 days')
");
$stmt->execute();

echo "Sesiones eliminadas: " . $stmt->rowCount() . "\n";
```

**Crontab:**
```bash
# Ejecutar diariamente a las 3 AM
0 3 * * * cd /opt/homebrew/var/www/data2rest && php scripts/cleanup_sessions.php
```

---

### **💾 Cache de Validaciones**

**Problema:** Validar token en cada request es costoso

**Solución:** Cachear validaciones por 5 minutos

```php
class ProjectAuthController 
{
    public function verifyToken() 
    {
        $token = $this->getBearerToken();
        $projectId = $_SERVER['HTTP_X_PROJECT_ID'];
        
        $cacheKey = "token:valid:{$projectId}:{$token}";
        
        // Verificar cache primero
        if ($cached = Cache::get($cacheKey)) {
            return $this->json($cached);
        }
        
        // Validar token
        $result = $this->validateJWT($token, $projectId);
        
        // Cachear por 5 minutos
        Cache::put($cacheKey, $result, 300);
        
        return $this->json($result);
    }
}
```

---

## 4️⃣ Monitoreo y Debugging

### **📝 Logs Estructurados**

**Recomendación:** Usar logs JSON para análisis

```php
class AuthLogger 
{
    public static function logAuthAttempt($userId, $projectId, $success, $reason = null) 
    {
        $log = [
            'timestamp' => date('c'),
            'event' => 'auth_attempt',
            'user_id' => $userId,
            'project_id' => $projectId,
            'success' => $success,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];
        
        error_log(json_encode($log));
    }
}

// Uso
AuthLogger::logAuthAttempt($userId, $projectId, false, 'invalid_credentials');
```

---

### **📊 Métricas Importantes**

**Implementar tracking de:**

```
- Intentos de login exitosos/fallidos por proyecto
- Tiempo promedio de autenticación
- Tokens expirados vs activos
- Usuarios activos por proyecto
- Errores de validación más comunes
```

**Dashboard recomendado:**

```php
// En panel de Data2Rest
public function authMetrics($projectId) 
{
    $db = Database::getInstance()->getConnection();
    
    // Últimos 30 días
    $metrics = [
        'total_logins' => $this->getTotalLogins($projectId, 30),
        'failed_attempts' => $this->getFailedAttempts($projectId, 30),
        'active_users' => $this->getActiveUsers($projectId, 30),
        'avg_session_duration' => $this->getAvgSessionDuration($projectId)
    ];
    
    return $this->view('admin/projects/auth_metrics', $metrics);
}
```

---

## 5️⃣ Mejoras Futuras (Roadmap)

### **🔐 Autenticación Multi-Factor (2FA)**

```php
// Fase 2 - Agregar después de implementación inicial
- TOTP (Google Authenticator)
- SMS (Twilio)
- Email con código
```

---

### **🌐 OAuth con Otros Proveedores**

```php
// Extender para soportar:
- Facebook Login
- Apple Sign In
- Microsoft Account
- GitHub OAuth
```

**Estructura extensible:**

```php
interface OAuthProvider 
{
    public function getAuthUrl();
    public function verifyCode($code);
    public function getUserInfo($accessToken);
}

class GoogleOAuthProvider implements OAuthProvider { }
class FacebookOAuthProvider implements OAuthProvider { }
```

---

### **👥 Single Sign-On (SSO)**

**Para empresas con múltiples proyectos:**

```
Usuario hace login una vez
  ↓
Puede acceder a TODOS sus proyectos
  ↓
Sin volver a autenticarse
```

---

## 6️⃣ Testing

### **🧪 Tests Críticos a Implementar**

```php
// tests/Auth/ProjectAuthTest.php

class ProjectAuthTest extends TestCase 
{
    public function test_user_can_login_with_google() { }
    
    public function test_user_cannot_access_unauthorized_project() { }
    
    public function test_token_expires_after_24_hours() { }
    
    public function test_admin_can_change_user_role() { }
    
    public function test_client_only_sees_own_data() { }
    
    public function test_rate_limiting_blocks_brute_force() { }
}
```

---

## 7️⃣ Documentación para Desarrolladores

### **📚 Crear Guía de Integración**

**Para desarrolladores que crearán sitios web:**

```markdown
# Guía Rápida: Integrar Autenticación

## 1. Configurar Proyecto en Data2Rest
- Ir a Proyectos → [Tu Proyecto] → Autenticación Externa
- Agregar Google Client ID y Secret
- Configurar dominios permitidos

## 2. Instalar en Next.js
npm install @tanstack/react-query

## 3. Copiar Componentes
- GoogleLoginButton.tsx
- RoleGuard.tsx
- Can.tsx

## 4. Configurar Variables
NEXT_PUBLIC_DATA2REST_URL=...
NEXT_PUBLIC_PROJECT_ID=...

## 5. Proteger Rutas
// middleware.ts
export { default } from '@/lib/auth-middleware'
```

---

## ✅ Checklist Pre-Implementación

### **Antes de Empezar:**

- [ ] Revisar todos los documentos de la carpeta `docs/autenticacion-sitios-externos/`
- [ ] Decidir si usar SQLite, MySQL o PostgreSQL
- [ ] Crear cuenta en Google Cloud Console
- [ ] Configurar proyecto de prueba en Vercel
- [ ] Preparar entorno de desarrollo local

### **Durante Implementación:**

- [ ] Seguir orden: Backend → Frontend → Testing
- [ ] Implementar rate limiting desde el inicio
- [ ] Agregar logs detallados
- [ ] Probar con múltiples usuarios y roles
- [ ] Validar seguridad con intentos de suplantación

### **Después de Implementación:**

- [ ] Configurar cron job de limpieza
- [ ] Implementar monitoreo de métricas
- [ ] Crear documentación de usuario final
- [ ] Planear roadmap de mejoras futuras

---

## 🎯 Prioridades Recomendadas

### **Fase 1 (MVP):**
1. Autenticación con Google OAuth
2. Roles básicos (admin, client)
3. Protección de rutas
4. Panel de administración de usuarios

### **Fase 2 (Mejoras):**
1. Autenticación tradicional (email/password)
2. Refresh tokens
3. Rol "staff" intermedio
4. Métricas y dashboard

### **Fase 3 (Avanzado):**
1. Autenticación multi-factor
2. Otros proveedores OAuth
3. Single Sign-On
4. Auditoría completa

---

## 💬 Consideraciones Finales

### **🚀 Ventajas de Este Diseño:**

✅ **Escalable** - Soporta miles de usuarios y proyectos  
✅ **Flexible** - Fácil agregar nuevos proveedores OAuth  
✅ **Seguro** - Múltiples capas de validación  
✅ **Mantenible** - Código organizado y documentado  
✅ **Extensible** - Preparado para futuras mejoras  

### **⚠️ Puntos de Atención:**

⚠️ **Rendimiento** - Implementar cache y índices desde el inicio  
⚠️ **Seguridad** - Nunca exponer secretos, usar HTTPS siempre  
⚠️ **UX** - Implementar refresh tokens para evitar re-logins constantes  
⚠️ **Monitoreo** - Logs y métricas son críticos para debugging  

---

**Documento creado:** 2026-01-24  
**Versión:** 1.0
