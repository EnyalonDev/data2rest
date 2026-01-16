<?php
/**
 * add_phpdoc.php
 *
 * Scans all controller files under src/Modules and adds generic PHPDoc blocks
 * to classes and public methods if they are missing.
 */

$baseDir = __DIR__ . '/src/Modules';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));

foreach ($iterator as $file) {
    if ($file->isFile() && substr($file->getFilename(), -14) === 'Controller.php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $modified = false;
        // Find class definition line
        foreach ($lines as $i => $line) {
            if (preg_match('/class\s+(\w+)\s+extends\s+BaseController/', $line, $matches)) {
                $className = $matches[1];
                // Check for PHPDoc before class
                $prevLine = $i - 1;
                $hasDoc = false;
                while ($prevLine >= 0 && trim($lines[$prevLine]) === '') {
                    $prevLine--;
                }
                if ($prevLine >= 0 && strpos(trim($lines[$prevLine]), '/**') === 0) {
                    $hasDoc = true;
                }
                if (!$hasDoc) {
                    $doc = [];
                    $doc[] = '/**';
                    $doc[] = " * $className Controller";
                    $doc[] = ' *';
                    $doc[] = ' * Core Features: TODO';
                    $doc[] = ' *';
                    $doc[] = ' * Security: Requires login, permission checks as implemented.';
                    $doc[] = ' *';
                    $doc[] = " * @package App\\Modules\\" . implode('\\', array_slice(explode('\\', $matches[0]), 0, -1));
                    $doc[] = ' * @author DATA2REST Development Team';
                    $doc[] = ' * @version 1.0.0';
                    $doc[] = ' */';
                    array_splice($lines, $i, 0, $doc);
                    $modified = true;
                    $i += count($doc); // adjust index
                }
                // Add method docs for public methods without doc
                for ($j = $i + 1; $j < count($lines); $j++) {
                    $l = $lines[$j];
                    if (preg_match('/public\s+function\s+(\w+)\s*\(/', $l, $m)) {
                        $method = $m[1];
                        // check preceding lines for doc
                        $k = $j - 1;
                        $hasMethodDoc = false;
                        while ($k >= 0 && trim($lines[$k]) === '') {
                            $k--;
                        }
                        if ($k >= 0 && strpos(trim($lines[$k]), '/**') === 0) {
                            $hasMethodDoc = true;
                        }
                        if (!$hasMethodDoc) {
                            $methodDoc = [];
                            $methodDoc[] = '/**';
                            $methodDoc[] = " * $method method";
                            $methodDoc[] = ' *';
                            $methodDoc[] = ' * @return void';
                            $methodDoc[] = ' */';
                            array_splice($lines, $j, 0, $methodDoc);
                            $modified = true;
                            $j += count($methodDoc);
                        }
                    }
                }
                break; // done with this file
            }
        }
        if ($modified) {
            file_put_contents($path, implode("\n", $lines));
            echo "Updated $path\n";
        }
    }
}
?>