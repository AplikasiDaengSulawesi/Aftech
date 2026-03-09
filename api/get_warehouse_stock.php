<?php
include 'config.php';
header('Content-Type: application/json');

$sql = "SELECT w.*, p.batch, p.item, p.copies, p.quantity, p.unit, p.size, IFNULL(q.total_scanned, 0) as scanned 
        FROM warehouse_transfers w 
        JOIN production_labels p ON w.production_id = p.id 
        LEFT JOIN qc_scans q ON p.id = q.production_id 
        ORDER BY w.transferred_at DESC";

$res = $conn->query($sql);
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>
