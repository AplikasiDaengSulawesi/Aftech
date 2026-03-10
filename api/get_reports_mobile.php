<?php
header("Content-Type: application/json");
include 'config.php';
// File ini khusus untuk Aplikasi Mobile agar tidak bentrok dengan format Web

$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : date('Y-m-d');
$item_filter = isset($_GET['item']) ? $conn->real_escape_string($_GET['item']) : '';

$where = "WHERE 1=1";
if ($item_filter) {
    $where .= " AND p.item = '$item_filter'";
}

$sql = "
    SELECT 
        p.production_date,
        p.batch,
        p.item,
        p.size,
        p.unit,
        p.operator,
        p.machine,
        p.production_time,
        (p.quantity * p.copies) as produced_qty,
        p.copies as copies
    FROM production_labels p
    $where
    ORDER BY p.production_date DESC, p.id DESC
";

$res = $conn->query($sql);
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>