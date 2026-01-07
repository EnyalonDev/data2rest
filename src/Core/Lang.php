<?php

namespace App\Core;

class Lang
{
    private static $translations = null;
    private static $currentLang = 'es';

    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::$currentLang = $_SESSION['lang'] ?? 'es';

        $filePath = __DIR__ . '/../I18n/' . self::$currentLang . '.php';
        if (file_exists($filePath)) {
            self::$translations = require $filePath;
        } else {
            // Fallback to Spanish if something fails
            $fallbackPath = __DIR__ . '/../I18n/es.php';
            if (file_exists($fallbackPath)) {
                self::$translations = require $fallbackPath;
            } else {
                self::$translations = []; // Critical fallback
            }
        }
    }

    public static function set($lang)
    {
        $allowed = ['es', 'en', 'pt'];
        if (in_array($lang, $allowed)) {
            $_SESSION['lang'] = $lang;
            self::$currentLang = $lang;
            self::init();
        }
    }

    public static function get($key, $default = null)
    {
        if (self::$translations === null) {
            self::init();
        }

        $parts = explode('.', $key);
        $result = self::$translations;

        foreach ($parts as $part) {
            if (isset($result[$part]) && (is_array($result[$part]) || is_string($result[$part]))) {
                $result = $result[$part];
            } else {
                return $default ?? $key;
            }
        }

        return is_string($result) ? $result : ($default ?? $key);
    }

    public static function current()
    {
        return self::$currentLang;
    }
}