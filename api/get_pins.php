<?php
include 'config.php';
header('Content-Type: application/json');

$res = $conn->query("SELECT id, pin_code, note FROM app_config ORDER BY id ASC");
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>
