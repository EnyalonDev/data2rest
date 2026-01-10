<?php
/**
 * Data2Rest Database Schema Dumper
 * 
 * DESCRIPCIÓN:
 * Utilidad de diagnóstico que genera un volcado legible (Dump) del esquema actual
 * de la base de datos de sistema. Es útil para depuración de la estructura SQL.
 * 
 * INSTRUCCIONES DE USO:
 * php scripts/dump_schema.php
 * (La salida se imprime por consola en formato de array exportable de PHP)
 */
require_once __DIR__ . '/../src/Core/Config.php';

use App\Core\Config;

// Mock Config if needed or just load it
// For this script, we just need the DB path.
$dbPath = __DIR__ . '/../data/system.sqlite';
if (!file_exists($dbPath)) {
    die("Database not found at $dbPath\n");
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all tables
$tables = $db->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_ASSOC);

$schema = [];

foreach ($tables as $table) {
    $name = $table['name'];
    $sql = $table['sql'];

    // Get columns
    $stmt = $db->query("PRAGMA table_info($name)");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $columnDefinitions = [];
    foreach ($cols as $col) {
        $def = $col['type'];
        if ($col['pk'])
            $def .= ' PRIMARY KEY';
        if ($col['notnull'])
            $def .= ' NOT NULL';
        if ($col['dflt_value'] !== null)
            $def .= ' DEFAULT ' . $col['dflt_value'];

        $columnDefinitions[$col['name']] = $def;
    }

    $schema[$name] = [
        'sql' => $sql,
        'columns' => $columnDefinitions
    ];
}

echo "<?php\nreturn " . var_export($schema, true) . ";\n";
