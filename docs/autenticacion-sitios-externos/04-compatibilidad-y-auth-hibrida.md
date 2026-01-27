# 🔧 Compatibilidad Multi-BD y Autenticación Híbrida

## 1️⃣ Compatibilidad con SQLite, MySQL y PostgreSQL

### ✅ **Respuesta:** El plan es 100% compatible con los 3 motores

---

### **Cambios en la Base de Datos**

#### **Nuevas Columnas en `projects`:**
```sql
-- Compatible con SQLite, MySQL y PostgreSQL
ALTER TABLE projects ADD COLUMN google_client_id VARCHAR(255);
ALTER TABLE projects ADD COLUMN google_client_secret VARCHAR(255);
ALTER TABLE projects ADD COLUMN domain VARCHAR(255);
ALTER TABLE projects ADD COLUMN allowed_origins TEXT;
ALTER TABLE projects ADD COLUMN external_auth_enabled INTEGER DEFAULT 0;
```

#### **Nuevas Columnas en `project_users`:**
```sql
ALTER TABLE project_users ADD COLUMN external_permissions TEXT;
ALTER TABLE project_users ADD COLUMN external_access_enabled INTEGER DEFAULT 1;
```

#### **Nueva Tabla `project_sessions`:**
```sql
CREATE TABLE project_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,  -- SQLite
    -- id SERIAL PRIMARY KEY,               -- PostgreSQL
    -- id INT AUTO_INCREMENT PRIMARY KEY,   -- MySQL
    project_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    token VARCHAR(512) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,          -- SQLite/MySQL
    -- expires_at TIMESTAMP NOT NULL,       -- PostgreSQL
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

### **Adaptación Automática por Motor**

El `Installer.php` ya maneja las diferencias:

```php
// En Installer::syncSchema()

if ($adapter->getType() === 'mysql') {
    $sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);
    $sql = str_replace('DATETIME', 'DATETIME', $sql);
    $sql = str_replace('INTEGER', 'INT', $sql);
} elseif ($adapter->getType() === 'pgsql') {
    $sql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'SERIAL PRIMARY KEY', $sql);
    $sql = str_replace('DATETIME', 'TIMESTAMP', $sql);
    $sql = str_replace('INTEGER DEFAULT', 'INTEGER DEFAULT', $sql);
} else {
    // SQLite - sin cambios
}
```

---

### **Script de Migración Multi-BD**

**Archivo:** `scripts/migrate_external_auth.php`

```php
<?php
require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Config;

echo "=== Migración: Autenticación Externa ===\n\n";

$adapter = Database::getInstance()->getAdapter();
$db = Database::getInstance()->getConnection();
$type = $adapter->getType();

echo "Motor de BD detectado: " . strtoupper($type) . "\n\n";

// 1. Agregar columnas a projects
echo "1. Extendiendo tabla 'projects'...\n";

$projectColumns = [
    'google_client_id' => 'VARCHAR(255)',
    'google_client_secret' => 'VARCHAR(255)',
    'domain' => 'VARCHAR(255)',
    'allowed_origins' => 'TEXT',
    'external_auth_enabled' => 'INTEGER DEFAULT 0'
];

