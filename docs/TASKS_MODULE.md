# Módulo de Gestión de Tareas - Kanban Board

## Descripción General

Se ha implementado un módulo completo de gestión de tareas con interfaz visual tipo Kanban que soporta Drag & Drop, con control de permisos por roles y automatización especial para clientes.

## 1. Modelo de Datos

### Tablas Creadas

#### `task_statuses`
Almacena los estados/columnas del tablero Kanban:
- `id`: INTEGER PRIMARY KEY AUTOINCREMENT
- `name`: TEXT NOT NULL (nombre del estado)
- `slug`: TEXT UNIQUE NOT NULL (identificador único)
- `color`: TEXT DEFAULT '#6366f1' (color hex para la UI)
- `position`: INTEGER DEFAULT 0 (orden de visualización)
- `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

**Estados predefinidos:**
1. **Solicitud** (Backlog) - Color: #94a3b8
2. **En Desarrollo** (In Progress) - Color: #3b82f6
3. **En Revisión** (QA/Internal Review) - Color: #f59e0b
4. **Validación Cliente** (Waiting for Client) - Color: #8b5cf6
5. **Finalizado** (Done) - Color: #10b981

#### `tasks`
Almacena las tareas del proyecto:
- `id`: INTEGER PRIMARY KEY AUTOINCREMENT
- `project_id`: INTEGER NOT NULL (FK a projects)
- `title`: TEXT NOT NULL
- `description`: TEXT
- `priority`: TEXT DEFAULT 'medium' (low, medium, high)
- `status_id`: INTEGER NOT NULL (FK a task_statuses)
- `assigned_to`: INTEGER (FK a users)
- `created_by`: INTEGER NOT NULL (FK a users)
- `position`: INTEGER DEFAULT 0 (orden dentro de la columna)
- `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

#### `task_history`
Registra todos los cambios y movimientos de tareas:
- `id`: INTEGER PRIMARY KEY AUTOINCREMENT
- `task_id`: INTEGER NOT NULL (FK a tasks)
- `user_id`: INTEGER NOT NULL (FK a users)
- `action`: TEXT NOT NULL (created, moved, updated, deleted, commented, approved)
- `old_status_id`: INTEGER (FK a task_statuses)
- `new_status_id`: INTEGER (FK a task_statuses)
- `comment`: TEXT
- `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

## 2. Lógica de Roles y Permisos

### Permisos por Rol

#### Administrador / Editor / Dev
- ✅ Pueden crear tareas
- ✅ Pueden mover tarjetas entre columnas (Drag & Drop habilitado)
- ✅ Pueden editar tareas
- ✅ Pueden eliminar tareas (solo Admin)
- ✅ Pueden asignar tareas a usuarios
- ✅ Pueden ver el historial completo

#### Cliente
- ✅ Puede crear tareas
- ✅ Puede ver el tablero completo
- ❌ **NO puede mover tarjetas** (Drag & Drop bloqueado)
- ❌ **NO puede eliminar tareas**
- ✅ **Acción Especial**: En el estado "Validación Cliente", puede aprobar y finalizar tareas

### Automatización para Clientes

Cuando una tarea está en estado **"Validación Cliente"**, el cliente puede:

1. Ver un formulario especial de aprobación
2. Agregar un comentario obligatorio
3. Marcar un checkbox: "Acepto los entregables y solicito cierre de tarea"
4. Al guardar, el sistema **automáticamente mueve la tarea al estado "Finalizado"**
5. Se registra la acción en el historial como "approved"

## 3. Implementación Técnica

### Backend (PHP)

**Archivo**: `/src/Modules/Tasks/TaskController.php`

**Métodos principales:**
- `index()`: Muestra el tablero Kanban con todas las tareas
- `create()`: Crea una nueva tarea
- `move()`: Actualiza el estado y posición de una tarea (Drag & Drop)
- `update()`: Actualiza los detalles de una tarea
- `delete()`: Elimina una tarea (solo Admin)
- `addComment()`: Agrega comentarios y procesa aprobaciones de clientes
- `history()`: Obtiene el historial de cambios de una tarea

**Validaciones de seguridad:**
- Verificación de permisos en cada endpoint
- Validación de propiedad del proyecto activo
- Control de roles para operaciones sensibles
- Registro de todas las acciones en `task_history`

### Frontend (Blade + JavaScript)

**Archivo**: `/src/Views/admin/tasks/kanban.blade.php`

**Características:**
- Diseño responsive con columnas horizontales scrolleables
- Drag & Drop nativo con JavaScript (sin librerías externas)
- Feedback visual según permisos del usuario
- Modales para crear y ver tareas
- Indicadores de prioridad con colores
- Avatares de usuarios asignados
- Contador de tareas por columna
- Historial de cambios en tiempo real

**Restricciones visuales:**
- Cursor "no permitido" (⊘) para clientes al intentar arrastrar
- Tarjetas no draggables para rol Cliente
- Botón especial de aprobación solo visible para clientes en "Validación Cliente"
- Botón de eliminar solo visible para Administradores

### Rutas

**Archivo**: `/public/index.php`

```php
// --- Module: Task Management (Kanban Board) ---
$router->add('GET', '/admin/tasks', 'Tasks\\TaskController@index');
$router->add('POST', '/admin/tasks/create', 'Tasks\\TaskController@create');
$router->add('POST', '/admin/tasks/move', 'Tasks\\TaskController@move');
$router->add('POST', '/admin/tasks/update', 'Tasks\\TaskController@update');
$router->add('POST', '/admin/tasks/delete', 'Tasks\\TaskController@delete');
$router->add('POST', '/admin/tasks/addComment', 'Tasks\\TaskController@addComment');
$router->add('GET', '/admin/tasks/history', 'Tasks\\TaskController@history');
```

## 4. Ordenamiento y Posicionamiento

El campo `position` en la tabla `tasks` asegura que:
- Cuando se mueve una tarea dentro de la misma columna, se actualiza su posición
- Al recargar la página, las tareas mantienen el orden correcto
- Las tareas se ordenan por `position ASC, created_at DESC`

## 5. Flujo de Trabajo Típico

### Para Desarrolladores/Administradores:
1. Crear tarea en "Solicitud" (Backlog)
2. Arrastrar a "En Desarrollo" cuando se comienza
3. Mover a "En Revisión" cuando está lista para QA
4. Mover a "Validación Cliente" cuando está lista para aprobación
5. Cliente aprueba → Automáticamente va a "Finalizado"

### Para Clientes:
1. Crear tarea en "Solicitud" (nueva solicitud)
2. Ver el progreso de sus tareas en el tablero
3. Cuando una tarea llega a "Validación Cliente":
   - Ver detalles y historial
   - Agregar comentario de aprobación
   - Marcar checkbox de aceptación
   - Sistema mueve automáticamente a "Finalizado"

## 6. Características de Seguridad

- ✅ Validación de proyecto activo en sesión
- ✅ Verificación de permisos por rol en cada acción
- ✅ Registro completo de auditoría en `task_history`
- ✅ Protección contra movimientos no autorizados
- ✅ Validación de propiedad de tareas para edición
- ✅ Solo administradores pueden eliminar tareas

## 7. Acceso al Módulo

**URL**: `http://localhost/data2rest/admin/tasks`

