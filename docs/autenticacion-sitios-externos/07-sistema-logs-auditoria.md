# 📊 Sistema de Logs y Auditoría de Actividades

## 🎯 Estado Actual vs Necesidades

### **✅ Ya Contemplado en el Plan:**

1. **Tabla `activity_logs`** - Ya existe en Data2Rest
2. **Logs de autenticación** - Intentos de login, éxitos/fallos
3. **Tabla `project_sessions`** - Registro de sesiones activas

### **❌ Falta Agregar:**

1. **Logs de actividades en el sitio web** (ediciones, creaciones, eliminaciones)
2. **Vista de consulta fácil** por proyecto
3. **Filtros avanzados** (usuario, fecha, tipo de acción)
4. **Dashboard de actividad** en tiempo real

---

## 🏗️ Extensión del Sistema de Logs

### **1. Tabla Existente: `activity_logs`**

**Estructura actual:**
```sql
CREATE TABLE activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    project_id INTEGER,
    action TEXT,
    details TEXT,
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**✅ Ya soporta lo que necesitas**, solo hay que usarla correctamente.

---

### **2. Tipos de Eventos a Registrar**

```javascript
// Eventos de Autenticación
- external_login_success
- external_login_failed
- external_logout
- token_refresh

// Eventos de Datos (CRUD)
- record_created
- record_updated
- record_deleted
- record_viewed

// Eventos de Configuración
- permissions_changed
- role_changed
- user_activated
- user_deactivated
```

---

## 💻 Implementación en Backend

### **Endpoint: Registrar Actividad desde Sitio Web**

**Ruta:** `POST /api/v1/external/{project_id}/log-activity`

**Headers:**
```
Authorization: Bearer {token}
X-Project-ID: {project_id}
```

**Body:**
```json
{
    "action": "record_updated",
    "resource": "pets",
    "resource_id": 10,
    "details": {
        "field": "name",
        "old_value": "Firulais",
        "new_value": "Firulais Jr."
    }
}
```

**Código del Controlador:**

```php
// src/Modules/Auth/ProjectAuthController.php

/**
 * Registrar actividad desde sitio externo
 */
