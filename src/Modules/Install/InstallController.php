<?php

namespace App\Modules\Install;

use App\Core\BaseController;
use App\Core\Auth;

class InstallController extends BaseController
{
    public function index()
    {
        // If config file already exists, redirect to login
        if (file_exists(__DIR__ . '/../../../data/config.json')) {
            $this->redirect('login');
        }

        // Initialize Lang for installer
        \App\Core\Lang::init();

        $this->view('install/index', [
            'baseUrl' => Auth::getBaseUrl(),
            'lang' => \App\Core\Lang::all()
        ]);
    }

    public function install()
    {
        // Start session (CSRF is excluded for the installation module)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $type = $_POST['type'] ?? 'sqlite';

        $config = [
            'type' => $type
        ];

        if ($type === 'sqlite') {
            // Default SQLite Configuration
            $config['path'] = __DIR__ . '/../../../data/system.sqlite';
        } else {
            // MySQL / PostgreSQL Configuration
            $config['host'] = $_POST['host'] ?? 'localhost';
            $config['port'] = $_POST['port'] ?? ($type === 'mysql' ? 3306 : 5432);
            $config['database'] = $_POST['database'] ?? 'data2rest_system';
            $config['username'] = $_POST['username'] ?? 'root';
            // We don't save password in plain text config usually, but for this simplified flow:
            $config['password'] = $_POST['password'] ?? '';
            if ($type === 'mysql') {
                $config['charset'] = $_POST['charset'] ?? 'utf8mb4';
            } else {
                $config['schema'] = $_POST['schema'] ?? 'public';
            }
        }

        // 1. Save Config
        file_put_contents(__DIR__ . '/../../../data/config.json', json_encode($config, JSON_PRETTY_PRINT));

        // 2. Run Migrations / Setup Tables
        // This logic needs to be robust. For now, we simulate simple setup.
        // In reality, we should instantiate the adapter here and run setups.

        try {
            $this->runInitialSetup($config);
            $this->json(['success' => true, 'redirect' => Auth::getBaseUrl() . 'login']);
        } catch (\Exception $e) {
            // If failed, remove config so user can try again
            @unlink(__DIR__ . '/../../../data/config.json');
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function runInitialSetup($config)
    {
        // 1. Create Adapter using Factory (without DB selected for remote DBs to create it)
        if ($config['type'] !== 'sqlite') {
            try {
                // Setup temp config to connect without DB name (or to default DB)
                $factoryConfig = $config;

                // For connection test/creation, we connect to system default DB
                $factoryConfig['database'] = ($config['type'] === 'pgsql') ? 'postgres' : (($config['type'] === 'mysql') ? 'information_schema' : $config['database']);

                // Create temp adapter just for DB creation
                $tempAdapter = \App\Core\DatabaseFactory::create($factoryConfig);

                // Create Database
                $tempAdapter->createDatabase($config['database']);

            } catch (\Exception $e) {
                // Ignore if DB already exists or connection fails (might be that we can't connect to default 'postgres'/'mysql')
            }
        }

        // 2. Connect to the ACTUAL target database to see if connection works
        // This will throw exception if connection fails, which is what we want (to rollback)
        $adapter = \App\Core\DatabaseFactory::create($config);
        $adapter->getConnection(); // trigger connection

        // 3. We rely on Installer::check() running on next request or explicitly here
        // Ideally, we'd run Installer::syncSchema($adapter) here, but Installer is static and coupled to Config.
        // Since we saved Config.json, the next request (redirect to login) will trigger Installer::check()
        // which will read the new config and run migrations.

        // So for now, validating connection is enough.
    }
}
