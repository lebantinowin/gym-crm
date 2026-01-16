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

// 💾 Database (XAMPP defaults)
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=gym_crm;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('<h3>DB Connection Failed</h3><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
}