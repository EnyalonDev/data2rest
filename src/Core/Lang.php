<?php

namespace App\Core;

/**
 * Internationalization (i18n) Manager
 * Handles language loading, switching, and string translation.
 */
class Lang
{
    /** @var array|null Loaded translations array */
    private static $translations = null;

    /** @var string Currently active language code */
    private static $currentLang = 'es';

    /**
     * Initializes the language manager.
     * Loads the translation file based on the session or default language.
     */
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
            // Fallback to Spanish if the specified language file is missing
            $fallbackPath = __DIR__ . '/../I18n/es.php';
            if (file_exists($fallbackPath)) {
                self::$translations = require $fallbackPath;
            } else {
                self::$translations = []; // Critical fallback to prevent errors
            }
        }
    }

    /**
     * Changes the current language.
     * 
     * @param string $lang Language code (es, en, pt)
     */
    public static function set($lang)
    {
        $allowed = ['es', 'en', 'pt'];
        if (in_array($lang, $allowed)) {
            $_SESSION['lang'] = $lang;
            self::$currentLang = $lang;
            self::init(); // Reload translations
        }
    }

    /**
     * Retrieves a translated string by its dot-notation key.
     * 
     * @param string $key Dot-notation key (e.g., 'common.save')
     * @param array|string|null $replace Array of placeholders to replace, or default string
     * @param string|null $default Default value if key is not found
     * @return string
     */
    public static function get($key, $replace = null, $default = null)
    {
        if (self::$translations === null) {
            self::init();
        }

        if (is_string($replace) && $default === null) {
            $default = $replace;
            $replace = [];
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

        $translation = is_string($result) ? $result : ($default ?? $key);

        if (!empty($replace) && is_array($replace)) {
            foreach ($replace as $k => $v) {
                $translation = str_replace(':' . $k, $v, $translation);
            }
        }

        return $translation;
    }

    /**
     * Returns the current language code.
     * 
     * @return string
     */
    public static function current()
    {
        return self::$currentLang;
    }
}