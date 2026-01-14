# Módulo de Administración de Base de Datos del Sistema

## Descripción

El módulo de **Administración de Base de Datos del Sistema** es una herramienta exclusiva para Super Administradores que proporciona control total sobre la base de datos del sistema (`system.sqlite`). Este módulo incluye funcionalidades avanzadas de gestión, backups, ejecución de consultas SQL, optimización y registro completo de todas las operaciones.

## 🔒 Seguridad

**IMPORTANTE**: Este módulo está protegido con `Auth::requireAdmin()` en todas sus rutas. Solo los usuarios con permisos `all: true` (Super Admin) pueden acceder a cualquier funcionalidad del módulo.

### Verificación de Permisos

```php
public function __construct()
{
    Auth::requireLogin();
    Auth::requireAdmin(); // CRITICAL: Only Super Admin can access
}
```

## 🎯 Funcionalidades Principales

### 1. Dashboard del Sistema

**Ruta**: `/admin/system-database`

Proporciona una vista general de la base de datos del sistema:
- Tamaño total de la base de datos
- Número de tablas del sistema
- Total de registros
- Uso de espacio en disco
- Información del último backup
- Accesos rápidos a funcionalidades principales

### 2. Visualización de Tablas

**Ruta**: `/admin/system-database/tables`

Lista todas las tablas del sistema con:
- Nombre de la tabla
- Número de registros
- Tamaño aproximado
- Enlace a detalles de estructura

**Detalles de Tabla** (`/admin/system-database/table-details?table=nombre_tabla`):
- Estructura completa (columnas, tipos, constraints)
- Índices de la tabla
- Datos de muestra (primeros 10 registros)

### 3. Ejecutor SQL

**Ruta**: `/admin/system-database/query-executor`

Permite ejecutar consultas SQL directamente en la base de datos del sistema:

**Características**:
- Editor de código SQL
- Validación de consultas peligrosas (DROP, TRUNCATE)
- Confirmación adicional para operaciones destructivas
- Visualización de resultados en tabla
- Registro automático de todas las consultas ejecutadas

**Ejemplo de uso**:
```sql
SELECT * FROM users WHERE role_id = 1;
SELECT COUNT(*) FROM logs WHERE created_at > '2026-01-01';
```

### 4. Sistema de Backups

**Ruta**: `/admin/system-database/backups`

Gestión completa de copias de seguridad:

#### Backups Manuales
- Crear backup con un clic
- Descargar backups existentes
- Restaurar desde backup (con backup de seguridad automático)
- Eliminar backups antiguos

#### Backups Automáticos
Script de backup automático ubicado en: `/scripts/auto_backup_system.php`

**Configuración de Cron Job**:
```bash
# Backup diario a las 2 AM
0 2 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/auto_backup_system.php
```

**Características del script**:
- Crea backups con nomenclatura `system_auto_YYYY-MM-DD_HH-MM-SS.sqlite`
- Mantiene los últimos 30 backups automáticos
- Elimina backups antiguos automáticamente
- Registra todas las operaciones en logs

#### Ubicación de Backups
```
/data/backups/system/
├── system_manual_2026-01-13_19-30-00.sqlite
├── system_auto_2026-01-13_02-00-00.sqlite
└── system_before_restore_2026-01-13_15-45-00.sqlite
```

### 5. Optimización de Base de Datos

**Ruta**: `POST /admin/system-database/optimize`

Ejecuta operaciones de optimización:
- `VACUUM` - Compacta la base de datos y libera espacio
- `ANALYZE` - Actualiza estadísticas de consultas

**Cuándo usar**:
- Después de eliminar grandes cantidades de datos
- Cuando la base de datos crece significativamente
- Como mantenimiento periódico (mensual)

### 6. Limpieza de Datos Antiguos

**Ruta**: `POST /admin/system-database/clean`

Elimina datos antiguos según configuración de retención:
- **Logs**: Configuración `log_retention_days` (default: 90 días)
- **Auditoría**: Configuración `audit_retention_days` (default: 365 días)
- **Papelera**: Registros eliminados hace más de 30 días

### 7. Visualización de Logs

**Ruta**: `/admin/system-database/logs`

Muestra todos los logs de operaciones del módulo:

**Tipos de eventos registrados**:
- `SYSTEM_BACKUP_CREATED` - Backup creado
- `SYSTEM_BACKUP_RESTORED` - Backup restaurado
- `SYSTEM_BACKUP_DELETED` - Backup eliminado
- `SYSTEM_QUERY_EXECUTED` - Consulta SQL ejecutada
- `SYSTEM_DATABASE_OPTIMIZED` - Base de datos optimizada
- `SYSTEM_DATA_CLEANED` - Datos antiguos eliminados
- `SYSTEM_LOGS_EXPORTED` - Logs exportados
- `SYSTEM_LOGS_CLEARED` - Logs limpiados

**Funcionalidades**:
- Filtrado por fecha (desde/hasta)
- Búsqueda de texto en logs
- Exportación a CSV
- Limpieza de logs antiguos

## 📋 Rutas del Módulo

