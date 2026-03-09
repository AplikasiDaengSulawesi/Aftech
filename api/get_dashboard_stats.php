<?php
include 'config.php';
header('Content-Type: application/json');

$stats = [];
$today = date('Y-m-d');

// 1. Overall Stats (Unit Fisik)
$stats['total_production'] = (int)$conn->query("SELECT SUM(quantity * copies) FROM production_labels")->fetch_row()[0];
$stats['total_warehouse'] = (int)$conn->query("SELECT SUM(p.quantity) FROM warehouse_items w JOIN production_labels p ON w.production_id = p.id")->fetch_row()[0];
$stats['total_shipped'] = (int)$conn->query("SELECT SUM(total_actual_qty) FROM outbound_shipments")->fetch_row()[0];

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

// 6. Production Trend (Last 7 Days)
$trend_res = $conn->query("
    SELECT d.date, 
           COALESCE(SUM(p.produced), 0) as produced,
           COALESCE(SUM(p.verified), 0) as verified,
           COALESCE(SUM(p.shipped), 0) as shipped
    FROM (
        SELECT CURDATE() - INTERVAL (a.a + (10 * b.a)) DAY as date
        FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as a
        CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as b
    ) d
    LEFT JOIN (
        SELECT production_date as act_date, (quantity * copies) as produced, 0 as verified, 0 as shipped FROM production_labels
        UNION ALL
        SELECT DATE(w.transferred_at) as act_date, 0 as produced, pl.quantity as verified, 0 as shipped FROM warehouse_items w JOIN production_labels pl ON w.production_id = pl.id
        UNION ALL
        SELECT shipment_date as act_date, 0 as produced, 0 as verified, total_actual_qty as shipped FROM outbound_shipments
    ) p ON d.date = p.act_date
    WHERE d.date BETWEEN CURDATE() - INTERVAL 6 DAY AND CURDATE()
    GROUP BY d.date
    ORDER BY d.date ASC
");

$dates = []; $prod_trend = []; $ver_trend = []; $ship_trend = [];
while($r = $trend_res->fetch_assoc()) {
    $dates[] = date('d M', strtotime($r['date']));
    $prod_trend[] = (int)$r['produced'];
    $ver_trend[] = (int)$r['verified'];
    $ship_trend[] = (int)$r['shipped'];
}
$stats['trend'] = ['labels' => $dates, 'produced' => $prod_trend, 'verified' => $ver_trend, 'shipped' => $ship_trend];

echo json_encode($stats);
?>