public function logExternalActivity()
{
    $projectId = $_SERVER['HTTP_X_PROJECT_ID'];
    $token = $this->getBearerToken();
    
    // Validar token
    $user = $this->validateJWT($token, $projectId);
    if (!$user) {
        return $this->json(['error' => 'No autorizado'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO activity_logs 
        (user_id, project_id, action, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user['user_id'],
        $projectId,
        $data['action'],
        json_encode([
            'resource' => $data['resource'],
            'resource_id' => $data['resource_id'],
            'details' => $data['details']
        ]),
        $_SERVER['REMOTE_ADDR'],
        date('Y-m-d H:i:s')
    ]);
    
    return $this->json(['success' => true]);
}
```

---

### **Helper: Clase de Logging**

```php
// src/Core/ActivityLogger.php

namespace App\Core;

class ActivityLogger
{
    /**
     * Log de actividad externa
     */
    public static function logExternal($userId, $projectId, $action, $resource, $resourceId, $details = [])
    {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs 
            (user_id, project_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $projectId,
            $action,
            json_encode([
                'resource' => $resource,
                'resource_id' => $resourceId,
                'details' => $details
            ]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Log de autenticación
     */
    public static function logAuth($userId, $projectId, $action, $success, $reason = null)
    {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs 
            (user_id, project_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $projectId,
            $action,
            json_encode([
                'success' => $success,
                'reason' => $reason
            ]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            date('Y-m-d H:i:s')
        ]);
    }
}
```

**Uso en controladores:**

```php
// Al hacer login exitoso
ActivityLogger::logAuth($userId, $projectId, 'external_login_success', true);

// Al editar un registro
ActivityLogger::logExternal($userId, $projectId, 'record_updated', 'pets', 10, [
    'field' => 'name',
    'old_value' => 'Firulais',
    'new_value' => 'Firulais Jr.'
]);
```

---

## 🌐 Implementación en Frontend

### **Cliente API con Logging Automático**

```typescript
// lib/api-client.ts

export class ApiClient {
  private baseUrl: string;
  private projectId: string;
  private token: string | null;

  async update<T>(table: string, id: number, data: any, oldData?: any): Promise<T> {
    const response = await fetch(
      `${this.baseUrl}/api/v1/external/${this.projectId}/${table}/${id}`,
      {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'X-Project-ID': this.projectId,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      }
    );

    if (!response.ok) {
      throw new Error('Error al actualizar');
    }

    const result = await response.json();

    // Registrar actividad automáticamente
    await this.logActivity('record_updated', table, id, {
      changes: this.getChanges(oldData, data)
    });

    return result.data;
  }

  private async logActivity(action: string, resource: string, resourceId: number, details: any) {
    try {
      await fetch(
        `${this.baseUrl}/api/v1/external/${this.projectId}/log-activity`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${this.token}`,
            'X-Project-ID': this.projectId,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action,
            resource,
            resource_id: resourceId,
            details
          })
        }
      );
    } catch (error) {
      // No fallar si el log falla
      console.error('Error logging activity:', error);
    }
  }

  private getChanges(oldData: any, newData: any): any[] {
    const changes = [];
    for (const key in newData) {
      if (oldData[key] !== newData[key]) {
        changes.push({
          field: key,
          old_value: oldData[key],
          new_value: newData[key]
        });
      }
    }
    return changes;
  }
}
```

**Uso en componentes:**

```tsx
// El logging es automático
const api = new ApiClient();

// Al editar mascota
await api.update('pets', 10, {
  name: 'Firulais Jr.',
  age: 3
}, {
  name: 'Firulais',
  age: 2
});

// Automáticamente se registra:
// - Acción: record_updated
// - Recurso: pets
// - ID: 10
// - Cambios: name (Firulais → Firulais Jr.), age (2 → 3)
```

---

## 📊 Vista de Consulta en Data2Rest

### **Nueva Vista: Logs de Actividad del Proyecto**

**Ruta:** `Proyectos → [Clínica Veterinaria] → Logs de Actividad`

**Wireframe:**

```
┌────────────────────────────────────────────────────────────┐
│ Proyecto: Clínica Veterinaria                              │
│ Pestañas: [General] [BD] [API] [Usuarios Web] [Logs] ←    │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ 📊 Logs de Actividad                                      │
│                                                            │
│ Filtros:                                                   │
│ [Usuario: Todos ▼] [Acción: Todas ▼] [Desde: __/__/__]   │
│ [Recurso: Todos ▼] [Buscar: ___________] [Filtrar]       │
│                                                            │
│ ┌────────────────────────────────────────────────────┐    │
│ │ Fecha/Hora    │ Usuario    │ Acción        │ Detalles│  │
│ ├────────────────────────────────────────────────────┤    │
│ │ 2026-01-24    │ María      │ record_updated│ Editó   │  │
│ │ 22:30:15      │ Pérez      │ (pets #10)    │ nombre  │  │
│ │               │            │               │ [Ver +] │  │
│ ├────────────────────────────────────────────────────┤    │
│ │ 2026-01-24    │ Dr. Juan   │ record_created│ Creó    │  │
│ │ 22:15:42      │            │ (appointments)│ cita    │  │
│ │               │            │               │ [Ver +] │  │
│ ├────────────────────────────────────────────────────┤    │
│ │ 2026-01-24    │ María      │ external_login│ Login   │  │
│ │ 22:10:05      │ Pérez      │ _success      │ exitoso │  │
│ ├────────────────────────────────────────────────────┤    │
│ │ 2026-01-24    │ Pedro      │ external_login│ Login   │  │
│ │ 21:45:12      │ Gómez      │ _failed       │ fallido │  │
│ │               │            │               │ [Ver +] │  │
│ └────────────────────────────────────────────────────┘    │
│                                                            │
│ [← Anterior] Página 1 de 15 [Siguiente →]                 │
│                                                            │
│ [Exportar CSV] [Exportar JSON]                            │
└────────────────────────────────────────────────────────────┘
```

---

### **Modal: Detalles del Log**

```
┌────────────────────────────────────────────────────────┐
│ Detalles de Actividad                                  │
├────────────────────────────────────────────────────────┤
│                                                        │
│ 📅 Fecha: 2026-01-24 22:30:15                         │
│ 👤 Usuario: María Pérez (maria@gmail.com)             │
│ 🌐 IP: 192.168.1.100                                  │
│ 🔧 Acción: record_updated                             │
│                                                        │
│ Recurso: pets (ID: 10)                                │
│                                                        │
│ Cambios realizados:                                    │
│ ┌────────────────────────────────────────────────┐    │
│ │ Campo  │ Valor Anterior │ Valor Nuevo         │    │
│ ├────────────────────────────────────────────────┤    │
│ │ name   │ Firulais       │ Firulais Jr.        │    │
│ │ age    │ 2              │ 3                   │    │
│ └────────────────────────────────────────────────┘    │
│                                                        │
│ [Cerrar]                                               │
└────────────────────────────────────────────────────────┘
```

---

### **Código del Controlador**

```php
// src/Modules/Projects/ProjectLogsController.php

namespace App\Modules\Projects;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Auth;

class ProjectLogsController extends BaseController
{
    /**
     * Vista de logs del proyecto
     */
    public function index($projectId)
    {
        if (!Auth::isAdmin()) {
            return $this->redirect('admin/dashboard');
        }

        $db = Database::getInstance()->getConnection();
        
        // Filtros
        $userId = $_GET['user_id'] ?? null;
        $action = $_GET['action'] ?? null;
        $resource = $_GET['resource'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $search = $_GET['search'] ?? null;
        
        // Query base
        $sql = "
            SELECT al.*, u.username, u.email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.project_id = ?
        ";
        
        $params = [$projectId];
        
        // Aplicar filtros
        if ($userId) {
            $sql .= " AND al.user_id = ?";
            $params[] = $userId;
        }
        
        if ($action) {
            $sql .= " AND al.action = ?";
            $params[] = $action;
        }
        
        if ($dateFrom) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($search) {
            $sql .= " AND (al.details LIKE ? OR al.action LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT 50";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        // Obtener usuarios únicos para filtro
        $users = $db->query("
            SELECT DISTINCT u.id, u.username
            FROM users u
            JOIN activity_logs al ON u.id = al.user_id
            WHERE al.project_id = $projectId
            ORDER BY u.username
        ")->fetchAll();
        
        // Obtener acciones únicas
        $actions = $db->query("
            SELECT DISTINCT action
            FROM activity_logs
            WHERE project_id = $projectId
            ORDER BY action
        ")->fetchAll();
        
        return $this->view('admin/projects/logs', [
            'project' => $this->getProject($projectId),
            'logs' => $logs,
            'users' => $users,
            'actions' => $actions
        ]);
    }
    
    /**
     * Exportar logs a CSV
     */
    public function exportCsv($projectId)
    {
        // Similar a index() pero retorna CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="logs-proyecto-' . $projectId . '.csv"');
        
        // ... generar CSV
    }
}
```

---

## 📈 Dashboard de Métricas

### **Vista de Resumen**

```
┌────────────────────────────────────────────────────────┐
│ 📊 Resumen de Actividad - Últimos 30 días             │
├────────────────────────────────────────────────────────┤
│                                                        │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐   │
│ │ 1,234        │ │ 45           │ │ 98.5%        │   │
│ │ Logins       │ │ Usuarios     │ │ Tasa Éxito   │   │
│ │ Totales      │ │ Activos      │ │ Login        │   │
│ └──────────────┘ └──────────────┘ └──────────────┘   │
│                                                        │
│ Actividad por Tipo:                                   │
│ ┌────────────────────────────────────────────────┐    │
│ │ ████████████ Logins (45%)                      │    │
│ │ ████████ Ediciones (30%)                       │    │
│ │ █████ Creaciones (20%)                         │    │
│ │ ██ Eliminaciones (5%)                          │    │
│ └────────────────────────────────────────────────┘    │
│                                                        │
│ Usuarios Más Activos:                                 │
│ 1. Dr. Juan - 156 acciones                           │
│ 2. María Pérez - 89 acciones                         │
│ 3. Pedro Gómez - 45 acciones                         │
└────────────────────────────────────────────────────────┘
```

---

## ✅ Resumen: Qué Agregar

### **Backend:**
1. ✅ Endpoint `/api/v1/external/{project_id}/log-activity`
2. ✅ Clase `ActivityLogger` helper
3. ✅ Controlador `ProjectLogsController`
4. ✅ Vistas de consulta y filtrado

### **Frontend:**
1. ✅ Logging automático en `ApiClient`
2. ✅ Tracking de cambios en ediciones
3. ✅ Logs de navegación (opcional)

### **Panel Data2Rest:**
1. ✅ Nueva pestaña "Logs" en proyectos
2. ✅ Filtros avanzados
3. ✅ Exportación CSV/JSON
4. ✅ Dashboard de métricas

---

**Documento creado:** 2026-01-24  
**Versión:** 1.0
