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

$input_method_filter = strtolower(trim((string)($_GET['input_method'] ?? '')));
$valid_input_methods = ['scan', 'manual', 'hybrid'];
$having = '';
if (in_array($input_method_filter, $valid_input_methods, true)) {
    $having = "HAVING batch_input_method = '" . $conn->real_escape_string($input_method_filter) . "'";
}

$where = "WHERE " . implode(" AND ", $where_clauses);

$bulan_ini = 'Keseluruhan';

if (
    !empty($_GET['start_date']) || !empty($_GET['end_date']) || !empty($_GET['search']) ||
    !empty($_GET['item']) || !empty($_GET['size']) || !empty($_GET['machine']) ||
    !empty($_GET['shift']) || $input_method_filter !== ''
) {
    $bulan_ini = 'Hasil Filter';
}

$statsWhere = $where;

$batch_method_sql = "CASE
    WHEN COUNT(DISTINCT COALESCE(NULLIF(w.input_method, ''), 'scan')) > 1 THEN 'hybrid'
    ELSE MAX(COALESCE(NULLIF(w.input_method, ''), 'scan'))
END";

$baseStatsSql = "
    SELECT
        p.id,
        p.copies,
        p.quantity,
        COUNT(w.id) as total_in_warehouse,
        (SELECT COUNT(*) FROM distributor_shipments WHERE production_id = p.id) as total_shipped,
        $batch_method_sql as batch_input_method
    FROM warehouse_items w
    JOIN production_labels p ON w.production_id = p.id
    $statsWhere
    GROUP BY p.id
    $having
";

$statsRes = $conn->query($baseStatsSql);
$total_batch = 0;
$total_stok = 0;
$total_verified = 0;
$total_kapasitas = 0;
$total_shipped = 0;

$baseStockRes = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key='warehouse_base_stock'");
$base_stock_offset = ($baseStockRes && $row = $baseStockRes->fetch_assoc()) ? (int)$row['setting_value'] : 0;

if ($statsRes) {
    while ($row = $statsRes->fetch_assoc()) {
        $total_batch++;
        $verified = (int)$row['total_in_warehouse'];
        $shipped = (int)$row['total_shipped'];
        $total_verified += $verified;
        $total_stok += ($verified - $shipped);
        $total_kapasitas += (int)$row['copies'];
        $total_shipped += $shipped;
    }
}

// Tambahkan offset virtual ke patokan sisa gudang
$total_stok += $base_stock_offset;

$baseListSql = "
    SELECT
        p.id as production_id,
        p.batch,
        p.item,
        p.copies,
        p.unit,
        p.size,
        p.quantity,
        p.machine,
        p.shift,
        COUNT(w.id) as total_in_warehouse,
        (SELECT COUNT(*) FROM distributor_shipments WHERE production_id = p.id) as total_shipped_labels,
        MAX(w.transferred_at) as last_entry,
        (SELECT transferred_by FROM warehouse_items WHERE production_id = p.id ORDER BY transferred_at DESC LIMIT 1) as pengirim,
        $batch_method_sql as batch_input_method
    FROM warehouse_items w
    JOIN production_labels p ON w.production_id = p.id
    $where
    GROUP BY p.id
    $having
";

$totalSql = "SELECT COUNT(*) as total FROM ($baseListSql) warehouse_batches";
$totalRes = $conn->query($totalSql);
$totalData = $totalRes ? (int)($totalRes->fetch_assoc()['total'] ?? 0) : 0;
$totalPages = $limit > 0 ? (int)ceil($totalData / $limit) : 1;

$sql = $baseListSql . " ORDER BY last_entry DESC LIMIT $offset, $limit";
$res = $conn->query($sql);
$data = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['last_entry_time'] = date('H:i:s', strtotime($row['last_entry']));
        $row['last_entry_date'] = date('d/m/Y', strtotime($row['last_entry']));
        $data[] = $row;
    }
}

// Global stats (semua data, tanpa filter bulan)
$globalBaseStatsSql = "
    SELECT
        p.id,
        COUNT(w.id) as total_in_warehouse,
        (SELECT COUNT(*) FROM distributor_shipments WHERE production_id = p.id) as total_shipped,
        $batch_method_sql as batch_input_method
    FROM warehouse_items w
    JOIN production_labels p ON w.production_id = p.id
    $where
    GROUP BY p.id
    $having
";

$globalStatsRes = $conn->query($globalBaseStatsSql);
$global_batch    = 0;
$global_verified = 0;
$global_stok     = 0;
$global_shipped  = 0;

if ($globalStatsRes) {
    while ($row = $globalStatsRes->fetch_assoc()) {
        $global_batch++;
        $verified = (int)$row['total_in_warehouse'];
        $shipped  = (int)$row['total_shipped'];
        $global_verified += $verified;
        $global_stok     += ($verified - $shipped);
        $global_shipped  += $shipped;
    }
}

// Tambahkan offset virtual untuk global
$global_stok += $base_stock_offset;

echo json_encode([
    'data'         => $data,
    'total'        => (int)$totalData,
    'pages'        => (int)$totalPages,
    'current_page' => (int)$page,
    'stats' => [
        'total_batch'    => $total_batch,
        'total_verified' => $total_verified,
        'total_stok'     => $total_stok,
        'total_shipped'  => $total_shipped,
        'bulan'          => $bulan_ini,
        'base_stock_offset' => $base_stock_offset,
        // Global (semua data)
        'global_batch'   => $global_batch,
        'global_verified' => $global_verified,
        'global_stok'    => $global_stok,
        'global_shipped' => $global_shipped,
    ]
]);
