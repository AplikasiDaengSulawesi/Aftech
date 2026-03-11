<?php
header('Content-Type: application/json');
include 'config.php';
verify_api_access();

$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 0; // 0 berarti ambil semua (untuk cetak jika diperlukan API)
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : date('Y-m-d');
$item_filter = isset($_GET['item']) ? $conn->real_escape_string($_GET['item']) : '';
$size_filter = isset($_GET['size']) ? $conn->real_escape_string($_GET['size']) : '';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == 'true';
$report_type = isset($_GET['report_type']) ? $conn->real_escape_string($_GET['report_type']) : 'rekap';

// Base Filter
$where = "WHERE 1=1";
if (!$show_all) { $where .= " AND p.production_date BETWEEN '$start_date' AND '$end_date'"; }
if ($item_filter) { $where .= " AND p.item = '$item_filter'"; }
if ($size_filter && $size_filter !== 'Custom') { $where .= " AND p.size = '$size_filter'"; }

// --- 1. SUMMARY TOTALS ---
$res_prod = $conn->query("SELECT SUM(copies) as total FROM production_labels p $where");
$total_produced = (int)($res_prod->fetch_assoc()['total'] ?? 0);

$v_where = $show_all ? "WHERE 1=1" : "WHERE pl.production_date BETWEEN '$start_date' AND '$end_date'";
if($item_filter) $v_where .= " AND pl.item = '$item_filter'";
if($size_filter && $size_filter !== 'Custom') $v_where .= " AND pl.size = '$size_filter'";
$res_ver = $conn->query("SELECT COUNT(w.id) as total FROM warehouse_items w JOIN production_labels pl ON w.production_id = pl.id $v_where");
$total_verified = (int)($res_ver->fetch_assoc()['total'] ?? 0);

$s_where = $show_all ? "WHERE 1=1" : "WHERE s.shipment_date BETWEEN '$start_date' AND '$end_date'";
if($item_filter) $s_where .= " AND pl.item = '$item_filter'";
$res_ship = $conn->query("SELECT COUNT(ds.id) as total FROM distributor_shipments ds JOIN outbound_shipments s ON ds.shipment_id = s.id JOIN production_labels pl ON ds.production_id = pl.id $s_where");
$total_shipped = (int)($res_ship->fetch_assoc()['total'] ?? 0);

// --- 2. COUNT TOTAL DATA FOR PAGINATION ---
$count_sql = "";
if ($report_type == 'pengiriman') {
    $count_sql = "SELECT COUNT(DISTINCT s.id) as total FROM outbound_shipments s JOIN outbound_shipment_batches b ON s.id = b.shipment_id JOIN production_labels p ON b.production_id = p.id " . ($show_all ? "WHERE 1=1" : "WHERE s.shipment_date BETWEEN '$start_date' AND '$end_date'") . ($item_filter ? " AND p.item = '$item_filter'" : "");
} else {
    $count_sql = "SELECT COUNT(*) as total FROM production_labels p $where";
}
$total_rows = (int)($conn->query($count_sql)->fetch_assoc()['total'] ?? 0);
$total_pages = $limit > 0 ? ceil($total_rows / $limit) : 1;

// --- 3. DETAILED TABLE DATA ---
$sql = "";
if ($report_type == 'produksi') {
    $sql = "SELECT p.production_date, p.batch, p.item, p.size, p.unit, p.copies as produced_qty, p.machine, p.shift, p.operator, p.qc, p.production_time,
                   (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) as scanned
            FROM production_labels p $where ORDER BY p.production_date DESC, p.id DESC";
} elseif ($report_type == 'gudang') {
    $sql = "SELECT p.production_date, p.batch, p.item, p.size, p.unit, p.copies as produced_qty, p.machine, p.shift,
                   (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) as verified_qty,
                   (SELECT COUNT(*) FROM distributor_shipments WHERE production_id = p.id) as shipped_qty
            FROM production_labels p $where ORDER BY p.production_date DESC, p.id DESC";
} elseif ($report_type == 'pengiriman') {
    $sql = "SELECT s.id as shipment_id, s.shipment_date, s.customer_name, s.shipped_at, s.shipped_by, s.total_qty as total_shipped_qty,
                   GROUP_CONCAT(CONCAT(p.item, ' (', p.size, ' ', p.unit, ')|', (SELECT COUNT(*) FROM distributor_shipments WHERE shipment_id = s.id AND production_id = p.id), '|', p.batch) SEPARATOR ';') as item_summary
            FROM outbound_shipments s
            JOIN outbound_shipment_batches b ON s.id = b.shipment_id
            JOIN production_labels p ON b.production_id = p.id
            " . ($show_all ? "WHERE 1=1" : "WHERE s.shipment_date BETWEEN '$start_date' AND '$end_date'") . "
            " . ($item_filter ? " AND p.item = '$item_filter'" : "") . "
            GROUP BY s.id ORDER BY s.shipment_date DESC, s.id DESC";
} else { // rekap
    $sql = "SELECT p.production_date, p.batch, p.item, p.size, p.unit, p.copies as produced_qty,
                   (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) as verified_qty,
                   COALESCE((SELECT COUNT(*) FROM distributor_shipments WHERE production_id = p.id), 0) as shipped_qty
            FROM production_labels p $where ORDER BY p.production_date DESC, p.id DESC";
}

if ($limit > 0) {
    $sql .= " LIMIT $offset, $limit";
}

$res_details = $conn->query($sql);
$details = [];
if ($res_details) {
    while($row = $res_details->fetch_assoc()) {
        if ($report_type != 'pengiriman') {
            $row['stock_qty'] = (int)($row['verified_qty'] ?? 0) - (int)($row['shipped_qty'] ?? 0);
        } else {
            // Generate No Resi for API response
            $ship_id = $row['shipment_id']; $ship_date = $row['shipment_date'];
            $resSeq = $conn->query("SELECT COUNT(id) as seq FROM outbound_shipments WHERE shipment_date = '$ship_date' AND id <= $ship_id");
            $seq = $resSeq->fetch_assoc()['seq'] ?? 1;
            $name_parts = explode(' ', trim($row['customer_name']));
            $initials = (count($name_parts) >= 2) ? strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1)) : strtoupper(substr(trim($row['customer_name']), 0, 2));
            $row['no_resi'] = $seq . '-' . date('dmYHi', strtotime($row['shipped_at'])) . '-' . $row['total_shipped_qty'] . '-' . $initials;
        }
        $details[] = $row;
    }
}

$bulan_indonesia = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
if ($show_all) $bulan_label = "Semua Waktu";
else {
    $m_start = date('m', strtotime($start_date));
    $y_start = date('Y', strtotime($start_date));
    $bulan_label = ($start_date == date('Y-m-01', strtotime($start_date)) && $end_date == date('Y-m-t', strtotime($start_date))) ? '(' . $bulan_indonesia[$m_start] . ' ' . $y_start . ')' : "Hasil Filter";
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
    'data' => $details,
    'total' => $total_rows,
    'pages' => $total_pages,
    'current_page' => $page
]);
?>