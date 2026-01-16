<?php
echo "<h2>PHP Config Check</h2>";
echo "<p><b>allow_url_fopen:</b> " . (ini_get('allow_url_fopen') ? '✅ ON' : '❌ OFF') . "</p>";
echo "<p><b>cURL loaded:</b> " . (extension_loaded('curl') ? '✅ YES' : '❌ NO') . "</p>";