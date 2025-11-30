<?php
// config/database.php

// Helper function to parse .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/../.env');

// Check for DATABASE_URL (Railway/Heroku style)
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    $url = parse_url($databaseUrl);
    $host = $url['host'];
    $port = $url['port'] ?? 5432;
    $user = $url['user'];
    $pass = $url['pass'];
    $db   = ltrim($url['path'], '/');
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $db   = getenv('DB_DATABASE') ?: 'bosowa';
    $user = getenv('DB_USERNAME') ?: 'postgres';
    $pass = getenv('DB_PASSWORD') ?: 'postgres';
    $port = getenv('DB_PORT') ?: '5432';
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    // Menggunakan PDO agar aman dan bisa koneksi ke PostgreSQL
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>