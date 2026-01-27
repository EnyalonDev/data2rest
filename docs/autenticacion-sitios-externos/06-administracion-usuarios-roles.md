# 👥 Administración de Usuarios y Roles - Panel Data2Rest

## 🎯 Flujo de Administración

### **Separación de Roles:**

```
ROLES INTERNOS (Data2Rest):
├── Administrador (role_id: 1)
├── Gestor de Proyectos (role_id: 2)
├── Editor (role_id: 3)
└── Usuario (role_id: 4)

ROLES EXTERNOS (Sitio Web):
├── admin (acceso total al sitio)
├── staff (gestión operativa)
└── client (usuario final)
```

**Importante:** Son sistemas de roles **independientes**.

---

## 📋 Vista: Gestión de Usuarios del Proyecto

### **Ruta en Data2Rest:**
```
Proyectos → [Clínica Veterinaria] → Usuarios del Sitio Web
```

### **Wireframe de la Vista:**

```
┌────────────────────────────────────────────────────────────────┐
│ Proyecto: Clínica Veterinaria                                  │
│ Pestañas: [General] [Bases de Datos] [API] [Usuarios Web] ←   │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ 👥 Usuarios del Sitio Web                                     │
│                                                                │
│ [🔍 Buscar usuario]  [+ Agregar Usuario Existente]            │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐  │
│ │ Pendientes de Aprobación (2)                             │  │
│ ├──────────────────────────────────────────────────────────┤  │
│ │ Usuario         │ Email              │ Acciones          │  │
│ │─────────────────┼────────────────────┼──────────────────│  │
│ │ Carlos López    │ carlos@gmail.com   │ [✓ Aprobar]      │  │
│ │ Ana Martínez    │ ana@hotmail.com    │ [✓ Aprobar]      │  │
│ └──────────────────────────────────────────────────────────┘  │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐  │
│ │ Usuarios Activos (5)                                     │  │
│ ├──────────────────────────────────────────────────────────┤  │
│ │ Usuario      │ Email           │ Rol    │ Acciones      │  │
│ │──────────────┼─────────────────┼────────┼───────────────│  │
│ │ María Pérez  │ maria@gmail.com │ client │ [⚙️ Config]   │  │
│ │ Dr. Juan     │ juan@vet.com    │ staff  │ [⚙️ Config]   │  │
│ │ Pedro Gómez  │ pedro@gmail.com │ client │ [⚙️ Config]   │  │
│ │ Dra. Laura   │ laura@vet.com   │ admin  │ [⚙️ Config]   │  │
│ │ Luis Torres  │ luis@gmail.com  │ client │ [⚙️ Config]   │  │
│ └──────────────────────────────────────────────────────────┘  │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

---

## ⚙️ Modal: Configurar Permisos de Usuario

### **Al hacer clic en "⚙️ Config" o "✓ Aprobar":**

```
┌────────────────────────────────────────────────────────────┐
│ Configurar Acceso Web - María Pérez                       │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ 📧 Email: maria@gmail.com                                 │
│ 🆔 ID Usuario: 5                                          │
│                                                            │
│ ┌────────────────────────────────────────────────────┐    │
│ │ ACCESO AL SITIO WEB                                │    │
│ ├────────────────────────────────────────────────────┤    │
│ │                                                    │    │
│ │ ☑ Habilitar acceso al sitio web                   │    │
│ │                                                    │    │
│ │ Rol en el Sitio:                                   │    │
│ │ ○ Administrador (acceso total)                     │    │
│ │ ○ Staff (gestión operativa)                        │    │
│ │ ● Cliente (usuario final)                          │    │
│ │                                                    │    │
│ └────────────────────────────────────────────────────┘    │
│                                                            │
│ ┌────────────────────────────────────────────────────┐    │
│ │ PÁGINAS PERMITIDAS                                 │    │
│ ├────────────────────────────────────────────────────┤    │
│ │ ☑ Dashboard                                        │    │
│ │ ☑ Mis Mascotas                                     │    │
│ │ ☑ Mis Citas                                        │    │
│ │ ☐ Todas las Mascotas (solo staff/admin)           │    │
│ │ ☐ Todas las Citas (solo staff/admin)              │    │
│ │ ☐ Reportes (solo admin)                            │    │
│ │ ☐ Configuración (solo admin)                       │    │
│ └────────────────────────────────────────────────────┘    │
│                                                            │
│ ┌────────────────────────────────────────────────────┐    │
│ │ ALCANCE DE DATOS                                   │    │
│ ├────────────────────────────────────────────────────┤    │
│ │ ● Solo sus propios datos                           │    │
│ │ ○ Todos los datos (staff/admin)                    │    │
│ └────────────────────────────────────────────────────┘    │
│                                                            │
│ ┌────────────────────────────────────────────────────┐    │
│ │ PERMISOS POR RECURSO                               │    │
│ ├────────────────────────────────────────────────────┤    │
│ │ Mascotas:                                          │    │
│ │ ☑ Ver  ☐ Crear  ☐ Editar  ☐ Eliminar             │    │
│ │                                                    │    │
│ │ Citas:                                             │    │
│ │ ☑ Ver  ☑ Crear  ☑ Cancelar  ☐ Editar  ☐ Eliminar │    │
│ │                                                    │    │
│ │ Historias Clínicas:                                │    │
│ │ ☑ Ver  ☐ Crear  ☐ Editar  ☐ Eliminar             │    │
│ └────────────────────────────────────────────────────┘    │
│                                                            │
│ [Cancelar]                            [Guardar Cambios]   │
└────────────────────────────────────────────────────────────┘
```

---

## 🔄 Flujo Paso a Paso: Asignar Rol Administrativo

### **Escenario: Convertir a Dr. Juan en Admin del Sitio**

```
1. Administrador entra a Data2Rest
   └─> Proyectos → Clínica Veterinaria → Usuarios del Sitio Web

