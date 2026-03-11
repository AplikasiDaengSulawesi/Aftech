<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$prod_id = isset($_GET['prod_id']) ? (int)$_GET['prod_id'] : 0;

if ($prod_id > 0) {
    $res = $conn->query("SELECT label_no FROM warehouse_items WHERE production_id = $prod_id");
    $data = [];
    while($row = $res->fetch_assoc()) {
        $data[] = (int)$row['label_no'];
    }
    echo json_encode($data);
} else {
    echo json_encode([]);
}
?>
