<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die(json_encode(['status' => 'error', 'message' => 'ID tidak valid']));
}

// Tarik data detail per-batch
$sql = "SELECT p.id as production_id, d.shipment_id, p.batch, p.item, p.size, p.unit, p.quantity as per_paket, COUNT(d.id) as label_qty, (COUNT(d.id) * p.quantity) as unit_qty
        FROM distributor_shipments d
        JOIN production_labels p ON d.production_id = p.id
        WHERE d.shipment_id = $id
        GROUP BY p.id
        ORDER BY p.item ASC, p.batch ASC";
        
$res = $conn->query($sql);
$details = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $details[] = $row;
    }
}

echo json_encode(['status' => 'success', 'data' => $details]);
?>