### Rutas Principales
```php
GET  /admin/system-database                    // Dashboard
GET  /admin/system-database/tables             // Lista de tablas
GET  /admin/system-database/table-details      // Detalles de tabla
GET  /admin/system-database/query-executor     // Ejecutor SQL
POST /admin/system-database/execute-query      // Ejecutar consulta
POST /admin/system-database/optimize           // Optimizar DB
POST /admin/system-database/clean              // Limpiar datos antiguos
```

### Rutas de Backups
```php
GET  /admin/system-database/backups            // Lista de backups
POST /admin/system-database/backup/create      // Crear backup
POST /admin/system-database/backup/restore     // Restaurar backup
GET  /admin/system-database/backup/delete      // Eliminar backup
GET  /admin/system-database/backup/download    // Descargar backup
```

### Rutas de Logs
```php
GET  /admin/system-database/logs               // Ver logs
GET  /admin/system-database/logs/export        // Exportar logs
POST /admin/system-database/logs/clear         // Limpiar logs
```

## 🎨 Vistas del Módulo

### Archivos de Vista
```
src/Views/admin/system_database/
├── index.blade.php           // Dashboard principal
├── tables.blade.php          // Lista de tablas
├── table_details.blade.php   // Detalles de tabla
├── query_executor.blade.php  // Ejecutor SQL
├── backups.blade.php         // Gestión de backups
└── logs.blade.php            // Visualización de logs
```

## 🌍 Internacionalización

### Traducciones Disponibles

**Español** (`src/I18n/es.php`):
```php
'system_database' => [
    'title' => 'Base de Datos del Sistema',
    'dashboard' => 'Panel de Control',
    'tables' => 'Tablas del Sistema',
    'backups' => 'Copias de Seguridad',
    'logs' => 'Registros del Sistema',
    'query_executor' => 'Ejecutor SQL',
    // ... más traducciones
]
```

## 🔧 Configuración

### Variables de Configuración del Sistema

El módulo utiliza las siguientes configuraciones de `system_settings`:

| Clave | Descripción | Default |
|-------|-------------|---------|
| `log_retention_days` | Días de retención de logs | 90 |
| `audit_retention_days` | Días de retención de auditoría | 365 |

### Configuración de Backups Automáticos

Editar el script `/scripts/auto_backup_system.php`:

```php
$maxBackups = 30; // Número de backups automáticos a mantener
```

## 📊 Ejemplos de Uso

### Crear un Backup Manual

1. Navegar a `/admin/system-database/backups`
2. Clic en "Crear Backup"
3. El backup se crea instantáneamente
4. Aparece en la lista con fecha y tamaño

### Ejecutar una Consulta SQL

1. Navegar a `/admin/system-database/query-executor`
2. Escribir la consulta:
   ```sql
   SELECT username, role_id, status 
   FROM users 
   WHERE status = 1 
   ORDER BY id DESC 
   LIMIT 10;
   ```
3. Clic en "Ejecutar Consulta"
4. Ver resultados en tabla

### Restaurar desde Backup

1. Navegar a `/admin/system-database/backups`
2. Localizar el backup deseado
3. Clic en el icono de restaurar (⟲)
4. Confirmar la operación
5. El sistema crea un backup de seguridad antes de restaurar

### Optimizar la Base de Datos

1. Navegar a `/admin/system-database`
2. Clic en "Optimizar Base de Datos"
3. Confirmar la operación
4. El sistema ejecuta VACUUM + ANALYZE

## ⚠️ Advertencias de Seguridad

1. **Solo Super Admin**: Este módulo nunca debe ser accesible para usuarios normales o clientes
2. **Backups antes de operaciones críticas**: Siempre crear un backup antes de ejecutar consultas destructivas
3. **Consultas peligrosas**: El sistema solicita confirmación adicional para DROP, TRUNCATE, etc.
4. **Logs completos**: Todas las operaciones se registran con usuario, IP y timestamp
5. **Restauración**: Al restaurar un backup, se crea automáticamente un backup de seguridad

## 🐛 Solución de Problemas

### Error: "Database file not found"
- Verificar que `/data/system.sqlite` existe
- Verificar permisos de lectura/escritura

### Error al crear backup
- Verificar que `/data/backups/system/` existe y tiene permisos de escritura
- Verificar espacio en disco disponible

### Backups automáticos no se ejecutan
- Verificar que el cron job está configurado correctamente
- Verificar permisos de ejecución del script: `chmod +x scripts/auto_backup_system.php`
- Revisar logs del sistema

## 📝 Notas Adicionales

- Los backups se almacenan en formato SQLite nativo (copia directa del archivo)
- El módulo no afecta las bases de datos de proyectos, solo `system.sqlite`
- Todas las vistas utilizan el diseño moderno con glassmorphism del sistema
- El módulo es completamente responsive y funciona en dispositivos móviles

## 🔗 Enlaces Relacionados

- [Documentación de Autenticación](AUTH.md)
- [Documentación de Base de Datos](DATABASE.md)
- [Documentación de Logs](../README.md#logs)
