# Multi-Database Support

## Descripción

DATA2REST ahora soporta múltiples motores de base de datos de forma transparente. Puedes trabajar con SQLite, MySQL/MariaDB y potencialmente otros motores en el mismo proyecto.

## Motores Soportados

- **SQLite** (por defecto) - Base de datos embebida, ideal para desarrollo y proyectos pequeños
- **MySQL/MariaDB** - Base de datos cliente-servidor, ideal para producción y proyectos grandes
- **PostgreSQL** - Próximamente
- **SQL Server** - Próximamente

## Arquitectura

El sistema utiliza un patrón de adaptadores para abstraer las diferencias entre motores:

```
DatabaseFactory
    ↓
DatabaseAdapter (abstract)
    ↓
├── SQLiteAdapter
├── MySQLAdapter
└── PostgreSQLAdapter (futuro)
```

### Componentes Principales

1. **DatabaseAdapter** - Clase abstracta base que define la interfaz común
2. **DatabaseFactory** - Factory para crear instancias de adaptadores
3. **DatabaseManager** - Gestor centralizado de conexiones con caché
4. **Adaptadores específicos** - Implementaciones para cada motor (SQLiteAdapter, MySQLAdapter, etc.)

## Configuración

### Variables de Entorno (.env)

```bash
# Sistema de base de datos (siempre SQLite)
DB_PATH=/path/to/system.sqlite

# Configuración MySQL (para bases de datos de proyectos)
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_USERNAME=root
MYSQL_PASSWORD=tu_password
MYSQL_CHARSET=utf8mb4
```

### Configuración por Base de Datos

Cada base de datos en la tabla `databases` tiene un campo `config` (JSON) que almacena su configuración específica:

#### SQLite
```json
{
  "type": "sqlite",
  "path": "/path/to/database.sqlite"
}
```

#### MySQL
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

## Uso Programático

### Crear una Conexión SQLite

```php
use App\Core\DatabaseFactory;

$config = [
    'type' => 'sqlite',
    'path' => '/path/to/database.sqlite'
];

$adapter = DatabaseFactory::create($config);
$pdo = $adapter->getConnection();

// Usar PDO normalmente
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
```

### Crear una Conexión MySQL

```php
use App\Core\DatabaseFactory;

$config = [
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'mi_proyecto',
    'username' => 'root',
    'password' => 'password',
    'charset' => 'utf8mb4'
];

$adapter = DatabaseFactory::create($config);
$pdo = $adapter->getConnection();
```

### Usar DatabaseManager (Recomendado)

```php
use App\Core\DatabaseManager;

// Obtener conexión por ID de base de datos
$pdo = DatabaseManager::getConnectionById(1);

// Obtener adaptador (para funciones específicas del motor)
$adapter = DatabaseManager::getAdapterById(1);
$type = $adapter->getType(); // 'sqlite' o 'mysql'

// Crear nueva base de datos
$database = DatabaseManager::createDatabase(
    'Mi Proyecto',
    [
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'mi_proyecto',
        'username' => 'root',
        'password' => ''
    ],
    $projectId
);

// Probar conexión
$result = DatabaseManager::testConnection($config);
if ($result['success']) {
    echo "Conexión exitosa!";
} else {
    echo "Error: " . $result['message'];
}
```

### Trabajar con Registros de Base de Datos

```php
use App\Core\DatabaseFactory;

// Obtener registro de la tabla databases
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
$stmt->execute([1]);
$database = $stmt->fetch();

// Crear adaptador desde el registro
$adapter = DatabaseFactory::createFromDatabaseRecord($database);
$pdo = $adapter->getConnection();
```

## Funciones Específicas del Adaptador

Cada adaptador puede tener funciones específicas de su motor:

### SQLiteAdapter

```php
$adapter = DatabaseManager::getAdapterById(1);

// Obtener tamaño del archivo
$size = $adapter->getDatabaseSize();

// Optimizar (VACUUM)
$adapter->optimize();
```

### MySQLAdapter

```php
$adapter = DatabaseManager::getAdapterById(2);

// Obtener tamaño de la base de datos
$size = $adapter->getDatabaseSize();

// Optimizar todas las tablas
$adapter->optimize();

// Crear base de datos (si no existe)
$adapter->createDatabase('nueva_bd', 'utf8mb4', 'utf8mb4_unicode_ci');
```

## Consultas SQL Específicas del Motor

Los adaptadores proporcionan métodos para obtener SQL específico del motor:

```php
// Listar tablas
$sql = $adapter->getListTablesSQL();
$tables = $pdo->query($sql)->fetchAll();

// Obtener estructura de tabla
$sql = $adapter->getTableStructureSQL('users');
$structure = $pdo->query($sql)->fetchAll();

// Verificar si existe una tabla
$sql = $adapter->getTableExistsSQL('users');
$exists = $pdo->query($sql)->fetch();
```

## Migración de Bases de Datos Existentes

Para migrar bases de datos existentes al nuevo sistema:

```bash
php scripts/migrate_multi_database.php
```

Este script:
1. Agrega la columna `type` a la tabla `databases`
2. Actualiza registros existentes con `type = 'sqlite'`
3. Normaliza el campo `config` con la configuración apropiada

## Crear Base de Datos desde la Interfaz Web

*(Próximamente se agregará interfaz web para crear bases de datos MySQL)*

## Compatibilidad hacia Atrás

El sistema mantiene 100% de compatibilidad con código existente:

```php
// Esto sigue funcionando
$db = Database::getInstance()->getConnection();

// Ahora también puedes acceder al adaptador
$adapter = Database::getInstance()->getAdapter();
```

## Mejores Prácticas

1. **Usa DatabaseManager** para gestión centralizada de conexiones
2. **Cachea conexiones** - DatabaseManager cachea automáticamente
3. **Cierra conexiones** cuando termines trabajos largos:
   ```php
   DatabaseManager::clearCache($databaseId);
   ```
4. **Prueba conexiones** antes de crear bases de datos:
   ```php
   $result = DatabaseManager::testConnection($config);
   ```
5. **Usa transacciones** para operaciones críticas:
   ```php
   $adapter->beginTransaction();
   try {
       // operaciones...
       $adapter->commit();
   } catch (Exception $e) {
       $adapter->rollback();
   }
   ```

## Agregar Soporte para Nuevos Motores

Para agregar un nuevo motor de base de datos:

1. Crear adaptador extendiendo `DatabaseAdapter`:
   ```php
   namespace App\Core\Adapters;
   
   class PostgreSQLAdapter extends DatabaseAdapter
   {
       protected function connect(): PDO { ... }
       public function getListTablesSQL(): string { ... }
       public function getTableStructureSQL(string $tableName): string { ... }
       public function getTableExistsSQL(string $tableName): string { ... }
   }
   ```

2. Registrar en DatabaseFactory:
   ```php
   DatabaseFactory::registerAdapter('pgsql', PostgreSQLAdapter::class);
   ```

## Troubleshooting

### Error: "Unsupported database type"
- Verifica que el tipo esté en minúsculas
- Asegúrate de que el adaptador esté registrado

### Error de conexión MySQL
- Verifica credenciales en el config
- Asegúrate de que MySQL esté corriendo
- Verifica permisos del usuario MySQL

### SQLite: "unable to open database file"
- Verifica permisos del directorio
- Asegúrate de que la ruta sea absoluta
- El directorio debe existir y ser escribible

## Ejemplos Completos

Ver `/scripts/examples/` para ejemplos completos de uso.
