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
        OR b.returned_by LIKE '%$search%'
        OR b.reason LIKE '%$search%'
        OR s.customer_name LIKE '%$search%')";
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

$cond_filter = strtolower(trim((string)($_GET['condition'] ?? '')));
if (in_array($cond_filter, ['utuh', 'rusak'], true)) {
    $where_clauses[] = "b.condition_status = '" . $conn->real_escape_string($cond_filter) . "'";
}

if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start = $conn->real_escape_string($_GET['start_date']);
    $end   = $conn->real_escape_string($_GET['end_date']);
    $where_clauses[] = "DATE(b.returned_at) BETWEEN '$start' AND '$end'";
}

$where = "WHERE " . implode(" AND ", $where_clauses);

$baseSelect = "
    FROM shipment_return_batches b
    JOIN production_labels p ON b.production_id = p.id
    LEFT JOIN outbound_shipments s ON b.shipment_id = s.id
    $where
";

$totalRes = $conn->query("SELECT COUNT(*) AS total $baseSelect");
$totalData = $totalRes ? (int)($totalRes->fetch_assoc()['total'] ?? 0) : 0;
$totalPages = (int)ceil($totalData / $limit);

$statsRes = $conn->query("
    SELECT
        COUNT(*) AS cnt_event,
        COALESCE(SUM(b.label_qty), 0) AS cnt_label_total,
        COALESCE(SUM(b.unit_qty),  0) AS cnt_unit_total,
        COALESCE(SUM(CASE WHEN b.condition_status='utuh'  THEN b.label_qty END), 0) AS cnt_utuh,
        COALESCE(SUM(CASE WHEN b.condition_status='rusak' THEN b.label_qty END), 0) AS cnt_rusak,
        COALESCE(SUM(CASE WHEN b.condition_status='utuh'  THEN b.unit_qty  END), 0) AS cnt_unit_utuh,
        COALESCE(SUM(CASE WHEN b.condition_status='rusak' THEN b.unit_qty  END), 0) AS cnt_unit_rusak,
        COUNT(DISTINCT b.shipment_id)   AS cnt_nota,
        COUNT(DISTINCT b.production_id) AS cnt_batch,
        COUNT(DISTINCT s.customer_name) AS cnt_customer,
        MAX(b.returned_at) AS last_returned_at
    $baseSelect
");
$stats = [
    'total'            => $totalData,
    'cnt_event'        => 0,
    'cnt_label_total'  => 0,
    'cnt_unit_total'   => 0,
    'cnt_utuh'         => 0,
    'cnt_rusak'        => 0,
    'cnt_unit_utuh'    => 0,
    'cnt_unit_rusak'   => 0,
    'cnt_nota'         => 0,
    'cnt_batch'        => 0,
    'cnt_customer'     => 0,
    'last_returned_at' => null,
];
if ($statsRes && $row = $statsRes->fetch_assoc()) {
    $stats['cnt_event']        = (int)$row['cnt_event'];
    $stats['cnt_label_total']  = (int)$row['cnt_label_total'];
    $stats['cnt_unit_total']   = (int)$row['cnt_unit_total'];
    $stats['cnt_utuh']         = (int)$row['cnt_utuh'];
    $stats['cnt_rusak']        = (int)$row['cnt_rusak'];
    $stats['cnt_unit_utuh']    = (int)$row['cnt_unit_utuh'];
    $stats['cnt_unit_rusak']   = (int)$row['cnt_unit_rusak'];
    $stats['cnt_nota']         = (int)$row['cnt_nota'];
    $stats['cnt_batch']        = (int)$row['cnt_batch'];
    $stats['cnt_customer']     = (int)$row['cnt_customer'];
    $stats['last_returned_at'] = $row['last_returned_at'];
}

$sql = "
    SELECT
        b.id,
        b.shipment_id,
        b.production_id,
        b.condition_status,
        b.label_qty,
        b.unit_qty,
        b.reason,
        b.returned_by,
        b.returned_at,
        p.batch,
        p.item,
        p.size,
        p.unit,
        p.machine,
        p.shift,
        s.customer_name,
        s.customer_contact,
        s.shipment_date,
        (SELECT GROUP_CONCAT(sr.label_no ORDER BY sr.label_no ASC SEPARATOR ',')
           FROM shipment_returns sr
          WHERE sr.return_batch_id = b.id) AS label_nos
    $baseSelect
    ORDER BY b.returned_at DESC, b.id DESC
    LIMIT $offset, $limit
";

$res = $conn->query($sql);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $ts = strtotime($row['returned_at']);
        $row['returned_date'] = $ts ? date('d/m/Y', $ts) : '-';
        $row['returned_time'] = $ts ? date('H:i:s', $ts) : '-';
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