foreach ($projectColumns as $col => $colType) {
    try {
        // Verificar si la columna ya existe
        $exists = false;
        if ($type === 'sqlite') {
            $stmt = $db->query("PRAGMA table_info(projects)");
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            $exists = in_array($col, $cols);
        } elseif ($type === 'mysql') {
            $stmt = $db->query("SHOW COLUMNS FROM projects LIKE '$col'");
            $exists = $stmt->rowCount() > 0;
        } elseif ($type === 'pgsql') {
            $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'projects' AND column_name = ?");
            $stmt->execute([$col]);
            $exists = $stmt->rowCount() > 0;
        }

        if (!$exists) {
            $alterSql = "ALTER TABLE " . $adapter->quoteName('projects') . 
                       " ADD COLUMN " . $adapter->quoteName($col) . " $colType";
            $db->exec($alterSql);
            echo "  ✓ Columna '$col' agregada\n";
        } else {
            echo "  - Columna '$col' ya existe\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Error en '$col': " . $e->getMessage() . "\n";
    }
}

// 2. Agregar columnas a project_users
echo "\n2. Extendiendo tabla 'project_users'...\n";

$projectUserColumns = [
    'external_permissions' => 'TEXT',
    'external_access_enabled' => 'INTEGER DEFAULT 1'
];

foreach ($projectUserColumns as $col => $colType) {
    try {
        $exists = false;
        if ($type === 'sqlite') {
            $stmt = $db->query("PRAGMA table_info(project_users)");
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            $exists = in_array($col, $cols);
        } elseif ($type === 'mysql') {
            $stmt = $db->query("SHOW COLUMNS FROM project_users LIKE '$col'");
            $exists = $stmt->rowCount() > 0;
        } elseif ($type === 'pgsql') {
            $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'project_users' AND column_name = ?");
            $stmt->execute([$col]);
            $exists = $stmt->rowCount() > 0;
        }

        if (!$exists) {
            $alterSql = "ALTER TABLE " . $adapter->quoteName('project_users') . 
                       " ADD COLUMN " . $adapter->quoteName($col) . " $colType";
            $db->exec($alterSql);
            echo "  ✓ Columna '$col' agregada\n";
        } else {
            echo "  - Columna '$col' ya existe\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Error en '$col': " . $e->getMessage() . "\n";
    }
}

// 3. Crear tabla project_sessions
echo "\n3. Creando tabla 'project_sessions'...\n";

try {
    $existsSql = $adapter->getTableExistsSQL('project_sessions');
    $result = $db->query($existsSql);
    $exists = (bool) $result->fetchColumn();

    if (!$exists) {
        $createSql = "CREATE TABLE project_sessions (";
        
        if ($type === 'sqlite') {
            $createSql .= "
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                token VARCHAR(512) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
        } elseif ($type === 'mysql') {
            $createSql .= "
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `project_id` INT NOT NULL,
                `user_id` INT NOT NULL,
                `token` VARCHAR(512) NOT NULL UNIQUE,
                `expires_at` DATETIME NOT NULL,
                `ip_address` VARCHAR(45),
                `user_agent` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            )";
        } elseif ($type === 'pgsql') {
            $createSql .= "
                id SERIAL PRIMARY KEY,
                project_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                token VARCHAR(512) NOT NULL UNIQUE,
                expires_at TIMESTAMP NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
        }

        $db->exec($createSql);
        echo "  ✓ Tabla 'project_sessions' creada\n";
    } else {
        echo "  - Tabla 'project_sessions' ya existe\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// 4. Agregar configuración JWT
echo "\n4. Configurando JWT...\n";

try {
    $stmt = $db->prepare("INSERT OR IGNORE INTO system_settings (key_name, value) VALUES (?, ?)");
    // Para MySQL/PostgreSQL usar INSERT IGNORE o ON CONFLICT
    if ($type === 'mysql') {
        $stmt = $db->prepare("INSERT IGNORE INTO system_settings (key_name, value) VALUES (?, ?)");
    } elseif ($type === 'pgsql') {
        $stmt = $db->prepare("INSERT INTO system_settings (key_name, value) VALUES (?, ?) ON CONFLICT (key_name) DO NOTHING");
    }

    $jwtSecret = bin2hex(random_bytes(32));
    $stmt->execute(['jwt_secret', $jwtSecret]);
    $stmt->execute(['jwt_expiration', '86400']);
    $stmt->execute(['external_auth_enabled', '1']);

    echo "  ✓ Configuración JWT agregada\n";
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Migración completada ===\n";
```

**Ejecutar:**
```bash
php scripts/migrate_external_auth.php
```

---

## 2️⃣ Autenticación Híbrida: Google OAuth + Usuario/Contraseña

### ✅ **Respuesta:** Sí, se pueden combinar ambos métodos

---

### **Arquitectura Híbrida**

```
Usuario puede autenticarse con:
  ├── Google OAuth (recomendado)
  └── Usuario y Contraseña (tradicional)

Ambos métodos:
  ├── Generan el mismo tipo de token JWT
  ├── Tienen los mismos permisos
  └── Acceden a las mismas funcionalidades
```

---

### **Implementación en Data2Rest**

#### **Nuevo Endpoint: Registro Tradicional**

**Ruta:** `POST /api/v1/auth/register`

**Headers:**
```
Content-Type: application/json
X-Project-ID: {project_id}
```

**Body:**
```json
{
    "email": "usuario@example.com",
    "password": "contraseña_segura",
    "name": "Juan Pérez"
}
```

**Respuesta:**
```json
{
    "success": true,
    "message": "Usuario registrado. Espera aprobación del administrador.",
    "user_id": 15
}
```

---

#### **Nuevo Endpoint: Login Tradicional**

**Ruta:** `POST /api/v1/auth/login`

**Headers:**
```
Content-Type: application/json
X-Project-ID: {project_id}
```

**Body:**
```json
{
    "email": "usuario@example.com",
    "password": "contraseña_segura"
}
```

**Respuesta (igual que Google OAuth):**
```json
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "user": {
            "id": 15,
            "email": "usuario@example.com",
            "name": "Juan Pérez",
            "permissions": {...}
        },
        "expires_at": "2026-01-25T22:00:00Z"
    }
}
```

---

### **Código del Controlador**

**Archivo:** `src/Modules/Auth/ProjectAuthController.php`

```php
/**
 * Registro tradicional con email/password
 */
public function register()
{
    $projectId = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);

    // Validar proyecto
    $project = $this->getProject($projectId);
    if (!$project || !$project['external_auth_enabled']) {
        return $this->json(['error' => 'Proyecto no válido'], 403);
    }

    // Validar datos
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;
    $name = $data['name'] ?? null;

    if (!$email || !$password || !$name) {
        return $this->json(['error' => 'Datos incompletos'], 400);
    }

    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $this->json(['error' => 'Email inválido'], 400);
    }

    // Validar contraseña (mínimo 8 caracteres)
    if (strlen($password) < 8) {
        return $this->json(['error' => 'Contraseña debe tener al menos 8 caracteres'], 400);
    }

    $db = Database::getInstance()->getConnection();

    // Verificar si el email ya existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return $this->json(['error' => 'Email ya registrado'], 409);
    }

    // Crear usuario
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $username = explode('@', $email)[0];

    // Asegurar username único
    $checkUsername = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $checkUsername->execute([$username]);
    if ($checkUsername->fetchColumn() > 0) {
        $username .= rand(100, 999);
    }

    $insertUser = $db->prepare("
        INSERT INTO users (username, email, password, role_id, status, created_at)
        VALUES (?, ?, ?, 4, 0, ?)
    ");
    $insertUser->execute([
        $username,
        $email,
        $hashedPassword,
        date('Y-m-d H:i:s')
    ]);

    $userId = $db->lastInsertId();

    // Asignar al proyecto (pendiente de aprobación)
    $insertProjectUser = $db->prepare("
        INSERT INTO project_users (project_id, user_id, external_access_enabled, assigned_at)
        VALUES (?, ?, 0, ?)
    ");
    $insertProjectUser->execute([
        $projectId,
        $userId,
        date('Y-m-d H:i:s')
    ]);

    // Notificar a administradores
    $this->notifyAdmins($projectId, $userId, $email);

    return $this->json([
        'success' => true,
        'message' => 'Usuario registrado. Espera aprobación del administrador.',
        'user_id' => $userId
    ], 201);
}

/**
 * Login tradicional con email/password
 */
public function loginTraditional()
{
    $projectId = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);

    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$email || !$password) {
        return $this->json(['error' => 'Credenciales incompletas'], 400);
    }

    $db = Database::getInstance()->getConnection();

    // Buscar usuario
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return $this->json(['error' => 'Credenciales inválidas'], 401);
    }

    // Verificar acceso al proyecto
    $access = $this->hasExternalAccessToProject($user['id'], $projectId);
    if (!$access) {
        return $this->json(['error' => 'No tienes acceso a este proyecto'], 403);
    }

    // Generar token JWT (mismo proceso que Google OAuth)
    $permissions = json_decode($access['external_permissions'], true);
    $token = $this->generateJWT($user['id'], $projectId, $permissions);

    // Guardar sesión
    $this->saveSession($projectId, $user['id'], $token);

    return $this->json([
        'success' => true,
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['public_name'] ?? $user['username'],
                'permissions' => $permissions
            ],
            'project' => [
                'id' => $projectId
            ],
            'expires_at' => date('c', time() + 86400)
        ]
    ]);
}
```

---

### **Componente Frontend: Selector de Método**

**Archivo:** `app/auth/login/page.tsx`

```tsx
'use client';

