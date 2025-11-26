<?php
// config/database.php

$host = 'localhost';
$db   = 'bosowa'; // Ganti dengan nama DB kamu
$user = 'postgres';           // Default user postgres
$pass = 'admin';      // Password postgres kamu
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