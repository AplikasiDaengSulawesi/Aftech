<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_aftech";

try {
    // Koneksi awal tanpa dbname untuk memastikan database ada
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");

    echo "<h2>Setup Pengguna AFTECH...</h2>";

    // 1. Buat Tabel Users Jika Belum Ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        password VARCHAR(255),
        full_name VARCHAR(100),
        role ENUM('admin', 'qc', 'gudang') DEFAULT 'gudang',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "- Struktur tabel 'users' siap.<br>";

    // 2. Data User Default (Password: username + 123)
    $default_users = [
        [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Utama',
            'role' => 'admin'
        ],
        [
            'username' => 'qc',
            'password' => password_hash('qc123', PASSWORD_DEFAULT),
            'full_name' => 'Tim Quality Control',
            'role' => 'qc'
        ],
        [
            'username' => 'gudang',
            'password' => password_hash('gudang123', PASSWORD_DEFAULT),
            'full_name' => 'Staff Gudang',
            'role' => 'gudang'
        ]
    ];

    foreach ($default_users as $u) {
        // Cek apakah username sudah ada agar tidak menimpa data yang sudah diubah
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$u['username']]);
        
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$u['username'], $u['password'], $u['full_name'], $u['role']]);
            echo "<span style='color:green;'>- User '{$u['username']}' ({$u['role']}) berhasil dibuat.</span><br>";
        } else {
            echo "<span style='color:orange;'>- User '{$u['username']}' sudah ada (dilewati).</span><br>";
        }
    }

    echo "<br><strong>Selesai!</strong> Tabel sudah dibuat dan user default telah disiapkan.";

} catch (PDOException $e) {
    die("<br><strong style='color:red;'>Gagal Setup User: </strong>" . $e->getMessage());
}
?>
