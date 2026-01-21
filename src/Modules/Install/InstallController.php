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
            'baseUrl' => Auth::getFullBaseUrl(),
            'lang' => \App\Core\Lang::all()
        ]);
    }

    public function install()
    {
        ob_start();

        // Start session (CSRF is excluded for the installation module)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $type = $_POST['type'] ?? 'sqlite';
        $type = $_POST['type'] ?? 'sqlite';


        $config = [
            'type' => $type
        ];

        if ($type === 'sqlite') {
            // Default SQLite Configuration
            $config['path'] = __DIR__ . '/../../../data/system.sqlite';
        } else {
            // MySQL / PostgreSQL Configuration
            $rawHost = $_POST['host'] ?? 'localhost';
            // Force IPv4 loopback if localhost is provided to avoid ::1 resolution and auth issues
            $config['host'] = ($rawHost === 'localhost') ? '127.0.0.1' : $rawHost;

            $config['port'] = $_POST['port'] ?? ($type === 'mysql' ? 3306 : 5432);

            set_time_limit(300); // Increase execution time for DB creation
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
        } catch (\Throwable $e) {
            // If failed, remove config so user can try again
            @unlink(__DIR__ . '/../../../data/config.json');
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    private function runInitialSetup($config)
    {
        // Optimized Flow: Try connecting to the target database FIRST.
        // If it exists and we can connect, we skip the risky 'createDatabase' step which 
        // requires access to the system 'postgres' database (often restricted).

        $connected = false;
        try {
            $adapter = \App\Core\DatabaseFactory::create($config);
            $adapter->getConnection(); // trigger connection
            $connected = true;
        } catch (\Throwable $e) {
            // Connection failed, so now we try to create it
        }

        if (!$connected && $config['type'] !== 'sqlite') {
            try {
                // Setup temp config to connect without DB name (or to default DB)
                $factoryConfig = $config;

                // For connection test/creation, we connect to system default DB
                $factoryConfig['database'] = ($config['type'] === 'pgsql') ? 'postgres' : (($config['type'] === 'mysql') ? 'information_schema' : $config['database']);

                // Create temp adapter just for DB creation
                $tempAdapter = \App\Core\DatabaseFactory::create($factoryConfig);

                // Create Database
                $tempAdapter->createDatabase($config['database']);

            } catch (\Throwable $e) {
                // Ignore if DB already exists or connection fails
            }

            // Re-try connection after creation attempt
            $adapter = \App\Core\DatabaseFactory::create($config);
            $adapter->getConnection();
            $adapter = \App\Core\DatabaseFactory::create($config);
            $adapter->getConnection();
        }
    }

    public function checkConnection()
    {
        $type = $_POST['type'] ?? 'sqlite';
        $host = $_POST['host'] ?? 'localhost';
        if ($host === 'localhost')
            $host = '127.0.0.1';
        $port = $_POST['port'] ?? 5432;
        $dbName = $_POST['database'] ?? '';
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';

        if ($type === 'sqlite') {
            $this->json([
                'status' => 'ready',
                'message' => 'SQLite no requiere conexión externa.',
                'can_create' => true
            ]);
            return;
        }

        // 1. Try connecting to Target DB
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbName;connect_timeout=5";
            if ($type === 'mysql') {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
            }

            $pdo = new \PDO($dsn, $user, $pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

            $this->json([
                'status' => 'exists',
                'message' => "La base de datos '$dbName' ya existe y la conexión es exitosa.",
                'can_create' => false // It already exists
            ]);
            return;
        } catch (\PDOException $e) {
            // If error is auth, stop here
            if (strpos($e->getMessage(), 'authentication failed') !== false || $e->getCode() == 1045) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Error de Autenticación: Contraseña o Usuario incorrecto.',
                    'debug' => $e->getMessage()
                ]);
                return;
            }

            // If error isn't "db does not exist", return error
            // Postgres: "database ... does not exist"
            // MySQL: Unknown database
            if (strpos($e->getMessage(), 'does not exist') === false && strpos($e->getMessage(), 'Unknown database') === false) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Error de Conexión: ' . $e->getMessage(),
                    'debug' => $e->getMessage()
                ]);
                return;
            }
        }

        // 2. If we are here, DB does not exist. Try connecting to Server to see if we can create it
        try {
            $defaultDb = ($type === 'pgsql') ? 'postgres' : 'information_schema';
            $dsn = "pgsql:host=$host;port=$port;dbname=$defaultDb;connect_timeout=5";
            if ($type === 'mysql') {
                $dsn = "mysql:host=$host;port=$port;dbname=$defaultDb;charset=utf8mb4";
            }

            $pdo = new \PDO($dsn, $user, $pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

            $this->json([
                'status' => 'can_create',
                'message' => "La base de datos '$dbName' no existe, pero tenemos permisos para crearla.",
                'can_create' => true
            ]);
        } catch (\PDOException $e) {
            $this->json([
                'status' => 'error',
                'message' => "La base de datos '$dbName' no existe y NO se pudo conectar al servidor para crearla.",
                'debug' => $e->getMessage(),
                'hint' => 'Verifique que el usuario tenga permisos o cree la base de datos manualmente.'
            ]);
        }
    }
}
