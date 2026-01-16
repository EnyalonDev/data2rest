# Soporte Multi-Base de Datos

## 🎯 Resumen

Se ha implementado soporte completo para múltiples motores de base de datos en DATA2REST. Ahora puedes trabajar con SQLite, MySQL/MariaDB y potencialmente otros motores de forma transparente.

## ✨ Características

- ✅ **Soporte SQLite** - Base de datos embebida (por defecto)
- ✅ **Soporte MySQL/MariaDB** - Base de datos cliente-servidor
- ✅ **Arquitectura de Adaptadores** - Fácil de extender para nuevos motores
- ✅ **Gestión Centralizada** - DatabaseManager con caché de conexiones
- ✅ **Compatibilidad 100%** - Todo el código existente sigue funcionando
- ✅ **Configuración Flexible** - Por proyecto o global
- ✅ **Migración Automática** - Script para actualizar bases de datos existentes

## 📁 Archivos Creados

### Core
- `src/Core/DatabaseAdapter.php` - Clase abstracta base para adaptadores
- `src/Core/Adapters/SQLiteAdapter.php` - Adaptador para SQLite
- `src/Core/Adapters/MySQLAdapter.php` - Adaptador para MySQL
- `src/Core/DatabaseFactory.php` - Factory para crear adaptadores
- `src/Core/DatabaseManager.php` - Gestor centralizado de conexiones

### Scripts
- `scripts/migrate_multi_database.php` - Migración de bases de datos existentes
- `scripts/examples/multi_database_demo.php` - Ejemplos de uso

### Documentación
- `docs/MULTI_DATABASE.md` - Documentación completa (inglés)
- `docs/MULTI_DATABASE.es.md` - Este archivo

## 📝 Archivos Modificados

- `src/Core/Database.php` - Actualizado para usar DatabaseAdapter
- `.env` - Agregadas variables de configuración MySQL

## 🚀 Inicio Rápido

### 1. Ejecutar Migración

```bash
php scripts/migrate_multi_database.php
```

Esto actualiza la tabla `databases` para soportar múltiples motores.

### 2. Configurar MySQL (Opcional)

Edita `.env` y agrega:

```bash
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_USERNAME=root
MYSQL_PASSWORD=tu_password
MYSQL_CHARSET=utf8mb4
```

### 3. Probar el Sistema

```bash
php scripts/examples/multi_database_demo.php
```

## 💻 Uso Básico

### Crear Base de Datos SQLite

```php
use App\Core\DatabaseManager;

$database = DatabaseManager::createDatabase(
    'Mi Proyecto',
    [
        'type' => 'sqlite',
        'path' => '/ruta/a/base.sqlite'
    ],
    $projectId
);
```

### Crear Base de Datos MySQL

```php
use App\Core\DatabaseManager;

$database = DatabaseManager::createDatabase(
    'Mi Proyecto MySQL',
    [
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'mi_proyecto',
        'username' => 'root',
        'password' => 'password'
    ],
    $projectId
);
```

### Obtener Conexión

```php
use App\Core\DatabaseManager;

// Por ID de base de datos
$pdo = DatabaseManager::getConnectionById(1);

// Desde registro de base de datos
$adapter = DatabaseManager::getAdapter($database);
$pdo = $adapter->getConnection();
```

### Probar Conexión

```php
use App\Core\DatabaseManager;

$result = DatabaseManager::testConnection([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'test',
    'username' => 'root',
    'password' => ''
]);

if ($result['success']) {
    echo "Conexión exitosa!";
} else {
    echo "Error: " . $result['message'];
}
```

## 🏗️ Arquitectura

