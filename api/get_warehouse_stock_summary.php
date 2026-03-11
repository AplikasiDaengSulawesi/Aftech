<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];

if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where_clauses[] = "(p.batch LIKE '%$search%' OR p.item LIKE '%$search%' OR p.size LIKE '%$search%')";
}

if (!empty($_GET['item'])) {
    $item_filter = $conn->real_escape_string($_GET['item']);
    $where_clauses[] = "p.item = '$item_filter'";
}

if (!empty($_GET['size']) && $_GET['size'] !== 'Custom') {
    $size_filter = $conn->real_escape_string($_GET['size']);
    $where_clauses[] = "p.size = '$size_filter'";
}

if (!empty($_GET['machine'])) {
    $machine_filter = $conn->real_escape_string($_GET['machine']);
    $where_clauses[] = "p.machine = '$machine_filter'";
}

if (!empty($_GET['shift'])) {
    $shift_filter = $conn->real_escape_string($_GET['shift']);
    $where_clauses[] = "p.shift = '$shift_filter'";
}

if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start = $conn->real_escape_string($_GET['start_date']);
    $end = $conn->real_escape_string($_GET['end_date']);
    $where_clauses[] = "DATE(w.transferred_at) BETWEEN '$start' AND '$end'";
}

$where = "WHERE " . implode(" AND ", $where_clauses);

$bulan_indonesia = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$bulan_ini = '(' . $bulan_indonesia[date('m')] . ' ' . date('Y') . ')';

if (!empty($_GET['start_date']) || !empty($_GET['end_date']) || !empty($_GET['search']) || !empty($_GET['item']) || !empty($_GET['size']) || !empty($_GET['machine']) || !empty($_GET['shift'])) {
    $bulan_ini = 'Hasil Filter';
}

$statsWhere = $where;
if ($bulan_ini !== 'Hasil Filter') {
    $currentMonth = date('m');
    $currentYear = date('Y');
    $statsWhere = "WHERE MONTH(w.transferred_at) = '$currentMonth' AND YEAR(w.transferred_at) = '$currentYear'";
}

$statsSql = "
    SELECT p.id, p.copies, p.quantity,
           COUNT(w.id) as total_in_warehouse,
           (SELECT COUNT(*) FROM distributor_shipments WHERE production_id = p.id) as total_shipped
    FROM warehouse_items w
    JOIN production_labels p ON w.production_id = p.id
    $statsWhere
    GROUP BY p.id
";

$statsRes = $conn->query($statsSql);
$total_batch = 0;
$total_stok = 0; // Net Stock (Verified - Shipped)
$total_verified = 0; // Total labels that entered
$total_kapasitas = 0;
$total_shipped = 0;

if ($statsRes) {
    while($row = $statsRes->fetch_assoc()) {
        $total_batch++;
        $verified = (int)$row['total_in_warehouse'];
        $shipped = (int)$row['total_shipped'];
        $total_verified += $verified;
        $total_stok += ($verified - $shipped);
        $total_kapasitas += (int)$row['copies'];
        $total_shipped += $shipped;
    }
}

// Hitung total baris untuk paginasi (menggunakan subquery karena GROUP BY)
$totalSql = "SELECT COUNT(DISTINCT p.id) as total
             FROM warehouse_items w
             JOIN production_labels p ON w.production_id = p.id
             $where";
$totalRes = $conn->query($totalSql);
$totalData = $totalRes->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalData / $limit);

// Menghitung jumlah label per batch di gudang dan mengambil pengirim terakhir
$sql = "SELECT p.id as production_id, p.batch, p.item, p.copies, p.unit, p.size, p.quantity, p.machine, p.shift,
               COUNT(w.id) as total_in_warehouse,
               (SELECT COUNT(*) FROM distributor_shipments WHERE production_id = p.id) as total_shipped_labels,
               MAX(w.transferred_at) as last_entry,
               (SELECT transferred_by FROM warehouse_items WHERE production_id = p.id ORDER BY transferred_at DESC LIMIT 1) as pengirim
        FROM warehouse_items w
        JOIN production_labels p ON w.production_id = p.id
        $where
        GROUP BY p.id
        ORDER BY last_entry DESC
        LIMIT $offset, $limit";

$res = $conn->query($sql);
$data = [];

while($row = $res->fetch_assoc()) {
    $row['last_entry_time'] = date('H:i:s', strtotime($row['last_entry']));
    $row['last_entry_date'] = date('d/m/Y', strtotime($row['last_entry']));
    $data[] = $row;
}

echo json_encode([
    'data' => $data,
    'total' => (int)$totalData,
    'pages' => (int)$totalPages,
    'current_page' => (int)$page,
    'stats' => [
        'total_batch' => $total_batch,
        'total_verified' => $total_verified,
        'total_stok' => $total_stok, // Net stock (Verified - Shipped)
        'total_kapasitas' => $total_kapasitas,
        'total_shipped' => $total_shipped,
        'bulan' => $bulan_ini
    ]
]);
?>
