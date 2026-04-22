<?php
header("Content-Type: application/json");
include '../config.php';
verify_api_access();

$start_date   = isset($_GET['start_date'])   ? $conn->real_escape_string($_GET['start_date']) : date('Y-m-01');
$end_date     = isset($_GET['end_date'])     ? $conn->real_escape_string($_GET['end_date'])   : date('Y-m-d');
$item_filter  = isset($_GET['item'])         ? $conn->real_escape_string($_GET['item'])       : '';
$device_id    = isset($_GET['device_id'])    ? $conn->real_escape_string(trim($_GET['device_id']))    : '';
$device_model = isset($_GET['device_model']) ? $conn->real_escape_string(trim($_GET['device_model'])) : '';

$where = "WHERE p.production_date BETWEEN '$start_date' AND '$end_date'";
if ($item_filter !== '')  { $where .= " AND p.item = '$item_filter'"; }
if ($device_id !== '')    { $where .= " AND p.device_id = '$device_id'"; }
if ($device_model !== '') { $where .= " AND p.device_model = '$device_model'"; }

$sql = "
    SELECT
        p.production_date,
        p.batch,
        p.item,
        p.size,
        p.unit,
        p.operator,
        p.qc,
        p.machine,
        p.shift,
        p.production_time,
        p.device_model,
        p.device_id,
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