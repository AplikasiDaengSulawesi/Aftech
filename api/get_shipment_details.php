<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die(json_encode(['status' => 'error', 'message' => 'ID tidak valid']));
}

// Header pengiriman
$hdrRes = $conn->query("SELECT id, customer_name, customer_contact, customer_address, shipment_date, shipped_at, shipped_by, total_qty, total_actual_qty, input_method FROM outbound_shipments WHERE id = $id");
$header = ($hdrRes && $hdrRes->num_rows) ? $hdrRes->fetch_assoc() : null;

// Detail per-batch dengan no. label (GROUP_CONCAT) + mesin/shift
$sql = "SELECT p.id as production_id, d.shipment_id, p.batch, p.item, p.size, p.unit,
               p.machine, p.shift, p.quantity as per_paket,
               COUNT(d.id) as label_qty,
               (COUNT(d.id) * p.quantity) as unit_qty,
               GROUP_CONCAT(d.label_no ORDER BY d.label_no ASC SEPARATOR ',') as label_nos
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

echo json_encode(['status' => 'success', 'header' => $header, 'data' => $details]);
?>
