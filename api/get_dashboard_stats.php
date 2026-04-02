<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$stats = [];
$today = date('Y-m-d');

// If polling only for logs (via header.php)
if (isset($_GET['logs_only']) && $_GET['logs_only'] == '1') {
    $log_res = $conn->query("SELECT action, details, timestamp FROM activity_logs ORDER BY id DESC LIMIT 12");
    $logs = [];
    while($r = $log_res->fetch_assoc()) { 
        $r['time'] = date('H:i', strtotime($r['timestamp']));
        $logs[] = $r; 
    }
    $stats['recent_logs'] = $logs;
    echo json_encode($stats);
    exit;
}

// 1. Overall Stats (Based on Dus/Labels)
$stats['total_production'] = (int)$conn->query("SELECT SUM(copies) FROM production_labels")->fetch_row()[0];
$stats['total_verified'] = (int)$conn->query("SELECT COUNT(*) FROM warehouse_items")->fetch_row()[0];
$stats['total_shipped'] = (int)$conn->query("SELECT COUNT(*) FROM distributor_shipments")->fetch_row()[0];

// Label-based stats (Keeping for compatibility with existing JS if any)
$stats['total_stok_labels'] = $stats['total_verified'];
$stats['total_kapasitas_labels'] = $stats['total_production'];

// 2. Machine Performance (Produksi Dus)
$mach_res = $conn->query("SELECT machine, SUM(copies) as total FROM production_labels GROUP BY machine ORDER BY total DESC LIMIT 5");
$machines = [];
while($r = $mach_res->fetch_assoc()) { $machines[] = $r; }
$stats['machine_stats'] = $machines;

// 3. Inventory Health (Stok Dus per Item di Gudang)
$inv_res = $conn->query("
    SELECT p.item, 
           COUNT(w.id) - COALESCE((SELECT COUNT(*) FROM distributor_shipments ds WHERE ds.production_id = p.id), 0) as current_stock
    FROM production_labels p
    LEFT JOIN warehouse_items w ON w.production_id = p.id
    GROUP BY p.item
    HAVING current_stock > 0
    ORDER BY current_stock DESC
");
$inventory = [];
while($r = $inv_res->fetch_assoc()) { $inventory[] = ['item' => $r['item'], 'in_wh' => (int)$r['current_stock']]; }
$stats['inventory_health'] = $inventory;

// 4. Recent Batches (Using copies as total_qty)
$batch_res = $conn->query("SELECT batch, item, copies as total_qty FROM production_labels ORDER BY id DESC LIMIT 10");
$batches = [];
while($r = $batch_res->fetch_assoc()) { $batches[] = $r; }
$stats['recent_batches'] = $batches;

// 5. Recent Logs
$log_res = $conn->query("SELECT action, details, timestamp FROM activity_logs ORDER BY id DESC LIMIT 12");
$logs = [];
while($r = $log_res->fetch_assoc()) { 
    $r['time'] = date('H:i', strtotime($r['timestamp']));
    $logs[] = $r; 
}
$stats['recent_logs'] = $logs;

// 6. Production Trend (Dynamic Range)
$range = $_GET['range'] ?? 'week';
$dates = []; $prod_trend = []; $ver_trend = []; $ship_trend = [];

if ($range === 'year') {
    // Trend by Year for the last 5 years
    $current_year_num = (int)date('Y');
    for ($i = 4; $i >= 0; $i--) {
        $year = $current_year_num - $i;
        $dates[] = (string)$year;
        $p = $conn->query("SELECT SUM(copies) FROM production_labels WHERE YEAR(production_date) = '$year'")->fetch_row()[0] ?? 0;
        $v = $conn->query("SELECT COUNT(*) FROM warehouse_items WHERE YEAR(transferred_at) = '$year'")->fetch_row()[0] ?? 0;
        $s = $conn->query("SELECT COUNT(*) FROM distributor_shipments ds JOIN outbound_shipments s ON ds.shipment_id = s.id WHERE YEAR(s.shipment_date) = '$year'")->fetch_row()[0] ?? 0;
        $prod_trend[] = (int)$p; $ver_trend[] = (int)$v; $ship_trend[] = (int)$s;
    }
} else if ($range === 'month') {
    // Trend by Month for the last 12 months (e.g., Maret 2026, April 2026)
    for ($i = 11; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $month_start = "$m-01";
        $month_end = date('Y-m-t', strtotime($month_start));
        $dates[] = date('M Y', strtotime($month_start));
        $p = $conn->query("SELECT SUM(copies) FROM production_labels WHERE production_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0] ?? 0;
        $v = $conn->query("SELECT COUNT(*) FROM warehouse_items WHERE DATE(transferred_at) BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0] ?? 0;
        $s = $conn->query("SELECT COUNT(*) FROM distributor_shipments ds JOIN outbound_shipments s ON ds.shipment_id = s.id WHERE s.shipment_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0] ?? 0;
        $prod_trend[] = (int)$p; $ver_trend[] = (int)$v; $ship_trend[] = (int)$s;
    }
} else {
    // Trend by Day for the last 7 days (Week)
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('d M', strtotime($d));
        $p = $conn->query("SELECT SUM(copies) FROM production_labels WHERE production_date = '$d'")->fetch_row()[0] ?? 0;
        $v = $conn->query("SELECT COUNT(*) FROM warehouse_items WHERE DATE(transferred_at) = '$d'")->fetch_row()[0] ?? 0;
        $s = $conn->query("SELECT COUNT(*) FROM distributor_shipments ds JOIN outbound_shipments s ON ds.shipment_id = s.id WHERE s.shipment_date = '$d'")->fetch_row()[0] ?? 0;
        $prod_trend[] = (int)$p; $ver_trend[] = (int)$v; $ship_trend[] = (int)$s;
    }
}

$stats['trend'] = ['labels' => $dates, 'produced' => $prod_trend, 'verified' => $ver_trend, 'shipped' => $ship_trend];

echo json_encode($stats);
?>