<?php

namespace App\Modules\System;

use App\Core\BaseController;
use App\Core\Config;
use App\Core\Database;
use App\Core\DatabaseFactory;

class MigrationController extends BaseController
{
    public function showMigrationForm()
    {
        // Only allow if current is SQLite
        $currentConfig = Config::get('system_db_config') ?? ['type' => 'sqlite'];
        if (($currentConfig['type'] ?? 'sqlite') !== 'sqlite') {
            // Already migrated or remote
            return $this->view('system/message', [
                'message' => \App\Core\Lang::get('migration.current_remote_error', ['type' => $currentConfig['type']])
            ]);
        }

        return $this->view('system/migration_form');
    }

    public function migrate()
    {
        ini_set('max_execution_time', 300); // Allow time for migration

        $targetType = $_POST['type'] ?? '';

        if (!in_array($targetType, ['mysql', 'pgsql'])) {
            return $this->json(['success' => false, 'message' => 'Invalid database type.']);
        }

        $targetConfig = [
            'type' => $targetType,
            'host' => $_POST['host'] ?? 'localhost',
            'port' => $_POST['port'] ?? ($targetType === 'mysql' ? 3306 : 5432),
            'database' => $_POST['database'] ?? 'data2rest_system',
            'username' => $_POST['username'] ?? 'root',
            'password' => $_POST['password'] ?? '',
        ];

        if ($targetType === 'mysql') {
            $targetConfig['charset'] = $_POST['charset'] ?? 'utf8mb4';
        } else {
            $targetConfig['schema'] = $_POST['schema'] ?? 'public';
        }

        try {
            // 1. Validate Target and Create DB if needed
            $this->prepareTargetDatabase($targetConfig);

            // 2. Connect to Target
            $targetAdapter = DatabaseFactory::create($targetConfig);
            $targetDb = $targetAdapter->getConnection();

            // 3. Get Source Data (SQLite)
            // We use the current global instance which is presumably SQLite per check
            $sourceAdapter = Database::getInstance()->getAdapter();
            $sourceDb = Database::getInstance()->getConnection();

            // 4. Migrate Schema and Data
            // We use Installer schema definition but applied to Target
            // We need to access Installer::$SCHEMA via Reflection or helper since it is private
            $schema = $this->getInstallerSchema();

            $targetDb->beginTransaction();

            foreach ($schema as $tableName => $definition) {
                // A. Create Table in Target
                // Determine SQL for target
                $createSql = $definition['sql'];

                if ($targetType === 'mysql') {
                    $createSql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $createSql);
                    $createSql = str_replace('STRING', 'VARCHAR(255)', $createSql);
                } elseif ($targetType === 'pgsql') {
                    $createSql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'SERIAL PRIMARY KEY', $createSql);
                    $createSql = str_replace('DATETIME', 'TIMESTAMP', $createSql);
                }

                // Drop if exists (Safety for fresh migration)
                $targetDb->exec("DROP TABLE IF EXISTS " . $targetAdapter->quoteName($tableName));
                $targetDb->exec($createSql);

                // B. Migrate Data
                // Fetch all from source
                $rows = $sourceDb->query("SELECT * FROM " . $sourceAdapter->quoteName($tableName))->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        // Build Insert
                        $columns = array_keys($row);
                        $placeholders = array_fill(0, count($columns), '?');

                        $sql = "INSERT INTO " . $targetAdapter->quoteName($tableName) . " (" .
                            implode(', ', array_map([$targetAdapter, 'quoteName'], $columns)) . ") VALUES (" .
                            implode(', ', $placeholders) . ")";

                        $stmt = $targetDb->prepare($sql);
                        $stmt->execute(array_values($row));
                    }
                }
            }

            $targetDb->commit();

            // 5. Update Config.json to switch system
            file_put_contents(__DIR__ . '/../../../data/config.json', json_encode($targetConfig, JSON_PRETTY_PRINT));

            return $this->json(['success' => true, 'redirect' => \App\Core\Auth::getBaseUrl() . 'admin/system/info']);

        } catch (\Exception $e) {
            if (isset($targetDb))
                $targetDb->rollBack();
            return $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function prepareTargetDatabase($config)
    {
        $tempConfig = $config;
        $tempConfig['database'] = ($config['type'] === 'pgsql') ? 'postgres' : (($config['type'] === 'mysql') ? 'information_schema' : $config['database']);
        $tempAdapter = DatabaseFactory::create($tempConfig);
        $tempAdapter->createDatabase($config['database']);
    }

    private function getInstallerSchema()
    {
        $class = new \ReflectionClass(\App\Core\Installer::class);
        $property = $class->getProperty('SCHEMA');
        $property->setAccessible(true);
        return $property->getValue();
    }
}
