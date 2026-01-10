<?php
/**
 * Data2Rest Installer Schema Synchronizer
 * 
 * DESCRIPCIÓN:
 * Este script actúa como un agente de sincronización entre la base de datos física 
 * (system.sqlite) y el código fuente del instalador (src/Core/Installer.php). 
 * Captura la estructura actual de las tablas y actualiza el array estático $SCHEMA.
 * 
 * INSTRUCCIONES DE USO:
 * 1. Realiza cambios estructurales en data/system.sqlite (usando SQLite Browser o similar).
 * 2. Ejecuta este script:
 *    php scripts/sync_installer_schema.php
 * 3. El archivo Installer.php se actualizará con el nuevo esquema, permitiendo que
 *    otros entornos (producción/test) se sincronicen automáticamente al detectar los cambios.
 */

$dbPath = __DIR__ . '/../data/system.sqlite';
$installerPath = __DIR__ . '/../src/Core/Installer.php';

if (!file_exists($dbPath)) {
    die("Error: system.sqlite not found at $dbPath\n");
}

if (!file_exists($installerPath)) {
    die("Error: Installer.php not found at $installerPath\n");
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = $db->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_ASSOC);

    $schemaEntries = [];
    foreach ($tables as $table) {
        $name = $table['name'];
        $sql = trim($table['sql']);

        // Clean up the SQL a bit (indentation)
        $lines = explode("\n", $sql);
        $cleanSql = array_shift($lines);
        foreach ($lines as $line) {
            $cleanSql .= "\n                " . trim($line);
        }

        $schemaEntries[] = "        '$name' => [
            'sql' => \"$cleanSql\"
        ]";
    }

    $schemaCode = "    private static \$SCHEMA = [\n" . implode(",\n", $schemaEntries) . "\n    ];";

    // Read Installer.php
    $content = file_get_contents($installerPath);

    // Replace the SCHEMA block
    $pattern = '/private static \$SCHEMA = \[.*?\];/s';
    $newContent = preg_replace($pattern, $schemaCode, $content);

    if ($newContent === null) {
        die("Error: Failed to replace SCHEMA in Installer.php (Regex failed)\n");
    }

    file_put_contents($installerPath, $newContent);
    echo "Successfully updated Installer.php with current database schema.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