2. Ve lista de usuarios activos:
   ┌──────────────────────────────────────┐
   │ Dr. Juan │ juan@vet.com │ staff     │
   └──────────────────────────────────────┘

3. Clic en [⚙️ Config] de Dr. Juan

4. Modal se abre con configuración actual:
   - Rol: ○ Admin  ● Staff  ○ Cliente
   - Páginas: [✓] Dashboard, [✓] Todas las Citas
   - Alcance: ○ Solo sus datos  ● Todos los datos

5. Administrador cambia a:
   - Rol: ● Admin  ○ Staff  ○ Cliente
   - Páginas: [✓] Todas (automático)
   - Alcance: ● Todos los datos (automático)
   - Permisos: [✓] Todos los recursos CRUD

6. Clic en [Guardar Cambios]

7. Data2Rest ejecuta:
   UPDATE project_users SET
     external_permissions = '{
       "role": "admin",
       "pages": ["*"],
       "data_access": {"scope": "all"},
       "actions": {"*": ["read","create","update","delete"]}
     }'
   WHERE project_id = 1 AND user_id = 8;

8. Dr. Juan ahora es Admin del sitio web
   (pero NO admin de Data2Rest)
```

---

## ➕ Agregar Usuario Existente al Proyecto

### **Botón: [+ Agregar Usuario Existente]**

```
┌────────────────────────────────────────────────────────────┐
│ Agregar Usuario al Proyecto                               │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ Buscar usuario registrado en Data2Rest:                   │
│                                                            │
│ [🔍 Buscar por email o nombre_______________] [Buscar]    │
│                                                            │
│ Resultados:                                                │
│ ┌────────────────────────────────────────────────────┐    │
│ │ ○ Pedro Gómez (pedro@gmail.com)                    │    │
│ │ ○ Ana Martínez (ana@hotmail.com)                   │    │
│ │ ○ Luis Torres (luis@gmail.com)                     │    │
│ └────────────────────────────────────────────────────┘    │
│                                                            │
│ [Cancelar]                                  [Agregar]      │
└────────────────────────────────────────────────────────────┘

Después de [Agregar]:
  ↓
Se abre modal de configuración de permisos
  ↓
Administrador configura rol y permisos
  ↓