**Requisitos**:
- Usuario autenticado
- Proyecto activo seleccionado
- Cualquier rol puede acceder (permisos se aplican dentro del módulo)

## 8. Próximos Pasos Sugeridos

1. **Agregar al menú lateral**: Incluir un enlace en el sidebar principal
2. **Notificaciones**: Enviar notificaciones cuando una tarea cambia de estado
3. **Filtros**: Agregar filtros por prioridad, usuario asignado, etc.
4. **Búsqueda**: Implementar búsqueda de tareas por título/descripción
5. **Fechas límite**: Agregar campo `due_date` para deadlines
6. **Adjuntos**: Permitir adjuntar archivos a las tareas
7. **Comentarios múltiples**: Sistema de comentarios completo (no solo en aprobación)
8. **Etiquetas**: Sistema de tags/labels para categorizar tareas

## 9. Notas de Implementación

- El módulo se integra perfectamente con el sistema de proyectos existente
- Usa el layout principal del sistema (`@extends('layouts.main')`)
- Compatible con el sistema de temas (dark/light mode)
- Responsive y optimizado para móviles
- Sin dependencias externas para Drag & Drop
- Código limpio y bien documentado

## 10. Testing

Para probar el módulo:

1. Acceder como **Administrador**:
   - Crear varias tareas
   - Mover tareas entre columnas
   - Verificar que el orden se mantiene al recargar

2. Acceder como **Cliente**:
   - Intentar arrastrar una tarea (debe estar bloqueado)
   - Crear una nueva tarea
   - Cuando una tarea esté en "Validación Cliente", aprobarla
   - Verificar que se mueve automáticamente a "Finalizado"

3. Verificar el **historial**:
   - Cada acción debe quedar registrada
   - Los comentarios deben aparecer correctamente
   - Las fechas y usuarios deben ser precisos
