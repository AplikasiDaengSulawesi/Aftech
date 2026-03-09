<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_aftech";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Setup Modul Gudang AFTECH...</h2>";

    // 1. Tabel Transfer Gudang
    $pdo->exec("CREATE TABLE IF NOT EXISTS warehouse_transfers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        production_id INT UNIQUE, 
        transferred_by VARCHAR(100),
        received_by VARCHAR(100) DEFAULT 'PENDING',
        status ENUM('IN_TRANSIT', 'RECEIVED') DEFAULT 'IN_TRANSIT',
        transferred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (production_id) REFERENCES production_labels(id) ON DELETE CASCADE
    )");
    echo "<span style='color:green;'>- Tabel 'warehouse_transfers' berhasil disiapkan.</span><br>";

    echo "<br><strong>Selesai!</strong> Modul Gudang sudah siap dihubungkan dengan Produksi.";

} catch (PDOException $e) {
    die("<br><strong style='color:red;'>Gagal Setup Gudang: </strong>" . $e->getMessage());
}
?>
