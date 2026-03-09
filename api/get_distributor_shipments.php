<?php
include 'config.php';
header('Content-Type: application/json');

$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$item_filter = isset($_GET['item']) ? $conn->real_escape_string($_GET['item']) : '';
$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
$end_date   = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';

$where = "WHERE 1=1";

if (!empty($search)) {
    $where .= " AND (s.customer_name LIKE '%$search%' OR s.customer_contact LIKE '%$search%' OR s.id IN (SELECT ds.shipment_id FROM distributor_shipments ds JOIN production_labels p ON ds.production_id = p.id WHERE p.item LIKE '%$search%'))";
}

if (!empty($item_filter)) {
    $where .= " AND s.id IN (SELECT ds.shipment_id FROM distributor_shipments ds JOIN production_labels p ON ds.production_id = p.id WHERE p.item = '$item_filter')";
}

if (!empty($start_date) && !empty($end_date)) {
    $where .= " AND s.shipment_date BETWEEN '$start_date' AND '$end_date'";
}

// Hitung total data header
$sqlTotal = "SELECT COUNT(DISTINCT s.id) as total, COUNT(DISTINCT s.customer_name) as total_customer FROM outbound_shipments s $where";
$resTotal = $conn->query($sqlTotal);
$totalRow = $resTotal->fetch_assoc();
$totalData = $totalRow['total'];
$totalPages = ceil($totalData / $limit);
$total_customer = $totalRow['total_customer'];

// Hitung khusus Total Unit secara presisi berdasarkan filter
$unit_join = "JOIN outbound_shipment_batches b_unit ON b_unit.shipment_id = s.id 
              JOIN production_labels p_unit ON b_unit.production_id = p_unit.id";
$unit_where = $where; 
if (!empty($item_filter)) {
    $unit_where .= " AND p_unit.item = '$item_filter'";
}
$sqlUnitTotal = "SELECT COALESCE(SUM(b_unit.unit_qty), 0) as total_unit FROM outbound_shipments s $unit_join $unit_where";
$resUnitTotal = $conn->query($sqlUnitTotal);
$unitTotalRow = $resUnitTotal->fetch_assoc();
$total_unit = $unitTotalRow['total_unit'];

// Tentukan label bulan/filter
$bulan_indonesia = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$current_label = $bulan_indonesia[date('m')] . ' ' . date('Y');

if (!empty($search) || !empty($item_filter) || !empty($start_date)) {
    $current_label = "Hasil Filter";
}

// Ambil data header dengan limit
$select_qty = "";
if (!empty($item_filter)) {
    $select_qty = ", (SELECT SUM(b3.label_qty) FROM outbound_shipment_batches b3 JOIN production_labels p3 ON b3.production_id = p3.id WHERE b3.shipment_id = s.id AND p3.item = '$item_filter') as filtered_label_qty,
                    (SELECT SUM(b4.unit_qty) FROM outbound_shipment_batches b4 JOIN production_labels p4 ON b4.production_id = p4.id WHERE b4.shipment_id = s.id AND p4.item = '$item_filter') as filtered_actual_qty";
} else {
    $select_qty = ", s.total_qty as filtered_label_qty, s.total_actual_qty as filtered_actual_qty";
}

$sql = "SELECT s.* $select_qty, 
               (SELECT GROUP_CONCAT(CONCAT(p.item, ' (', p.size, ' ', p.unit, ')|', b.label_qty, '|', b.unit_qty) SEPARATOR ';') 
                FROM outbound_shipment_batches b
                JOIN production_labels p ON b.production_id = p.id 
                WHERE b.shipment_id = s.id" . (!empty($item_filter) ? " AND p.item = '$item_filter'" : "") . "
                GROUP BY b.shipment_id) as item_summary
        FROM outbound_shipments s 
        $where 
        ORDER BY s.shipment_date DESC, s.shipped_at DESC 
        LIMIT $offset, $limit";
$res = $conn->query($sql);
$data = [];

$monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

while($row = $res->fetch_assoc()) {
$ts = strtotime($row['shipment_date']);
$day = date('d', $ts);
$month = $monthNames[(int)date('m', $ts) - 1];
$year = date('Y', $ts);
$row['shipped_at_formatted'] = "$day $month $year";
$row['shipped_time_formatted'] = date('H:i', strtotime($row['shipped_at'])) . " WITA";

// Hitung seq
    $shipment_date = $row['shipment_date'];
    $shipment_id = $row['id'];
    $stmtSeq = $conn->prepare("SELECT COUNT(id) as seq FROM outbound_shipments WHERE shipment_date = ? AND id <= ?");
    $stmtSeq->bind_param("si", $shipment_date, $shipment_id);
    $stmtSeq->execute();
    $resSeq = $stmtSeq->get_result();
    $seqData = $resSeq->fetch_assoc();
    $seq = $seqData['seq'];

    // Format Tanggal dan Waktu
    $datetime_str = date('dmYHi', strtotime($row['shipped_at']));

    // Format Inisial Customer
    $name_parts = explode(' ', trim($row['customer_name']));
    $initials = '';
    if (count($name_parts) >= 2) {
        $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
    } else {
        $initials = strtoupper(substr(trim($row['customer_name']), 0, 2));
    }

    // Generate No Resi
    $total_paket = $row['total_qty'];
    $row['no_resi'] = $seq . '-' . $datetime_str . '-' . $total_paket . '-' . $initials;

    $data[] = $row;
}

echo json_encode([
    'data' => $data,
    'total' => $totalData,
    'pages' => $totalPages,
    'current_page' => $page,
    'stats' => [
        'total_pengiriman' => $totalData,
        'total_unit' => $total_unit,
        'total_customer' => $total_customer,
        'bulan' => $current_label
    ]
]);
?>