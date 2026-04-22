<?php
require __DIR__ . '/db_credentials.php';

try {
    // Koneksi tanpa DB dulu untuk pengecekan di seeder
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Gunakan database aftech
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $db");
    $pdo->exec("USE $db");
    
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>
