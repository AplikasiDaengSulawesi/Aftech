<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_aftech";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Setup Modul QC AFTECH (Optimized)...</h2>";

    // 1. Buat Tabel QC Scans (Summary Style)
    // Satu production_id hanya satu baris, menggunakan counter
    $pdo->exec("CREATE TABLE IF NOT EXISTS qc_scans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        production_id INT UNIQUE, 
        total_scanned INT DEFAULT 0,
        last_scanned_by VARCHAR(100),
        last_scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (production_id) REFERENCES production_labels(id) ON DELETE CASCADE
    )");
    echo "<span style='color:green;'>- Struktur tabel 'qc_scans' berhasil dioptimasi dengan Relasi ID.</span><br>";

    echo "<br><strong>Selesai!</strong> Sekarang data scan akan tercatat secara akumulatif (lebih hemat data).";

} catch (PDOException $e) {
    die("<br><strong style='color:red;'>Gagal Setup QC: </strong>" . $e->getMessage());
}
?>
