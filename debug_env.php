<?php
require_once 'config.php';
echo "<h2>Loaded ENV variables:</h2><pre>";
foreach ($_ENV as $k => $v) {
    if (strpos($k, 'KEY') !== false) {
        echo "$k = " . substr($v, 0, 8) . "...\n";
    }
}
echo "</pre>";