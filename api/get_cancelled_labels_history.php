<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$limit  = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];

if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where_clauses[] = "(p.batch LIKE '%$search%'
        OR p.item LIKE '%$search%'
        OR p.size LIKE '%$search%'
        OR c.cancelled_by LIKE '%$search%'
        OR c.reason LIKE '%$search%'
        OR c.label_no LIKE '%$search%')";
}

if (!empty($_GET['item'])) {
    $item_filter = $conn->real_escape_string($_GET['item']);
    $where_clauses[] = "p.item = '$item_filter'";
}

if (!empty($_GET['size']) && $_GET['size'] !== 'Custom') {
    $size_filter = $conn->real_escape_string($_GET['size']);
    $where_clauses[] = "p.size = '$size_filter'";
}

if (!empty($_GET['batch'])) {
    $batch_filter = $conn->real_escape_string($_GET['batch']);
    $where_clauses[] = "p.batch LIKE '%$batch_filter%'";
}

if (!empty($_GET['device_id'])) {
    $device_filter = $conn->real_escape_string($_GET['device_id']);
    $where_clauses[] = "c.device_id LIKE '%$device_filter%'";
}

$category_filter = strtolower(trim((string)($_GET['category'] ?? '')));
$valid_categories = ['production', 'warehouse'];
if (in_array($category_filter, $valid_categories, true)) {
    $where_clauses[] = "c.category = '" . $conn->real_escape_string($category_filter) . "'";
}

if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start = $conn->real_escape_string($_GET['start_date']);
    $end   = $conn->real_escape_string($_GET['end_date']);
    $where_clauses[] = "DATE(c.cancelled_at) BETWEEN '$start' AND '$end'";
}

$where = "WHERE " . implode(" AND ", $where_clauses);

$baseSelect = "
    FROM cancelled_labels c
    JOIN production_labels p ON c.production_id = p.id
    LEFT JOIN api_keys d ON d.device_uuid = c.device_id
    $where
";

$totalRes = $conn->query("SELECT COUNT(*) AS total $baseSelect");
$totalData = $totalRes ? (int)($totalRes->fetch_assoc()['total'] ?? 0) : 0;
$totalPages = (int)ceil($totalData / $limit);

$statsRes = $conn->query("
    SELECT
        SUM(c.category = 'production') AS cnt_production,
        SUM(c.category = 'warehouse')  AS cnt_warehouse,
        COUNT(DISTINCT c.device_id)    AS cnt_devices
    $baseSelect
");
$stats = [
    'total'          => $totalData,
    'cnt_production' => 0,
    'cnt_warehouse'  => 0,
    'cnt_devices'    => 0,
];
if ($statsRes && $row = $statsRes->fetch_assoc()) {
    $stats['cnt_production'] = (int)$row['cnt_production'];
    $stats['cnt_warehouse']  = (int)$row['cnt_warehouse'];
    $stats['cnt_devices']    = (int)$row['cnt_devices'];
}

$sql = "
    SELECT
        c.id,
        c.production_id,
        c.label_no,
        c.category,
        c.reason,
        c.cancelled_by,
        c.cancelled_at,
        c.device_id,
        d.device_name,
        p.batch,
        p.item,
        p.size,
        p.unit,
        p.machine,
        p.shift
    $baseSelect
    ORDER BY c.cancelled_at DESC
    LIMIT $offset, $limit
";

$res = $conn->query($sql);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $ts = strtotime($row['cancelled_at']);
        $row['cancelled_date'] = $ts ? date('d/m/Y', $ts) : '-';
        $row['cancelled_time'] = $ts ? date('H:i:s', $ts) : '-';
        $data[] = $row;
    }
}

echo json_encode([
    'data'         => $data,
    'total'        => $totalData,
    'pages'        => $totalPages,
    'current_page' => $page,
    'stats'        => $stats,
]);
