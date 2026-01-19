<?php
if (ob_get_level() === 0)
    ob_start();

/**
 * Custom PSR-4 Autoloader
 * Automatically maps the 'App\' namespace to the 'src/' directory.
 */
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'App\\';
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace namespace separators with directory separators in the relative class name, 
    // and append with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Load helper functions
$helpersDir = __DIR__ . '/helpers/';
if (is_dir($helpersDir)) {
    foreach (glob($helpersDir . '*.php') as $helperFile) {
        require_once $helperFile;
    }
}
