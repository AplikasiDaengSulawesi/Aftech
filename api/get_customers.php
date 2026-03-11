<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$sql = "SELECT * FROM master_customers";
if (!empty($q)) {
    $sql .= " WHERE name LIKE '%$q%' OR contact LIKE '%$q%'";
}
$sql .= " ORDER BY total_orders DESC, name ASC LIMIT 10";

$res = $conn->query($sql);
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>