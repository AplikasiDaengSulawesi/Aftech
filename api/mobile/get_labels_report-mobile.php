<?php
header("Content-Type: application/json");
include '../config.php';
verify_api_access();

$start_date   = isset($_GET['start_date'])   ? $conn->real_escape_string($_GET['start_date']) : date('Y-m-01');
$end_date     = isset($_GET['end_date'])     ? $conn->real_escape_string($_GET['end_date'])   : date('Y-m-d');
$item_filter  = isset($_GET['item'])         ? $conn->real_escape_string($_GET['item'])       : '';
$batch_only   = isset($_GET['batch'])        ? $conn->real_escape_string(trim($_GET['batch'])) : '';
$device_id    = isset($_GET['device_id'])    ? $conn->real_escape_string(trim($_GET['device_id']))    : '';
$device_model = isset($_GET['device_model']) ? $conn->real_escape_string(trim($_GET['device_model'])) : '';

$where = "WHERE p.production_date BETWEEN '$start_date' AND '$end_date'";
if ($item_filter !== '')  { $where .= " AND p.item = '$item_filter'"; }
if ($batch_only !== '')   { $where .= " AND p.batch = '$batch_only'"; }
if ($device_id !== '')    { $where .= " AND p.device_id = '$device_id'"; }
if ($device_model !== '') { $where .= " AND p.device_model = '$device_model'"; }

$res = $conn->query("
    SELECT p.id, p.batch, p.item, p.size, p.unit, p.machine, p.shift,
           p.operator, p.qc, p.production_date, p.production_time,
           p.copies, p.quantity, p.device_model, p.device_id
    FROM production_labels p
    $where
    ORDER BY p.production_date DESC, p.id DESC
");

if (!$res) {
    echo json_encode(["status" => "error", "message" => $conn->error]);
    exit;
}

$batches     = [];
$batch_index = [];
while ($row = $res->fetch_assoc()) {
    $pid = (int)$row['id'];
    $batches[$pid] = [
        'production_id'   => $pid,
        'batch'           => $row['batch'],
        'item'            => $row['item'],
        'size'            => $row['size'],
        'unit'            => $row['unit'],
        'machine'         => $row['machine'],
        'shift'           => $row['shift'],
        'operator'        => $row['operator'],
        'qc'              => $row['qc'],
        'production_date' => $row['production_date'],
        'production_time' => $row['production_time'],
        'copies'          => (int)$row['copies'],
        'quantity'        => (int)$row['quantity'],
        'device_model'    => $row['device_model'],
        'device_id'       => $row['device_id'],
        'stats'           => [
            'issued'    => (int)$row['copies'],
            'active'    => 0,
            'shipped'   => 0,
            'cancelled' => 0,
            'pending'   => 0,
        ],
        'labels'          => [],
    ];
    $batch_index[] = $pid;
}

if (empty($batch_index)) {
    echo json_encode([]);
    exit;
}

$ids_csv = implode(',', array_map('intval', $batch_index));

// Kumpulkan status per label (warehouse, shipped, cancelled) dalam 3 query saja
$wh_map = [];
$rs = $conn->query("SELECT production_id, label_no FROM warehouse_items WHERE production_id IN ($ids_csv)");
while ($r = $rs->fetch_assoc()) {
    $wh_map[(int)$r['production_id']][(int)$r['label_no']] = true;
}

$ship_map = [];
$rs = $conn->query("SELECT production_id, label_no FROM distributor_shipments WHERE production_id IN ($ids_csv)");
while ($r = $rs->fetch_assoc()) {
    $ship_map[(int)$r['production_id']][(int)$r['label_no']] = true;
}

$cancel_map = [];
$rs = $conn->query("
    SELECT production_id, label_no, category, reason, cancelled_by, cancelled_at
    FROM cancelled_labels WHERE production_id IN ($ids_csv)
");
while ($r = $rs->fetch_assoc()) {
    $cancel_map[(int)$r['production_id']][(int)$r['label_no']] = [
        'category'     => $r['category'],
        'reason'       => $r['reason'],
        'cancelled_by' => $r['cancelled_by'],
        'cancelled_at' => $r['cancelled_at'],
    ];
}

// Rakit array labels per batch
foreach ($batches as $pid => &$batch) {
    $copies = $batch['copies'];
    for ($label_no = 1; $label_no <= $copies; $label_no++) {
        $status = 'pending';
        $extra  = [];

        if (isset($cancel_map[$pid][$label_no])) {
            $status = 'cancelled';
            $extra  = $cancel_map[$pid][$label_no];
        } elseif (isset($ship_map[$pid][$label_no])) {
            $status = 'shipped';
        } elseif (isset($wh_map[$pid][$label_no])) {
            $status = 'active';
        }

        $batch['stats'][$status]++;

        $label = [
            'label_no' => $label_no,
            'status'   => $status,
            'qr_code'  => $label_no . '-' . $batch['batch'],
        ];
        if (!empty($extra)) { $label = array_merge($label, $extra); }
        $batch['labels'][] = $label;
    }
    $batch['stats']['is_consistent'] =
        ($batch['stats']['active'] + $batch['stats']['shipped']
         + $batch['stats']['cancelled'] + $batch['stats']['pending']) === $copies;
}
unset($batch);

echo json_encode(array_values($batches));
