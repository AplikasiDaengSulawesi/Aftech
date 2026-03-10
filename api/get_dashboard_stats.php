<?php
include 'config.php';
header('Content-Type: application/json');

$stats = [];
$today = date('Y-m-d');

// 1. Overall Stats (Unit Fisik) - TOTAL OVERALL (No Month Filter)
$stats['total_production'] = (int)$conn->query("SELECT SUM(quantity * copies) FROM production_labels")->fetch_row()[0];
$stats['total_verified'] = (int)$conn->query("SELECT SUM(p.quantity) FROM warehouse_items w JOIN production_labels p ON w.production_id = p.id")->fetch_row()[0];
$stats['total_shipped'] = (int)$conn->query("SELECT SUM(total_actual_qty) FROM outbound_shipments")->fetch_row()[0];

// Label-based stats (Logic from warehouse_inventory.php) - TOTAL OVERALL
$label_stats = $conn->query("
    SELECT COUNT(w.id) as total_stok_labels
    FROM warehouse_items w
")->fetch_assoc();

$stats['total_stok_labels'] = (int)($label_stats['total_stok_labels'] ?? 0);

$kap_res = $conn->query("
    SELECT SUM(copies) FROM production_labels
");
$stats['total_kapasitas_labels'] = (int)($kap_res->fetch_row()[0] ?? 0);

// 2. Machine Performance (Produksi Unit)
$mach_res = $conn->query("SELECT machine, SUM(quantity * copies) as total FROM production_labels GROUP BY machine ORDER BY total DESC LIMIT 5");
$machines = [];
while($r = $mach_res->fetch_assoc()) { $machines[] = $r; }
$stats['machine_stats'] = $machines;

// 3. Inventory Health (Stok per Item di Gudang)
$inv_res = $conn->query("
    SELECT p.item, 
           (COUNT(w.id) * p.quantity) - COALESCE((SELECT SUM(b.unit_qty) FROM outbound_shipment_batches b WHERE b.production_id = p.id), 0) as current_stock
    FROM warehouse_items w
    JOIN production_labels p ON w.production_id = p.id
    GROUP BY p.item
    ORDER BY current_stock DESC
");
$inventory = [];
while($r = $inv_res->fetch_assoc()) { $inventory[] = ['item' => $r['item'], 'in_wh' => (int)$r['current_stock']]; }
$stats['inventory_health'] = $inventory;

// 4. Recent Batches
$batch_res = $conn->query("SELECT batch, item, (quantity * copies) as total_qty FROM production_labels ORDER BY id DESC LIMIT 10");
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
        $p = $conn->query("SELECT SUM(quantity * copies) FROM production_labels WHERE YEAR(production_date) = '$year'")->fetch_row()[0] ?? 0;
        $v = $conn->query("SELECT SUM(pl.quantity) FROM warehouse_items w JOIN production_labels pl ON w.production_id = pl.id WHERE YEAR(w.transferred_at) = '$year'")->fetch_row()[0] ?? 0;
        $s = $conn->query("SELECT SUM(total_actual_qty) FROM outbound_shipments WHERE YEAR(shipment_date) = '$year'")->fetch_row()[0] ?? 0;
        $prod_trend[] = (int)$p; $ver_trend[] = (int)$v; $ship_trend[] = (int)$s;
    }
} else if ($range === 'month') {
    // Trend by Month for the last 12 months (e.g., Maret 2026, April 2026)
    for ($i = 11; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $month_start = "$m-01";
        $month_end = date('Y-m-t', strtotime($month_start));
        $dates[] = date('M Y', strtotime($month_start));
        $p = $conn->query("SELECT SUM(quantity * copies) FROM production_labels WHERE production_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0] ?? 0;
        $v = $conn->query("SELECT SUM(pl.quantity) FROM warehouse_items w JOIN production_labels pl ON w.production_id = pl.id WHERE DATE(w.transferred_at) BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0] ?? 0;
        $s = $conn->query("SELECT SUM(total_actual_qty) FROM outbound_shipments WHERE shipment_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0] ?? 0;
        $prod_trend[] = (int)$p; $ver_trend[] = (int)$v; $ship_trend[] = (int)$s;
    }
} else {
    // Trend by Day for the last 7 days (Week)
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('d M', strtotime($d));
        $p = $conn->query("SELECT SUM(quantity * copies) FROM production_labels WHERE production_date = '$d'")->fetch_row()[0] ?? 0;
        $v = $conn->query("SELECT SUM(pl.quantity) FROM warehouse_items w JOIN production_labels pl ON w.production_id = pl.id WHERE DATE(w.transferred_at) = '$d'")->fetch_row()[0] ?? 0;
        $s = $conn->query("SELECT SUM(total_actual_qty) FROM outbound_shipments WHERE shipment_date = '$d'")->fetch_row()[0] ?? 0;
        $prod_trend[] = (int)$p; $ver_trend[] = (int)$v; $ship_trend[] = (int)$s;
    }
}

$stats['trend'] = ['labels' => $dates, 'produced' => $prod_trend, 'verified' => $ver_trend, 'shipped' => $ship_trend];

echo json_encode($stats);
?>