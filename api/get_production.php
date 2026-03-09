<?php
include 'config.php';
header('Content-Type: application/json');

$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search  = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$item    = isset($_GET['item']) ? $conn->real_escape_string($_GET['item']) : '';
$machine = isset($_GET['machine']) ? $conn->real_escape_string($_GET['machine']) : '';
$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
$end_date   = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';

$where_clauses = ["1=1"];
if ($search) {
    $where_clauses[] = "(p.batch LIKE '%$search%' 
                        OR p.item LIKE '%$search%' 
                        OR p.machine LIKE '%$search%' 
                        OR p.operator LIKE '%$search%' 
                        OR p.qc LIKE '%$search%' 
                        OR p.device_model LIKE '%$search%')";
}
if ($item) $where_clauses[] = "p.item = '$item'";
if ($machine) $where_clauses[] = "p.machine = '$machine'";
if ($start_date && $end_date) $where_clauses[] = "p.production_date BETWEEN '$start_date' AND '$end_date'";

$where = "WHERE " . implode(" AND ", $where_clauses);

// 1. Ambil Total Data
$totalRes = $conn->query("SELECT COUNT(*) as total FROM production_labels p $where");
$totalData = $totalRes->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalData / $limit);

// 1.5 Ambil Global Stats (Mengikuti Filter Tabel, namun jika tidak ada filter, ikuti saja filter bawaan atau tampilkan semua sesuai query $where)
$statsSql = "
    SELECT
        COUNT(p.id) as total_batch,
        COALESCE(SUM(p.quantity * p.copies), 0) as total_qty,
        COALESCE(SUM(p.copies), 0) as total_copies,
        COALESCE(SUM( (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) * p.quantity ), 0) as total_in_warehouse
    FROM production_labels p
    $where
";
$statsRes = $conn->query($statsSql);
$stats = $statsRes->fetch_assoc();

$total_batch = (int)($stats['total_batch'] ?? 0);
$total_qty = (int)($stats['total_qty'] ?? 0);
$total_in_warehouse = (int)($stats['total_in_warehouse'] ?? 0);
$total_belum_scan = max(0, $total_qty - $total_in_warehouse);

// Set locale format for bulan in Indonesia
$bulan_indonesia = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$bulan_ini = $bulan_indonesia[date('m')] . ' ' . date('Y');

// Jika ada filter tanggal spesifik dari pengguna, hapus label "Bulan Ini" dan ganti dengan "Filtered"
if ($start_date || $end_date || $search || $item || $machine) {
    $bulan_ini = 'Hasil Filter';
}

// 2. Ambil Data dengan Limit & Join ke Warehouse (Live Scanned Count)
$sql = "SELECT p.*,
               (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) as scanned,
               (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) as in_warehouse
        FROM production_labels p
        $where
        ORDER BY p.id DESC
        LIMIT $offset, $limit";

$res = $conn->query($sql);
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'data' => $data,
    'total' => (int)$totalData,
    'pages' => (int)$totalPages,
    'current_page' => (int)$page,
    'stats' => [
        'total_batch' => $total_batch,
        'total_qty' => $total_qty,
        'belum_scan' => $total_belum_scan,
        'in_warehouse' => $total_in_warehouse,
        'bulan' => $bulan_ini
    ]
]);?>