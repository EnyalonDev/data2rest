# 🔍 DatabaseController - Análisis Completo de Refactorización

## 📊 Estado Actual

### ✅ Métodos Ya Refactorizados (5)
1. `viewTables()` - Ver tablas ✅
2. `syncDatabase()` - Sincronizar estructura ✅
3. `createTable()` - Crear tabla ✅
4. `addField()` - Agregar campo ✅
5. `deleteField()` - Eliminar campo ✅

### ⚠️ Métodos que Requieren Refactorización (11)

#### 🔴 PRIORIDAD ALTA - Operaciones Críticas

1. **`create()`** (línea ~150-195)
   - **Problema**: Crea solo SQLite
   - **Uso**: Método legacy, ahora se usa `createMulti()`
   - **Acción**: Marcar como deprecated o refactorizar

2. **`deleteTable()`** (línea ~722-747)
   - **Problema**: `new PDO('sqlite:' . $database['path'])`
   - **Impacto**: No puede eliminar tablas MySQL
   - **Refactorización**: NECESARIA ✅

3. **`createTableSql()`** (línea ~659-712)
   - **Problema**: Ejecuta SQL raw asumiendo SQLite
   - **Impacto**: SQL puede ser incompatible entre motores
   - **Refactorización**: NECESARIA ✅

4. **`manageFields()`** (línea ~757-797)
   - **Problema**: Solo obtiene lista de tablas SQLite
   - **Impacto**: No muestra tablas MySQL en selector
   - **Refactorización**: NECESARIA ✅

#### 🟡 PRIORIDAD MEDIA - Import/Export

5. **`importSql()`** (línea ~1067-1117)
   - **Problema**: Crea solo SQLite y ejecuta SQL
   - **Impacto**: No puede importar a MySQL
   - **Refactorización**: NECESARIA ✅

6. **`exportSql()`** (línea ~1127-1184)
   - **Problema**: Usa `sqlite3` CLI
   - **Impacto**: No puede exportar MySQL
   - **Refactorización**: NECESARIA ✅

7. **`exportTableSql()`** (línea ~1189-1247)
   - **Problema**: Usa sintaxis SQLite
   - **Impacto**: Exporta con sintaxis incorrecta para MySQL
   - **Refactorización**: NECESARIA ✅

8. **`exportTableExcel()`** (línea ~1250-1307)
   - **Problema**: Conexión SQLite hardcoded
   - **Impacto**: No puede exportar tablas MySQL a Excel
   - **Refactorización**: NECESARIA ✅

9. **`exportTableCsv()`** (línea ~1310-1359)
   - **Problema**: Conexión SQLite hardcoded
   - **Impacto**: No puede exportar tablas MySQL a CSV
   - **Refactorización**: NECESARIA ✅

10. **`generateExcelTemplate()`** (línea ~1362-1406)
    - **Problema**: Usa `PRAGMA table_info()` (SQLite)
    - **Impacto**: No genera templates para MySQL
    - **Refactorización**: NECESARIA ✅

11. **`generateCsvTemplate()`** (línea ~1409-1453)
    - **Problema**: Usa `PRAGMA table_info()` (SQLite)
    - **Impacto**: No genera templates para MySQL
    - **Refactorización**: NECESARIA ✅

#### 🟢 PRIORIDAD BAJA - Import (Menos Críticos)

12. **`importTableSql()`** (línea ~1456-1502)
    - **Problema**: Ejecuta SQL raw
    - **Impacto**: Puede funcionar pero sin validación
    - **Refactorización**: RECOMENDADA

13. **`importTableSqlText()`** (línea ~1505-1562)
    - **Problema**: Ejecuta SQL raw
    - **Impacto**: Puede funcionar pero sin validación
    - **Refactorización**: RECOMENDADA

14. **`importTableExcel()`** (línea ~1565-1620)
    - **Problema**: Conexión SQLite hardcoded
    - **Impacto**: No puede importar a MySQL
    - **Refactorización**: NECESARIA ✅

15. **`importTableCsv()`** (línea ~1623-1763)
    - **Problema**: Conexión SQLite hardcoded
    - **Impacto**: No puede importar a MySQL
    - **Refactorización**: NECESARIA ✅

## 🎯 Plan de Refactorización por Fases

### FASE 1: Operaciones de Tabla (CRÍTICO)
- [ ] `deleteTable()` - Eliminar tablas
- [ ] `createTableSql()` - Crear tabla con SQL
- [ ] `manageFields()` - Gestionar campos

### FASE 2: Export (ALTA PRIORIDAD)
- [ ] `exportSql()` - Exportar BD completa
- [ ] `exportTableSql()` - Exportar tabla SQL
- [ ] `exportTableExcel()` - Exportar tabla Excel
- [ ] `exportTableCsv()` - Exportar tabla CSV

### FASE 3: Templates (MEDIA PRIORIDAD)
- [ ] `generateExcelTemplate()` - Template Excel
- [ ] `generateCsvTemplate()` - Template CSV

### FASE 4: Import (MEDIA PRIORIDAD)
- [ ] `importSql()` - Importar BD completa
- [ ] `importTableExcel()` - Importar Excel
- [ ] `importTableCsv()` - Importar CSV
- [ ] `importTableSql()` - Importar SQL (archivo)
- [ ] `importTableSqlText()` - Importar SQL (texto)

## 🗄️ Consideraciones Multi-Motor

### SQLite vs MySQL vs PostgreSQL vs MariaDB

