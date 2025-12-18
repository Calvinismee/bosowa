<?php
// config/database.php

$host = 'localhost';
$db   = 'bosowa'; 
$user = 'postgres';      // Sesuaikan dengan username connection masing-masing
$pass = 'postgres';      // Sesuaikan dengan password connection masing-masing
$port = "5432";

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