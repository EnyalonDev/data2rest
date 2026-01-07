<?php

namespace App\Core;

use PDO;
use PDOException;

class Installer {
    public static function check() {
        $dbPath = Config::get('db_path');
        $dataDir = dirname($dbPath);
        $uploadDir = Config::get('upload_dir');

        // Create directories if missing
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // If DB doesn't exist, initialize
        if (!file_exists($dbPath)) {
            self::initDatabase($dbPath);
        }
    }

    private static function initDatabase($dbPath) {
        try {
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $queries = [
                "CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE,
                    password TEXT,
                    role TEXT DEFAULT 'user',
                    status INTEGER DEFAULT 1
                )",
                "CREATE TABLE databases (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT,
                    path TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE fields_config (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    db_id INTEGER,
                    table_name TEXT,
                    field_name TEXT,
                    data_type TEXT,
                    view_type TEXT,
                    is_required INTEGER DEFAULT 0,
                    is_visible INTEGER DEFAULT 1,
                    is_editable INTEGER DEFAULT 1,
                    options TEXT,
                    FOREIGN KEY (db_id) REFERENCES databases(id)
                )",
                "CREATE TABLE media_config (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    mime_type TEXT,
                    extension TEXT,
                    is_allowed INTEGER DEFAULT 1
                )",
                "CREATE TABLE api_keys (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    key_value TEXT UNIQUE,
                    name TEXT,
                    permissions TEXT,
                    status INTEGER DEFAULT 1
                )",
                "CREATE TABLE api_endpoints (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT,
                    path TEXT UNIQUE,
                    method TEXT,
                    table_source TEXT,
                    sql_query TEXT,
                    requires_auth INTEGER DEFAULT 1,
                    return_type TEXT,
                    visible_fields TEXT,
                    is_active INTEGER DEFAULT 1
                )",
                "CREATE TABLE logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT,
                    details TEXT,
                    response_time FLOAT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
            ];

            foreach ($queries as $query) {
                $db->exec($query);
            }

            // Create default admin user (password: admin123)
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
            $stmt->execute([$password]);

        } catch (PDOException $e) {
            die("Auto-Installation Error: " . $e->getMessage());
        }
    }
}
