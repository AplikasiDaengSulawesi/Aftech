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

// 3. Inventory Health (Stok Dus per Item di Gudang) — 2 agregasi + merge di PHP
//    Hindari correlated subquery (kena only_full_group_by) & double-count dari JOIN silang.
$wh_per_item = [];
$r = $conn->query("
    SELECT p.item, COUNT(w.id) AS cnt
    FROM production_labels p
    JOIN warehouse_items w ON w.production_id = p.id
    GROUP BY p.item
");
while ($row = $r->fetch_assoc()) { $wh_per_item[$row['item']] = (int)$row['cnt']; }

$ship_per_item = [];
$r = $conn->query("
    SELECT p.item, COUNT(d.id) AS cnt
    FROM production_labels p
    JOIN distributor_shipments d ON d.production_id = p.id
    GROUP BY p.item
");
while ($row = $r->fetch_assoc()) { $ship_per_item[$row['item']] = (int)$row['cnt']; }

$inventory = [];
foreach ($wh_per_item as $item => $wh_cnt) {
    $stock = $wh_cnt - ($ship_per_item[$item] ?? 0);
    if ($stock > 0) { $inventory[] = ['item' => $item, 'in_wh' => $stock]; }
}
usort($inventory, fn($a, $b) => $b['in_wh'] - $a['in_wh']);
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

// 6. Production Trend (Dynamic Range) — 3 agregasi, bukan N+1 loop
$range = $_GET['range'] ?? 'week';
$dates = []; $prod_trend = []; $ver_trend = []; $ship_trend = [];

if ($range === 'year') {
    // Trend by Year for the last 5 years
    $current_year = (int)date('Y');
    $start_year   = $current_year - 4;
    $range_start  = "$start_year-01-01";
    $range_end    = "$current_year-12-31";

    $keys = [];
    for ($i = 4; $i >= 0; $i--) {
        $y = $current_year - $i;
        $keys[(string)$y] = 0;
        $dates[]          = (string)$y;
    }

    $prod_map = $ver_map = $ship_map = $keys;

    $q = $conn->query("SELECT YEAR(production_date) AS bucket, SUM(copies) AS total FROM production_labels WHERE production_date BETWEEN '$range_start' AND '$range_end' GROUP BY bucket");
    while ($r = $q->fetch_assoc()) { if (isset($prod_map[$r['bucket']])) $prod_map[$r['bucket']] = (int)$r['total']; }

    $q = $conn->query("SELECT YEAR(transferred_at) AS bucket, COUNT(*) AS total FROM warehouse_items WHERE transferred_at BETWEEN '$range_start 00:00:00' AND '$range_end 23:59:59' GROUP BY bucket");
    while ($r = $q->fetch_assoc()) { if (isset($ver_map[$r['bucket']])) $ver_map[$r['bucket']] = (int)$r['total']; }

    $q = $conn->query("SELECT YEAR(s.shipment_date) AS bucket, COUNT(*) AS total FROM distributor_shipments ds JOIN outbound_shipments s ON ds.shipment_id = s.id WHERE s.shipment_date BETWEEN '$range_start' AND '$range_end' GROUP BY bucket");
    while ($r = $q->fetch_assoc()) { if (isset($ship_map[$r['bucket']])) $ship_map[$r['bucket']] = (int)$r['total']; }

    foreach ($prod_map as $total) $prod_trend[] = $total;
    foreach ($ver_map  as $total) $ver_trend[]  = $total;
    foreach ($ship_map as $total) $ship_trend[] = $total;
} else if ($range === 'month') {
    // Trend by Month for the last 12 months
    $range_start = date('Y-m-01', strtotime('-11 months'));
    $range_end   = date('Y-m-t');

    $keys = [];
    for ($i = 11; $i >= 0; $i--) {
        $ym           = date('Y-m', strtotime("-$i months"));
        $keys[$ym]    = 0;
        $dates[]      = date('M Y', strtotime("$ym-01"));
    }

    $prod_map = $ver_map = $ship_map = $keys;

    $q = $conn->query("SELECT DATE_FORMAT(production_date,'%Y-%m') AS bucket, SUM(copies) AS total FROM production_labels WHERE production_date BETWEEN '$range_start' AND '$range_end' GROUP BY bucket");
    while ($r = $q->fetch_assoc()) { if (isset($prod_map[$r['bucket']])) $prod_map[$r['bucket']] = (int)$r['total']; }

    $q = $conn->query("SELECT DATE_FORMAT(transferred_at,'%Y-%m') AS bucket, COUNT(*) AS total FROM warehouse_items WHERE transferred_at BETWEEN '$range_start 00:00:00' AND '$range_end 23:59:59' GROUP BY bucket");
    while ($r = $q->fetch_assoc()) { if (isset($ver_map[$r['bucket']])) $ver_map[$r['bucket']] = (int)$r['total']; }

    $q = $conn->query("SELECT DATE_FORMAT(s.shipment_date,'%Y-%m') AS bucket, COUNT(*) AS total FROM distributor_shipments ds JOIN outbound_shipments s ON ds.shipment_id = s.id WHERE s.shipment_date BETWEEN '$range_start' AND '$range_end' GROUP BY bucket");
    while ($r = $q->fetch_assoc()) { if (isset($ship_map[$r['bucket']])) $ship_map[$r['bucket']] = (int)$r['total']; }

    foreach ($prod_map as $total) $prod_trend[] = $total;
    foreach ($ver_map  as $total) $ver_trend[]  = $total;
    foreach ($ship_map as $total) $ship_trend[] = $total;
} else {
    // Trend by Day for the last 7 days
    $range_start = date('Y-m-d', strtotime('-6 days'));
    $range_end   = date('Y-m-d');

    $keys = [];
    for ($i = 6; $i >= 0; $i--) {
        $d         = date('Y-m-d', strtotime("-$i days"));
        $keys[$d]  = 0;
        $dates[]   = date('d M', strtotime($d));
    }

    $prod_map = $ver_map = $ship_map = $keys;

    $q = $conn->query("SELECT production_date AS bucket, SUM(copies) AS total FROM production_labels WHERE production_date BETWEEN '$range_start' AND '$range_end' GROUP BY production_date");
    while ($r = $q->fetch_assoc()) { if (isset($prod_map[$r['bucket']])) $prod_map[$r['bucket']] = (int)$r['total']; }

    $q = $conn->query("SELECT DATE(transferred_at) AS bucket, COUNT(*) AS total FROM warehouse_items WHERE transferred_at BETWEEN '$range_start 00:00:00' AND '$range_end 23:59:59' GROUP BY bucket");
    while ($r = $q->fetch_assoc()) { if (isset($ver_map[$r['bucket']])) $ver_map[$r['bucket']] = (int)$r['total']; }

    $q = $conn->query("SELECT s.shipment_date AS bucket, COUNT(*) AS total FROM distributor_shipments ds JOIN outbound_shipments s ON ds.shipment_id = s.id WHERE s.shipment_date BETWEEN '$range_start' AND '$range_end' GROUP BY s.shipment_date");
    while ($r = $q->fetch_assoc()) { if (isset($ship_map[$r['bucket']])) $ship_map[$r['bucket']] = (int)$r['total']; }

    foreach ($prod_map as $total) $prod_trend[] = $total;
    foreach ($ver_map  as $total) $ver_trend[]  = $total;
    foreach ($ship_map as $total) $ship_trend[] = $total;
}

$stats['trend'] = ['labels' => $dates, 'produced' => $prod_trend, 'verified' => $ver_trend, 'shipped' => $ship_trend];

echo json_encode($stats);
?>