```
┌─────────────────────────────────────┐
│      DatabaseManager                │
│  (Gestión centralizada + caché)    │
└──────────────┬──────────────────────┘
               │
               ↓
┌─────────────────────────────────────┐
│      DatabaseFactory                │
│  (Crea adaptadores según config)   │
└──────────────┬──────────────────────┘
               │
               ↓
┌─────────────────────────────────────┐
│      DatabaseAdapter                │
│      (Interfaz abstracta)           │
└──────────────┬──────────────────────┘
               │
       ┌───────┴────────┐
       ↓                ↓
┌─────────────┐  ┌─────────────┐
│SQLiteAdapter│  │MySQLAdapter │
└─────────────┘  └─────────────┘
```

## 🔧 Configuración de Base de Datos

Cada base de datos tiene un campo `config` (JSON) en la tabla `databases`:

### SQLite
```json
{
  "type": "sqlite",
  "path": "/ruta/absoluta/base.sqlite"
}
```

### MySQL
```json
{
  "type": "mysql",
  "host": "localhost",
  "port": 3306,
  "database": "nombre_bd",
  "username": "usuario",
  "password": "contraseña",
  "charset": "utf8mb4"
}
```

## 🔄 Migración de Código Existente

El código existente **NO necesita cambios**. Todo sigue funcionando:

```php
// Esto sigue funcionando igual
$db = Database::getInstance()->getConnection();

// Pero ahora también puedes hacer:
$adapter = Database::getInstance()->getAdapter();
$type = $adapter->getType(); // 'sqlite'
```

Para nuevo código, se recomienda usar `DatabaseManager`:

```php
// Antes
$targetDb = new PDO('sqlite:' . $database['path']);

// Ahora (recomendado)
$targetDb = DatabaseManager::getConnection($database);
```

## 📊 Esquema de Base de Datos

La tabla `databases` ahora incluye:

```sql
CREATE TABLE databases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    path TEXT,              -- Para SQLite
    type TEXT DEFAULT 'sqlite',  -- Nuevo: 'sqlite', 'mysql', etc.
    config TEXT,            -- JSON con configuración específica
    project_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_edit_at DATETIME
);
```

## 🎨 Próximos Pasos

### Interfaz Web (Próximamente)
- [ ] Formulario para crear bases de datos MySQL desde el admin
- [ ] Selector de tipo de base de datos en creación de proyectos
- [ ] Panel de gestión de conexiones
- [ ] Prueba de conexión desde la interfaz

### Motores Adicionales (Futuro)
- [ ] PostgreSQL
- [ ] SQL Server
- [ ] Oracle

### Mejoras
- [ ] Pool de conexiones
- [ ] Replicación y failover
- [ ] Métricas de rendimiento

## 📖 Documentación Completa

Ver `docs/MULTI_DATABASE.md` para documentación completa en inglés con ejemplos avanzados.

## ⚠️ Notas Importantes

1. **Base de Datos del Sistema**: La base de datos del sistema (`system.sqlite`) siempre usa SQLite
2. **Permisos MySQL**: Asegúrate de que el usuario MySQL tenga permisos para crear bases de datos
3. **Seguridad**: Las contraseñas se almacenan en el campo `config`. Considera encriptarlas en producción
4. **Caché**: DatabaseManager cachea conexiones. Usa `clearCache()` si necesitas forzar reconexión

## 🐛 Troubleshooting

### "Unsupported database type"
- Verifica que el tipo esté en minúsculas ('mysql', no 'MySQL')
- Asegúrate de que el adaptador esté disponible

### Error de conexión MySQL
- Verifica credenciales en el config
- Confirma que MySQL esté corriendo: `mysql -u root -p`
- Revisa permisos del usuario

### SQLite: "unable to open database file"
- Verifica que el directorio exista
- Asegúrate de que sea escribible: `chmod 755 /ruta/data/`
- Usa rutas absolutas

## 📞 Soporte

Para más información, consulta:
- Documentación completa: `docs/MULTI_DATABASE.md`
- Ejemplos: `scripts/examples/multi_database_demo.php`
- Código fuente: `src/Core/DatabaseAdapter.php`
