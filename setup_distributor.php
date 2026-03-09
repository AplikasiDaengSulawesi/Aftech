<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_aftech";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabel Pengiriman Distributor
    $pdo->exec("CREATE TABLE IF NOT EXISTS distributor_shipments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        production_id INT NOT NULL,
        label_no INT NOT NULL,
        distributor_name VARCHAR(150) NOT NULL,
        shipped_by VARCHAR(100) NOT NULL,
        shipped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (production_id) REFERENCES production_labels(id) ON DELETE CASCADE,
        UNIQUE KEY unique_label (production_id, label_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    echo "<br><strong>Selesai!</strong> Tabel distributor_shipments berhasil dibuat.";

} catch (PDOException $e) {
    die("<br><strong style='color:red;'>Gagal Setup: </strong>" . $e->getMessage());
}
?>