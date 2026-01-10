<?php
/**
 * Data2Rest I18n Synchronization Agent
 * 
 * This script scans the codebase for Lang::get('key') calls and ensures
 * all keys exist in the translation files (es.php, en.php, pt.php).
 */

$languages = ['es', 'en', 'pt'];
$basePath = realpath(__DIR__ . '/../');
$i18nPath = $basePath . '/src/I18n';
$srcPath = $basePath . '/src';
$viewsPath = $basePath . '/src/Views';

// If run with --search="text", find the key quickly and exit
if (php_sapi_name() === 'cli' && isset($argv[1]) && strpos($argv[1], '--search=') === 0) {
    $searchTerm = substr($argv[1], 9);
    $key = findKeyByValue($searchTerm);
    if ($key) {
        echo "\e[1;32m[Match Found]\e[0m Value '$searchTerm' matches key: \e[1;36m$key\e[0m\n";
    } else {
        echo "\e[1;31m[No Match]\e[0m No existing key found for: '$searchTerm'\n";
    }
    exit;
}

echo "\e[1;34m[I18n Agent]\e[0m Scanning codebase for language keys...\n";

// 1. Scan files for Lang::get calls
$foundKeys = [];
$extensions = ['php', 'blade.php'];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcPath));

foreach ($iterator as $file) {
    if ($file->isDir())
        continue;
    if (!in_array($file->getExtension(), ['php']) && strpos($file->getFilename(), '.blade.php') === false)
        continue;

    $content = file_get_contents($file->getPathname());

    // Regex to find Lang::get('key') or \App\Core\Lang::get('key')
    preg_match_all('/(?:Lang|\\\\App\\\\Core\\\\Lang)::get\([\'"](.+?)[\'"]/', $content, $matches);

    if (!empty($matches[1])) {
        foreach ($matches[1] as $key) {
            $foundKeys[$key] = true;
        }
    }
}

$keys = array_keys($foundKeys);
sort($keys);

echo "\e[1;32m[I18n Agent]\e[0m Found " . count($keys) . " unique keys in code.\n";

// 2. Process each language file
foreach ($languages as $lang) {
    echo "\n\e[1;36m[Processing: $lang]\e[0m\n";
    $filePath = $i18nPath . '/' . $lang . '.php';

    if (!file_exists($filePath)) {
        echo "Creating new language file: $lang.php\n";
        $currentTranslations = [];
    } else {
        $currentTranslations = require $filePath;
    }

    $missingCount = 0;
    foreach ($keys as $key) {
        if (!keyExists($key, $currentTranslations)) {
            $currentTranslations = insertKey($key, $currentTranslations);
            echo "  [+] Adding missing key: \e[0;33m$key\e[0m\n";
            $missingCount++;
        }
    }

    if ($missingCount > 0) {
        $export = "<?php\nreturn " . var_export_pretty($currentTranslations) . ";\n";
        file_put_contents($filePath, $export);
        echo "\e[1;32m[Done]\e[0m Updated $lang.php with $missingCount new keys.\n";
    } else {
        echo "\e[0;90m[OK]\e[0m No missing keys found in $lang.php.\n";
    }
}

/**
 * Check if a dot-notation key exists in a nested array.
 */
function keyExists($key, $array)
{
    $parts = explode('.', $key);
    foreach ($parts as $part) {
        if (!isset($array[$part])) {
            return false;
        }
        $array = $array[$part];
    }
    return true;
}

/**
 * Insert a dot-notation key into a nested array.
 */
function insertKey($key, $array)
{
    $parts = explode('.', $key);
    $temp = &$array;

    foreach ($parts as $part) {
        if (!isset($temp[$part])) {
            $temp[$part] = [];
        }
        $temp = &$temp[$part];
    }

    // If it's an empty array (just created), set it to the key name as default value
    if (is_array($temp) && empty($temp)) {
        $temp = "NEW: " . $key;
    }

    return $array;
}

/**
 * Prettier var_export
 */
function var_export_pretty($expression)
{
    $export = var_export($expression, true);
    $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
    $array = preg_split("/\r\n|\n|\r/", $export);
    $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
    $export = join(PHP_EOL, array_filter(["["] + $array));

    // Final touch for indentation and style
    $export = str_replace('=> [', '=> [', $export);
    $export = str_replace('  ', '    ', $export);

    return $export;
}

/**
 * Search for a value in the translations and return its key.
 */
function findKeyByValue($value, $lang = 'es')
{
    global $i18nPath;
    $filePath = $i18nPath . '/' . $lang . '.php';
    if (!file_exists($filePath))
        return null;

    $translations = require $filePath;
    return searchRecursive($value, $translations);
}

function searchRecursive($value, $array, $prefix = '')
{
    foreach ($array as $key => $val) {
        $currentKey = $prefix ? $prefix . '.' . $key : $key;
        if (is_array($val)) {
            $res = searchRecursive($value, $val, $currentKey);
            if ($res)
                return $res;
        } elseif (mb_strtolower($val) === mb_strtolower($value)) {
            return $currentKey;
        }
    }
    return null;
}

