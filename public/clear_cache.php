<?php
// Force OPcache reset
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<h1>✅ OPcache Reset Successfully</h1>";
    echo "<p>The PHP bytecode cache has been cleared. Changes should be visible now.</p>";
} else {
    echo "<h1>⚠️ OPcache Not Enabled</h1>";
    echo "<p>Function <code>opcache_reset</code> does not exist.</p>";
}

// Also try to clear realpath cache
clearstatcache(true);
echo "<p>Realpath cache cleared.</p>";