#### Listar Tablas
```sql
-- SQLite
SELECT name FROM sqlite_master WHERE type='table'

-- MySQL/MariaDB
SHOW TABLES

-- PostgreSQL
SELECT tablename FROM pg_tables WHERE schemaname = 'public'
```

#### Listar Columnas
```sql
-- SQLite
PRAGMA table_info(table_name)

-- MySQL/MariaDB
SHOW COLUMNS FROM table_name

-- PostgreSQL
SELECT column_name, data_type FROM information_schema.columns 
WHERE table_name = 'table_name'
```

#### DROP TABLE
```sql
-- Todos (compatible)
DROP TABLE IF EXISTS table_name
```

#### Tipos de Datos Comunes
| Concepto | SQLite | MySQL/MariaDB | PostgreSQL |
|----------|--------|---------------|------------|
| Entero Auto | INTEGER PRIMARY KEY AUTOINCREMENT | INT AUTO_INCREMENT | SERIAL |
| Texto | TEXT | VARCHAR(255) / TEXT | VARCHAR / TEXT |
| Fecha/Hora | TEXT / DATETIME | DATETIME | TIMESTAMP |
| Booleano | INTEGER (0/1) | TINYINT(1) / BOOLEAN | BOOLEAN |

#### Export/Import
- **SQLite**: `sqlite3 db.sqlite .dump`
- **MySQL/MariaDB**: `mysqldump -u user -p database`
- **PostgreSQL**: `pg_dump database`

## 🔧 Patrón de Refactorización Estándar

```php
public function methodName() {
    // 1. Obtener database record
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
    $stmt->execute([$db_id]);
    $database = $stmt->fetch();
    
    // 2. Obtener adaptador
    $adapter = \App\Core\DatabaseManager::getAdapter($database);
    $connection = $adapter->getConnection();
    $dbType = $adapter->getType();
    
    // 3. Ejecutar según tipo
    switch ($dbType) {
        case 'sqlite':
            // SQLite logic
            break;
        case 'mysql':
        case 'mariadb':
            // MySQL/MariaDB logic (similar)
            break;
        case 'pgsql':
        case 'postgresql':
            // PostgreSQL logic
            break;
        default:
            throw new \Exception("Unsupported database type: $dbType");
    }
}
```

## 📝 Métodos Helper Necesarios

Para facilitar la refactorización, se deberían crear métodos helper:

```php
/**
 * Get table list query based on database type
 */
private function getTableListQuery(string $dbType): string
{
    switch ($dbType) {
        case 'sqlite':
            return "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'";
        case 'mysql':
        case 'mariadb':
            return "SHOW TABLES";
        case 'pgsql':
        case 'postgresql':
            return "SELECT tablename FROM pg_tables WHERE schemaname = 'public'";
        default:
            throw new \Exception("Unsupported database type: $dbType");
    }
}

/**
 * Get column list for a table based on database type
 */
private function getColumnInfo(PDO $connection, string $dbType, string $table): array
{
    switch ($dbType) {
        case 'sqlite':
            $stmt = $connection->query("PRAGMA table_info(`$table`)");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        case 'mysql':
        case 'mariadb':
            $stmt = $connection->query("SHOW COLUMNS FROM `$table`");
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Convert to SQLite-like format
            return array_map(function($col) {
                return ['name' => $col['Field'], 'type' => $col['Type']];
            }, $cols);
            
        case 'pgsql':
        case 'postgresql':
            $stmt = $connection->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ?");
            $stmt->execute([$table]);
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(function($col) {
                return ['name' => $col['column_name'], 'type' => $col['data_type']];
            }, $cols);
            
        default:
            throw new \Exception("Unsupported database type: $dbType");
    }
}

/**
 * Get export command based on database type
 */
private function getExportCommand(string $dbType, array $config, string $outputFile): string
{
    switch ($dbType) {
        case 'sqlite':
            return "sqlite3 " . escapeshellarg($config['path']) . " .dump > " . escapeshellarg($outputFile);
            
        case 'mysql':
        case 'mariadb':
            return sprintf(
                "mysqldump -h %s -P %s -u %s -p%s %s > %s",
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['database']),
                escapeshellarg($outputFile)
            );
            
        case 'pgsql':
        case 'postgresql':
            return sprintf(
                "PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s > %s",
                escapeshellarg($config['password']),
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['username']),
                escapeshellarg($config['database']),
                escapeshellarg($outputFile)
            );
            
        default:
            throw new \Exception("Unsupported database type: $dbType");
    }
}
```

## 🎯 Próximos Pasos Inmediatos

1. **Crear métodos helper** en `DatabaseController`
2. **Refactorizar FASE 1** (operaciones críticas de tabla)
3. **Probar con MySQL** cada método refactorizado
4. **Documentar diferencias** entre motores
5. **Crear adaptadores** para PostgreSQL cuando sea necesario

## 📊 Estimación de Esfuerzo

- **FASE 1**: 2-3 horas (3 métodos críticos)
- **FASE 2**: 3-4 horas (4 métodos export)
- **FASE 3**: 1-2 horas (2 métodos templates)
- **FASE 4**: 3-4 horas (5 métodos import)
- **Testing**: 2-3 horas
- **Total**: ~15 horas

## ✅ Recomendación

Proceder con la refactorización en fases, comenzando por FASE 1 (operaciones críticas) para asegurar que las funcionalidades básicas funcionen en todos los motores antes de abordar import/export.
