<?php
include 'config.php';
header('Content-Type: application/json');

$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : date('Y-m-d');
$item_filter = isset($_GET['item']) ? $conn->real_escape_string($_GET['item']) : '';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == 'true';

// Base Filter untuk Batch yang ditampilkan
$where = "WHERE 1=1";
if (!$show_all) {
    $where .= " AND p.production_date BETWEEN '$start_date' AND '$end_date'";
}
if ($item_filter) {
    $where .= " AND p.item = '$item_filter'";
}

// --- 1. SUMMARY TOTALS (Tetap Akurat Sesuai Filter) ---
$res_prod = $conn->query("SELECT SUM(quantity * copies) as total FROM production_labels p $where");
$total_produced = (int)($res_prod->fetch_assoc()['total'] ?? 0);

$res_ver = $conn->query("SELECT SUM(pl.quantity) as total 
                         FROM warehouse_items w 
                         JOIN production_labels pl ON w.production_id = pl.id 
                         " . ($show_all ? "" : "WHERE pl.production_date BETWEEN '$start_date' AND '$end_date'") . "
                         " . ($item_filter ? " AND pl.item = '$item_filter'" : ""));
$total_verified = (int)($res_ver->fetch_assoc()['total'] ?? 0);

$res_ship = $conn->query("SELECT SUM(b.unit_qty) as total 
                          FROM outbound_shipment_batches b 
                          JOIN outbound_shipments s ON b.shipment_id = s.id 
                          JOIN production_labels pl ON b.production_id = pl.id 
                          " . ($show_all ? "" : "WHERE pl.production_date BETWEEN '$start_date' AND '$end_date'") . "
                          " . ($item_filter ? " AND pl.item = '$item_filter'" : ""));
$total_shipped = (int)($res_ship->fetch_assoc()['total'] ?? 0);

// --- 2. DETAILED TABLE DATA (Berbasis Batch) ---
$sql = "
    SELECT 
        p.production_date,
        p.batch,
        p.item,
        p.size,
        p.unit,
        (p.quantity * p.copies) as produced_qty,
        (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) * p.quantity as verified_qty,
        COALESCE((SELECT SUM(unit_qty) FROM outbound_shipment_batches WHERE production_id = p.id), 0) as shipped_qty
    FROM production_labels p
    $where
    ORDER BY p.production_date DESC, p.id DESC
";

$res_details = $conn->query($sql);
$details = [];
if ($res_details) {
    while($row = $res_details->fetch_assoc()) {
        $row['stock_qty'] = (int)$row['verified_qty'] - (int)$row['shipped_qty'];
        $details[] = $row;
    }
}

// Labeling Logic
$bulan_indonesia = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
if ($show_all) $bulan_label = "Semua Waktu";
else {
    $m_start = date('m', strtotime($start_date));
    $y_start = date('Y', strtotime($start_date));
    $bulan_label = ($start_date == date('Y-m-01', strtotime($start_date)) && $end_date == date('Y-m-t', strtotime($start_date))) ? $bulan_indonesia[$m_start].' '.$y_start : "Hasil Filter";
}

echo json_encode([
    'status' => 'success',
    'summary' => [
        'produced' => $total_produced,
        'verified' => $total_verified,
        'shipped' => $total_shipped,
        'stock' => $total_verified - $total_shipped,
        'bulan' => $bulan_label
    ],
    'data' => $details
]);
?>