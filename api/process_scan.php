<?php
session_start();
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$qr_input = isset($_GET['batch']) ? $conn->real_escape_string($_GET['batch']) : '';
$scanned_by = $_SESSION['full_name'] ?? 'QC User';

if (empty($qr_input)) {
    echo json_encode(['status' => 'error', 'message' => 'Data QR Kosong!']);
    exit;
}

// 1. Bedah QR (Format: {label_no}-{batch})
$parts = explode('-', $qr_input, 2);
if (count($parts) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Format QR tidak valid (Gunakan No-Batch)']);
    exit;
}

$label_no = (int)$parts[0];
$batch_id = $parts[1];

// 2. Cari Data Produksi
$sql = "SELECT id, item, copies, quantity, unit FROM production_labels WHERE batch = '$batch_id' LIMIT 1";
$res = $conn->query($sql);

if ($res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Batch tidak terdaftar!']);
    exit;
}

$prod = $res->fetch_assoc();
$prod_pk = $prod['id'];
$limit = (int)$prod['copies'];

// 3. Validasi Rentang Nomor
if ($label_no < 1 || $label_no > $limit) {
    echo json_encode(['status' => 'error', 'message' => "Dus #$label_no di luar kuota ($limit)"]);
    exit;
}

// 4. Cek Duplikasi Dus di Gudang (Bukan lagi di QC)
$check = $conn->query("SELECT id FROM warehouse_items WHERE production_id = $prod_pk AND label_no = $label_no");
if ($check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => "Dus #$label_no sudah ada di Gudang!"]);
    exit;
}

// 5. Simpan Langsung ke Gudang
try {
    $conn->query("INSERT INTO warehouse_items (production_id, label_no, transferred_by) VALUES ($prod_pk, $label_no, '$scanned_by')");

    // Ambil list semua dus yang sudah di Gudang untuk dirender ulang di Grid UI Scanner
    $scanned_list = [];
    $res_list = $conn->query("SELECT label_no FROM warehouse_items WHERE production_id = $prod_pk");
    while($r = $res_list->fetch_assoc()) $scanned_list[] = (int)$r['label_no'];

    echo json_encode([
        'status' => 'success',
        'message' => "Dus Masuk (#$label_no)",
        'data' => [
            'production_id' => $prod_pk,
            'item' => $prod['item'],
            'batch' => $batch_id,
            'copies' => $limit,
            'scanned_list' => $scanned_list,
            'last_no' => $label_no,
            'progress' => count($scanned_list) . " / $limit"
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal simpan ke gudang: ' . $e->getMessage()]);
}
?>
