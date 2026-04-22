<?php
include '../config.php';
verify_api_access();
header('Content-Type: application/json');

$production_id = isset($_GET['production_id']) ? (int)$_GET['production_id'] : 0;
$batch         = isset($_GET['batch']) ? $conn->real_escape_string(trim($_GET['batch'])) : '';

if ($production_id <= 0 && $batch === '') {
    echo json_encode(["status" => "error", "message" => "Wajib isi production_id atau batch."]);
    exit;
}

$where = $production_id > 0 ? "id = $production_id" : "batch = '$batch'";
$res   = $conn->query("SELECT id, batch, item, size, unit, copies FROM production_labels WHERE $where LIMIT 1");

if (!$res || $res->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Batch tidak ditemukan."]);
    exit;
}
$prod          = $res->fetch_assoc();
$production_id = (int)$prod['id'];
$copies        = (int)$prod['copies'];

$active_count    = (int)$conn->query("SELECT COUNT(*) AS c FROM warehouse_items WHERE production_id = $production_id")->fetch_assoc()['c'];
$shipped_count   = (int)$conn->query("SELECT COUNT(*) AS c FROM distributor_shipments WHERE production_id = $production_id")->fetch_assoc()['c'];
$cancel_prod_cnt = (int)$conn->query("SELECT COUNT(*) AS c FROM cancelled_labels WHERE production_id = $production_id AND category='production'")->fetch_assoc()['c'];
$cancel_wh_cnt   = (int)$conn->query("SELECT COUNT(*) AS c FROM cancelled_labels WHERE production_id = $production_id AND category='warehouse'")->fetch_assoc()['c'];
$cancel_total    = $cancel_prod_cnt + $cancel_wh_cnt;

$cancel_list = [];
$res_list    = $conn->query(
    "SELECT label_no, category, reason, cancelled_by, cancelled_at
     FROM cancelled_labels
     WHERE production_id = $production_id
     ORDER BY label_no ASC"
);
while ($row = $res_list->fetch_assoc()) {
    $row['label_no'] = (int)$row['label_no'];
    $cancel_list[]   = $row;
}

// Invariant check (copies = active + shipped + cancelled_total)
$sum_check = $active_count + $shipped_count + $cancel_total;
$consistent = ($sum_check === $copies);

echo json_encode([
    'status' => 'success',
    'batch'  => [
        'production_id' => $production_id,
        'batch'         => $prod['batch'],
        'item'          => $prod['item'],
        'size'          => $prod['size'],
        'unit'          => $prod['unit']
    ],
    'stats' => [
        'issued'               => $copies,
        'active'               => $active_count,
        'shipped'              => $shipped_count,
        'cancelled_production' => $cancel_prod_cnt,
        'cancelled_warehouse'  => $cancel_wh_cnt,
        'cancelled_total'      => $cancel_total,
        'is_consistent'        => $consistent
    ],
    'cancelled_labels' => $cancel_list
]);