Usuario agregado al proyecto
```

---

## 💻 Código del Controlador

### **Archivo:** `src/Modules/Projects/ProjectUsersController.php`

```php
<?php

namespace App\Modules\Projects;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Auth;

class ProjectUsersController extends BaseController
{
    /**
     * Lista usuarios del proyecto con acceso externo
     */
    public function listExternalUsers($projectId)
    {
        if (!Auth::isAdmin()) {
            return $this->redirect('admin/dashboard');
        }

        $db = Database::getInstance()->getConnection();

        // Usuarios activos
        $stmtActive = $db->prepare("
            SELECT u.id, u.username, u.email, pu.external_permissions, pu.external_access_enabled
            FROM users u
            JOIN project_users pu ON u.id = pu.user_id
            WHERE pu.project_id = ? AND pu.external_access_enabled = 1
            ORDER BY u.username
        ");
        $stmtActive->execute([$projectId]);
        $activeUsers = $stmtActive->fetchAll();

        // Usuarios pendientes
        $stmtPending = $db->prepare("
            SELECT u.id, u.username, u.email
            FROM users u
            JOIN project_users pu ON u.id = pu.user_id
            WHERE pu.project_id = ? AND pu.external_access_enabled = 0
            ORDER BY pu.assigned_at DESC
        ");
        $stmtPending->execute([$projectId]);
        $pendingUsers = $stmtPending->fetchAll();

        // Obtener info del proyecto
        $project = $db->query("SELECT * FROM projects WHERE id = $projectId")->fetch();

        return $this->view('admin/projects/external_users', [
            'project' => $project,
            'activeUsers' => $activeUsers,
            'pendingUsers' => $pendingUsers
        ]);
    }

    /**
     * Actualizar permisos externos de un usuario
     */
    public function updateExternalPermissions()
    {
        if (!Auth::isAdmin()) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $projectId = $data['project_id'];
        $userId = $data['user_id'];
        $enabled = $data['enabled'] ?? 1;
        $role = $data['role'] ?? 'client';
        $pages = $data['pages'] ?? [];
        $dataAccess = $data['data_access'] ?? 'own';
        $actions = $data['actions'] ?? [];

        // Construir JSON de permisos
        $permissions = [
            'role' => $role,
            'pages' => $pages,
            'data_access' => [
                'scope' => $dataAccess,
                'filters' => $this->buildFilters($dataAccess, $userId)
            ],
            'actions' => $actions
        ];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE project_users SET
                external_permissions = ?,
                external_access_enabled = ?
            WHERE project_id = ? AND user_id = ?
        ");
        $stmt->execute([
            json_encode($permissions),
            $enabled,
            $projectId,
            $userId
        ]);

        // Activar usuario si estaba inactivo
        if ($enabled) {
            $db->prepare("UPDATE users SET status = 1 WHERE id = ?")->execute([$userId]);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Construir filtros según alcance de datos
     */
    private function buildFilters($scope, $userId)
    {
        if ($scope === 'all') {
            return [];
        }

        // Filtros para scope "own"
        return [
            'pets' => "owner_id = $userId",
            'appointments' => "client_id = $userId",
            'medical_records' => "pet.owner_id = $userId"
        ];
    }

    /**
     * Buscar usuarios para agregar al proyecto
     */
    public function searchUsers()
    {
        $query = $_GET['q'] ?? '';
        $projectId = $_GET['project_id'] ?? 0;

        $db = Database::getInstance()->getConnection();
        
        // Buscar usuarios que NO estén en el proyecto
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.email
            FROM users u
            WHERE (u.email LIKE ? OR u.username LIKE ?)
              AND u.id NOT IN (
                SELECT user_id FROM project_users WHERE project_id = ?
              )
            LIMIT 10
        ");
        $stmt->execute(["%$query%", "%$query%", $projectId]);
        $users = $stmt->fetchAll();

        return $this->json(['users' => $users]);
    }

    /**
     * Agregar usuario existente al proyecto
     */
    public function addUserToProject()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $projectId = $data['project_id'];
        $userId = $data['user_id'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO project_users (project_id, user_id, external_access_enabled, assigned_at)
            VALUES (?, ?, 0, ?)
        ");
        $stmt->execute([$projectId, $userId, date('Y-m-d H:i:s')]);

        return $this->json(['success' => true]);
    }
}
```

---

## 🎨 Vista Blade

### **Archivo:** `src/Views/admin/projects/external_users.blade.php`

```php
@extends('layouts.main')

@section('content')
<div class="container mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">{{ $project['name'] }}</h1>
        <p class="text-gray-600">Gestión de usuarios del sitio web</p>
    </div>

    <!-- Pestañas -->
    <div class="border-b mb-6">
        <nav class="flex gap-4">
            <a href="/admin/projects/{{ $project['id'] }}" class="px-4 py-2">General</a>
            <a href="/admin/projects/{{ $project['id'] }}/databases" class="px-4 py-2">Bases de Datos</a>
            <a href="/admin/projects/{{ $project['id'] }}/api" class="px-4 py-2">API</a>
            <a href="/admin/projects/{{ $project['id'] }}/external-users" class="px-4 py-2 border-b-2 border-blue-600 font-semibold">Usuarios Web</a>
        </nav>
    </div>

    <!-- Botón agregar usuario -->
    <div class="mb-6">
        <button onclick="openAddUserModal()" class="bg-blue-600 text-white px-4 py-2 rounded">
            + Agregar Usuario Existente
        </button>
    </div>

    <!-- Usuarios pendientes -->
    @if(count($pendingUsers) > 0)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <h2 class="text-xl font-semibold mb-4">⏳ Pendientes de Aprobación ({{ count($pendingUsers) }})</h2>
        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="text-left py-2">Usuario</th>
                    <th class="text-left py-2">Email</th>
                    <th class="text-left py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingUsers as $user)
                <tr class="border-b">
                    <td class="py-2">{{ $user['username'] }}</td>
                    <td class="py-2">{{ $user['email'] }}</td>
                    <td class="py-2">
                        <button onclick="openConfigModal({{ $user['id'] }}, true)" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                            ✓ Aprobar
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Usuarios activos -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <h2 class="text-xl font-semibold">👥 Usuarios Activos ({{ count($activeUsers) }})</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-3">Usuario</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Rol</th>
                    <th class="text-left px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activeUsers as $user)
                <?php
                    $perms = json_decode($user['external_permissions'], true);
                    $role = $perms['role'] ?? 'client';
                    $roleLabel = [
                        'admin' => '👑 Admin',
                        'staff' => '👨‍⚕️ Staff',
                        'client' => '👤 Cliente'
                    ][$role] ?? 'Cliente';
                ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $user['username'] }}</td>
                    <td class="px-4 py-3">{{ $user['email'] }}</td>
                    <td class="px-4 py-3">{{ $roleLabel }}</td>
                    <td class="px-4 py-3">
                        <button onclick="openConfigModal({{ $user['id'] }})" class="text-blue-600 hover:underline">
                            ⚙️ Configurar
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de configuración (JavaScript) -->
<script>
function openConfigModal(userId, isApproval = false) {
    // Abrir modal con configuración del usuario
    // Implementación con fetch y modal dinámico
}
</script>
@endsection
```

---

## ✅ Resumen

### **Pasos para Asignar Roles Administrativos:**

1. **Ir a:** Proyectos → [Proyecto] → Usuarios del Sitio Web
2. **Ver:** Lista de usuarios activos y pendientes
3. **Clic en:** [⚙️ Config] del usuario
4. **Cambiar:** Rol de "Cliente" a "Staff" o "Admin"
5. **Configurar:** Páginas y permisos automáticamente
6. **Guardar:** Cambios se aplican inmediatamente

### **Roles Disponibles:**

| Rol | Acceso | Datos | Permisos |
|-----|--------|-------|----------|
| **Admin** | Todas las páginas | Todos los datos | CRUD completo |
| **Staff** | Páginas operativas | Todos los datos | CRUD limitado |
| **Client** | Páginas básicas | Solo sus datos | Solo lectura |

**Documento creado:** 2026-01-24  
**Versión:** 1.0
