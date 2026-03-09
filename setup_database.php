<?php
// AFTECH DATABASE SETUP v6.0 - ULTIMATE MERGE
include 'api/config.php';

$conn->query("SET FOREIGN_KEY_CHECKS = 0");

echo "<h2 style='color:#FE634E;'>AFTECH SYSTEM SETUP v6.0</h2>";

// 1. HAPUS TABEL NON-STANDAR (Hati-hati!)
$wrong_tables = ['items', 'machines', 'shifts', 'units', 'sizes', 'quantities'];
foreach ($wrong_tables as $table) {
    $conn->query("DROP TABLE IF EXISTS $table");
}

// 2. TABEL USER & LOG (Fondasi Sistem)
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(100),
    role ENUM('admin', 'qc', 'gudang') DEFAULT 'gudang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 3. TABEL MASTER DATA (Relational Structure)
$conn->query("CREATE TABLE IF NOT EXISTS master_units (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(20) NOT NULL UNIQUE)");
$conn->query("CREATE TABLE IF NOT EXISTS master_shifts (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(20) NOT NULL UNIQUE)");
$conn->query("CREATE TABLE IF NOT EXISTS master_machines (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    name VARCHAR(50) NOT NULL UNIQUE, 
    status ENUM('active', 'maintenance') DEFAULT 'active'
)");

$conn->query("CREATE TABLE IF NOT EXISTS master_items (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    name VARCHAR(100) NOT NULL UNIQUE, 
    unit_id INT,
    FOREIGN KEY (unit_id) REFERENCES master_units(id) ON DELETE SET NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS master_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    item_id INT, 
    size_value VARCHAR(20), 
    FOREIGN KEY (item_id) REFERENCES master_items(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS master_quantities (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    machine_id INT, 
    qty_value VARCHAR(20), 
    FOREIGN KEY (machine_id) REFERENCES master_machines(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS master_templates (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    template_name VARCHAR(100) NOT NULL UNIQUE, 
    item VARCHAR(100), 
    size VARCHAR(20), 
    unit VARCHAR(20), 
    machine VARCHAR(50), 
    shift VARCHAR(20), 
    quantity VARCHAR(20)
)");

// 4. TABEL PRODUKSI (Pusat Data)
$conn->query("CREATE TABLE IF NOT EXISTS production_labels (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    batch VARCHAR(100) UNIQUE NOT NULL, 
    item VARCHAR(100), 
    size VARCHAR(20), 
    unit VARCHAR(20), 
    machine VARCHAR(50), 
    shift VARCHAR(20), 
    quantity VARCHAR(20), 
    operator VARCHAR(100), 
    qc VARCHAR(100), 
    copies INT DEFAULT 0, 
    production_date DATE, 
    production_time TIME, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 5. TABEL QC SCAN (Logic Counter & Serialized)
$conn->query("CREATE TABLE IF NOT EXISTS qc_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_id INT UNIQUE,
    total_scanned INT DEFAULT 0,
    last_scanned_by VARCHAR(100),
    last_scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (production_id) REFERENCES production_labels(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS qc_scan_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_id INT,
    label_no INT,
    scanned_by VARCHAR(100),
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (production_id, label_no),
    FOREIGN KEY (production_id) REFERENCES production_labels(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS app_config (
    config_key VARCHAR(50) PRIMARY KEY, 
    config_value VARCHAR(255)
)");

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "✔ Semua struktur tabel berhasil disinkronisasi.<br>";

// 6. SEEDING DATA (INSERT IGNORE agar tidak duplikat)
$pass = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO users (username, password, full_name, role) VALUES ('admin', '$pass', 'Administrator', 'admin')");
$conn->query("INSERT IGNORE INTO master_units (name) VALUES ('ML'), ('GR'), ('PCS'), ('LITER')");
$conn->query("INSERT IGNORE INTO master_shifts (name) VALUES ('SHIFT A'), ('SHIFT B')");
$conn->query("INSERT IGNORE INTO master_machines (name, status) VALUES ('MACHINE 1', 'active'), ('MACHINE 2', 'active')");
$conn->query("INSERT IGNORE INTO app_config (config_key, config_value) VALUES ('reset_pin', '1234')");

// Item & Template Seeding
$conn->query("INSERT IGNORE INTO master_items (id, name, unit_id) VALUES (1, 'BOTOL', 1), (2, 'CUP', 1), (3, 'SEDOTAN', 3)");
$conn->query("INSERT IGNORE INTO master_sizes (item_id, size_value) VALUES (1, '330'), (1, '600'), (2, '240'), (3, '1')");
$conn->query("REPLACE INTO master_templates (template_name, item, size, unit, machine, shift, quantity) VALUES 
('PRODUKSI BOTOL 600ML', 'BOTOL', '600', 'ML', 'MACHINE 1', 'SHIFT A', '1200'),
('PRODUKSI CUP 240ML', 'CUP', '240', 'ML', 'MACHINE 2', 'SHIFT B', '48')");

echo "<span style='color:green;'><b>Setup Selesai!</b> Database siap digunakan untuk Input Produksi dan QC Checker.</span>";
$conn->close();
?>