import { useState } from 'react';
import GoogleLoginButton from '@/components/GoogleLoginButton';

export default function LoginPage() {
  const [method, setMethod] = useState<'google' | 'traditional'>('google');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  const handleTraditionalLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      const response = await fetch('/api/auth/login-traditional', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });

      const data = await response.json();

      if (data.success) {
        localStorage.setItem('auth_token', data.data.token);
        localStorage.setItem('user', JSON.stringify(data.data.user));
        window.location.href = '/dashboard';
      } else {
        setError(data.error);
      }
    } catch (err) {
      setError('Error al iniciar sesión');
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
        <h1 className="text-3xl font-bold text-center mb-8">Iniciar Sesión</h1>

        {/* Selector de método */}
        <div className="flex gap-2 mb-6">
          <button
            onClick={() => setMethod('google')}
            className={`flex-1 py-2 rounded ${
              method === 'google' ? 'bg-blue-600 text-white' : 'bg-gray-200'
            }`}
          >
            Google
          </button>
          <button
            onClick={() => setMethod('traditional')}
            className={`flex-1 py-2 rounded ${
              method === 'traditional' ? 'bg-blue-600 text-white' : 'bg-gray-200'
            }`}
          >
            Email
          </button>
        </div>

        {/* Login con Google */}
        {method === 'google' && (
          <div className="text-center">
            <GoogleLoginButton />
          </div>
        )}

        {/* Login tradicional */}
        {method === 'traditional' && (
          <form onSubmit={handleTraditionalLogin}>
            {error && (
              <div className="bg-red-50 text-red-600 p-3 rounded mb-4">
                {error}
              </div>
            )}

            <div className="mb-4">
              <label className="block text-gray-700 mb-2">Email</label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-600"
                required
              />
            </div>

            <div className="mb-6">
              <label className="block text-gray-700 mb-2">Contraseña</label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-600"
                required
              />
            </div>

            <button
              type="submit"
              className="w-full bg-blue-600 text-white py-3 rounded hover:bg-blue-700 transition"
            >
              Iniciar Sesión
            </button>

            <p className="text-center mt-4 text-gray-600">
              ¿No tienes cuenta?{' '}
              <a href="/auth/register" className="text-blue-600 hover:underline">
                Regístrate
              </a>
            </p>
          </form>
        )}
      </div>
    </div>
  );
}
```

---

## ✅ Resumen

### **1. Compatibilidad Multi-BD:**
✅ SQLite, MySQL, PostgreSQL totalmente soportados  
✅ Script de migración automático incluido  
✅ Adaptación automática por motor de BD  

### **2. Autenticación Híbrida:**
✅ Google OAuth (recomendado)  
✅ Usuario/Contraseña tradicional  
✅ Ambos generan el mismo tipo de token  
✅ Mismos permisos y funcionalidades  

**Documento creado:** 2026-01-24  
**Versión:** 1.0
