# 📋 Módulo de Gestión de Tareas - Kanban

## ✨ Características Principales

- 🎯 **Tablero Kanban Visual** con 5 estados predefinidos
- 🖱️ **Drag & Drop** para mover tareas entre columnas
- 👥 **Control de Permisos por Roles** (Admin, Editor, Dev, Cliente)
- 🔒 **Restricciones para Clientes**: No pueden mover ni eliminar tareas
- ✅ **Aprobación Automática**: Los clientes pueden aprobar y cerrar tareas
- 📊 **Historial Completo** de todos los cambios
- 🎨 **Diseño Moderno** integrado con el sistema Data2Rest
- 📱 **Responsive** y optimizado para móviles

## 🚀 Inicio Rápido

### Acceso al Módulo

```
URL: http://localhost/data2rest/admin/tasks
```

**Requisitos:**
- Usuario autenticado
- Proyecto activo seleccionado

### Estados del Kanban

1. **Solicitud** (Backlog) - Nuevas tareas pendientes
2. **En Desarrollo** (In Progress) - Tareas en progreso
3. **En Revisión** (QA/Internal Review) - Tareas en revisión interna
4. **Validación Cliente** (Waiting for Client) - Esperando aprobación del cliente
5. **Finalizado** (Done) - Tareas completadas

## 👤 Permisos por Rol

### Administrador / Editor / Desarrollador

- ✅ Crear tareas
- ✅ Mover tareas (Drag & Drop)
- ✅ Editar tareas
- ✅ Eliminar tareas (solo Admin)
- ✅ Asignar tareas a usuarios
- ✅ Ver historial completo

### Cliente

- ✅ Crear tareas
- ✅ Ver el tablero
- ❌ **NO** puede mover tareas
- ❌ **NO** puede eliminar tareas
- ✅ **ESPECIAL**: Puede aprobar tareas en "Validación Cliente"

## 🔄 Flujo de Trabajo

### Flujo Estándar (Desarrollador)

```
Solicitud → En Desarrollo → En Revisión → Validación Cliente → Finalizado
```

### Aprobación del Cliente

Cuando una tarea llega a **"Validación Cliente"**:

1. El cliente recibe la tarea para revisión
2. Puede ver todos los detalles y el historial
3. Al estar conforme, agrega un comentario
4. Marca el checkbox: _"Acepto los entregables y solicito cierre de tarea"_
5. **El sistema automáticamente mueve la tarea a "Finalizado"**

## 📝 Crear una Nueva Tarea

1. Click en el botón **"Nueva Tarea"**
2. Completar el formulario:
   - **Título** (obligatorio)
   - **Descripción** (opcional)
   - **Prioridad**: Baja, Media, Alta
   - **Asignar a**: Seleccionar usuario del proyecto
   - **Estado inicial**: Normalmente "Solicitud"
3. Click en **"Crear Tarea"**

## 🖱️ Mover Tareas (Drag & Drop)

**Solo para Admin/Editor/Dev:**

1. Click y mantener presionado sobre una tarjeta
2. Arrastrar a la columna deseada
3. Soltar para actualizar el estado
4. El cambio se guarda automáticamente

**Para Clientes:**
- El cursor mostrará ⊘ (no permitido)
- Las tarjetas no se pueden arrastrar

## 📊 Ver Historial de una Tarea

1. Click en el ícono de ojo 👁️ en la tarjeta
2. Se abre un modal con:
   - Historial completo de cambios
   - Quién hizo cada cambio y cuándo
   - Comentarios asociados
   - (Para clientes) Formulario de aprobación

## 🎨 Prioridades

Las tareas tienen 3 niveles de prioridad con colores distintivos:

- 🔴 **Alta** (High) - Rojo
- 🟡 **Media** (Medium) - Amarillo
- 🔵 **Baja** (Low) - Azul

## 🗄️ Estructura de Base de Datos

### Tablas Creadas

- **`task_statuses`**: Estados/columnas del Kanban
- **`tasks`**: Tareas del proyecto
- **`task_history`**: Historial de cambios y auditoría

Ver documentación completa en: `/docs/TASKS_MODULE.md`

## 🔐 Seguridad

- ✅ Validación de permisos en cada acción
- ✅ Registro de auditoría completo
- ✅ Protección contra acciones no autorizadas
- ✅ Validación de proyecto activo
- ✅ Solo el creador o admin puede editar tareas

## 🛠️ Endpoints API

```php
GET  /admin/tasks              // Ver tablero Kanban
POST /admin/tasks/create       // Crear tarea
POST /admin/tasks/move         // Mover tarea (Drag & Drop)
POST /admin/tasks/update       // Actualizar tarea
POST /admin/tasks/delete       // Eliminar tarea (Admin)
POST /admin/tasks/addComment   // Agregar comentario/aprobar
GET  /admin/tasks/history      // Ver historial
```

## 💡 Consejos de Uso

1. **Ordenamiento**: Las tareas mantienen su posición al recargar la página
2. **Asignación**: Asigna tareas a usuarios específicos para mejor organización
3. **Prioridades**: Usa las prioridades para destacar tareas urgentes
4. **Historial**: Revisa el historial para entender el flujo de trabajo
5. **Aprobación**: Los clientes deben aprobar explícitamente en "Validación Cliente"

## 🐛 Solución de Problemas

### "No puedo mover tareas"
- Verifica que no tengas rol de Cliente
- Solo Admin/Editor/Dev pueden mover tareas

### "No veo el botón de aprobar"
- El botón solo aparece para Clientes
- Solo en tareas que están en "Validación Cliente"

### "La tarea no se movió automáticamente"
- Verifica que marcaste el checkbox de aprobación
- Solo funciona desde el estado "Validación Cliente"

## 📚 Documentación Adicional

- **Documentación Técnica Completa**: `/docs/TASKS_MODULE.md`
- **Código del Controlador**: `/src/Modules/Tasks/TaskController.php`
- **Vista del Kanban**: `/src/Views/admin/tasks/kanban.blade.php`

## 🎯 Próximas Mejoras Sugeridas

- [ ] Agregar fechas límite (deadlines)
- [ ] Sistema de comentarios múltiples
- [ ] Adjuntar archivos a tareas
- [ ] Notificaciones por email
- [ ] Filtros avanzados
- [ ] Búsqueda de tareas
- [ ] Etiquetas/Tags
- [ ] Exportar tablero a PDF

---

**Desarrollado para Data2Rest** | Versión 1.0 | Enero 2026
