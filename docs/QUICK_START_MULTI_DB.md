# 🚀 Quick Start: Multi-Database Support

## ¿Qué se implementó?

DATA2REST ahora soporta **múltiples motores de base de datos** (SQLite, MySQL, etc.) de forma transparente.

## ✅ Ya está listo para usar

La migración ya se ejecutó. Todas tus bases de datos existentes ahora tienen `type = 'sqlite'`.

## 🎯 Uso Inmediato

### Para nuevo código (Recomendado):

```php
use App\Core\DatabaseManager;

// En lugar de:
// $targetDb = new PDO('sqlite:' . $database['path']);

// Usa:
$targetDb = DatabaseManager::getConnection($database);
```

### O usa funciones helper:

```php
// Obtener conexión
$pdo = getProjectDatabase($database);

// Listar tablas
$tables = listDatabaseTables($database);

// Probar conexión
$result = testDatabaseConnection($config);
```

### Código existente:

**No necesita cambios.** Todo sigue funcionando igual.

## 📝 Crear Base de Datos MySQL

```php
use App\Core\DatabaseManager;

$database = DatabaseManager::createDatabase(
    'Mi Proyecto MySQL',
    [
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'nombre_bd',
        'username' => 'root',
        'password' => 'tu_password'
    ],
    $projectId
);
```

## ⚙️ Configurar MySQL

Edita `.env`:

```bash
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_USERNAME=root
MYSQL_PASSWORD=tu_password
```

## 🧪 Probar

```bash
php scripts/examples/multi_database_demo.php
```

## 📚 Documentación Completa

- **Español:** `docs/MULTI_DATABASE.es.md`
- **English:** `docs/MULTI_DATABASE.md`
- **Resumen:** `docs/IMPLEMENTATION_SUMMARY.md`

## 🎨 Próximo Paso Sugerido

Crear interfaz web para gestionar bases de datos MySQL desde el panel de administración.

---

**¿Dudas?** Lee `docs/MULTI_DATABASE.es.md` para documentación completa con ejemplos.
