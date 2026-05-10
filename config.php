<?php
// config.php — Core config (DB only). AI keys loaded per-need.

// 🔐 Load .env (safe, no crash)
$env_path = __DIR__ . DIRECTORY_SEPARATOR . '.env';
if (file_exists($env_path)) {
    $lines = @file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
                $key = trim($key);
                $value = trim($value);
                $value = preg_replace('/^([\'"])(.*)\1$/', '$2', $value);
                $_ENV[$key] = $value;
            }
        }
    }
}

// 💾 Database Settings
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'gym_crm');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// 📂 Path Constants
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('UPLOAD_DIR_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('<h3>DB Connection Failed</h3><p>Please ensure your database is running and credentials in .env are correct.</p>');
}