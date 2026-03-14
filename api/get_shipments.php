<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$item_filter = isset($_GET['item']) ? $conn->real_escape_string($_GET['item']) : '';
$size_filter = isset($_GET['size']) ? $conn->real_escape_string($_GET['size']) : '';
$view_mode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'customer';
$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
$end_date   = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';

$where = "WHERE 1=1";

if (!empty($search)) {
    if ($view_mode === 'batch') {
        $where .= " AND (p.batch LIKE '%$search%' OR p.item LIKE '%$search%' OR s.customer_name LIKE '%$search%')";
    } else {
        $where .= " AND (s.customer_name LIKE '%$search%' OR s.customer_contact LIKE '%$search%' OR s.id IN (SELECT ds.shipment_id FROM distributor_shipments ds JOIN production_labels p ON ds.production_id = p.id WHERE p.item LIKE '%$search%'))";
    }
}

if (!empty($item_filter)) {
    if ($view_mode === 'batch') {
        $where .= " AND p.item = '$item_filter'";
    } else {
        $where .= " AND s.id IN (SELECT ds.shipment_id FROM distributor_shipments ds JOIN production_labels p ON ds.production_id = p.id WHERE p.item = '$item_filter')";
    }
}

if (!empty($size_filter) && $size_filter !== 'Custom') {
    if ($view_mode === 'batch') {
        $where .= " AND p.size = '$size_filter'";
    } else {
        $where .= " AND s.id IN (SELECT ds.shipment_id FROM distributor_shipments ds JOIN production_labels p ON ds.production_id = p.id WHERE p.size = '$size_filter')";
    }
}

if (!empty($start_date) && !empty($end_date)) {
    $where .= " AND s.shipment_date BETWEEN '$start_date' AND '$end_date'";
}

// Logic for BATCH VIEW
if ($view_mode === 'batch') {
    $sqlTotal = "SELECT COUNT(DISTINCT p.id) as total FROM outbound_shipment_batches b JOIN production_labels p ON b.production_id = p.id JOIN outbound_shipments s ON b.shipment_id = s.id $where";
    $resTotal = $conn->query($sqlTotal);
    $totalData = $resTotal->fetch_assoc()['total'] ?? 0;
    $totalPages = ceil($totalData / $limit);

    $sql = "SELECT 
                p.batch, p.item, p.size, p.unit, 
                SUM(b.label_qty) as total_qty,
                GROUP_CONCAT(DISTINCT CONCAT(s.customer_name, ' (', b.label_qty, ' Dus)') SEPARATOR '|||') as distribution_list,
                MAX(s.shipment_date) as latest_shipment
            FROM outbound_shipment_batches b
            JOIN production_labels p ON b.production_id = p.id
            JOIN outbound_shipments s ON b.shipment_id = s.id
            $where
            GROUP BY p.id
            ORDER BY latest_shipment DESC, p.batch DESC
            LIMIT $offset, $limit";
} else {
    // Logic for CUSTOMER VIEW
    $sqlTotal = "SELECT COUNT(DISTINCT s.id) as total, COUNT(DISTINCT s.customer_name) as total_customer FROM outbound_shipments s $where";
    $resTotal = $conn->query($sqlTotal);
    $totalRow = $resTotal->fetch_assoc();
    $totalData = $totalRow['total'] ?? 0;
    $totalPages = ceil($totalData / $limit);
    $total_customer = $totalRow['total_customer'] ?? 0;

    $sql = "SELECT s.*, 
                   (SELECT GROUP_CONCAT(CONCAT(p.item, ' (', p.size, ' ', p.unit, ')|', b.label_qty, '|', b.unit_qty) SEPARATOR ';') 
                    FROM outbound_shipment_batches b
                    JOIN production_labels p ON b.production_id = p.id 
                    WHERE b.shipment_id = s.id" . (!empty($item_filter) ? " AND p.item = '$item_filter'" : "") . (!empty($size_filter) && $size_filter !== 'Custom' ? " AND p.size = '$size_filter'" : "") . "
                    GROUP BY b.shipment_id) as item_summary
            FROM outbound_shipments s 
            $where 
            ORDER BY s.shipment_date DESC, s.shipped_at DESC 
            LIMIT $offset, $limit";
}

$res = $conn->query($sql);
$data = [];
$monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

while($row = $res->fetch_assoc()) {
    if ($view_mode === 'batch') {
        $data[] = $row;
    } else {
        $ts = strtotime($row['shipment_date']);
        $row['shipped_at_formatted'] = date('d', $ts) . ' ' . $monthNames[(int)date('m', $ts) - 1] . ' ' . date('Y', $ts);
        $row['shipped_time_formatted'] = date('H:i', strtotime($row['shipped_at'])) . " WITA";
        
        $shipment_date = $row['shipment_date'];
        $shipment_id = $row['id'];
        $stmtSeq = $conn->prepare("SELECT COUNT(id) as seq FROM outbound_shipments WHERE shipment_date = ? AND id <= ?");
        $stmtSeq->bind_param("si", $shipment_date, $shipment_id);
        $stmtSeq->execute();
        $seq = $stmtSeq->get_result()->fetch_assoc()['seq'];
        $datetime_str = date('dmYHi', strtotime($row['shipped_at']));
        $name_parts = explode(' ', trim($row['customer_name']));
        $initials = (count($name_parts) >= 2) ? strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1)) : strtoupper(substr(trim($row['customer_name']), 0, 2));
        $row['no_resi'] = $seq . '-' . $datetime_str . '-' . $row['total_qty'] . '-' . $initials;
        
        $data[] = $row;
    }
}

// Stats Calculation
$sqlStats = "SELECT COUNT(DISTINCT s.id) as total_p, COALESCE(SUM(b_unit.label_qty), 0) as total_l, COUNT(DISTINCT s.customer_name) as total_c 
             FROM outbound_shipments s 
             JOIN outbound_shipment_batches b_unit ON b_unit.shipment_id = s.id 
             JOIN production_labels p_unit ON b_unit.production_id = p_unit.id";
$resStats = $conn->query($sqlStats);
$statsRow = $resStats->fetch_assoc();

echo json_encode([
    'data' => $data,
    'total' => $totalData,
    'pages' => $totalPages,
    'current_page' => $page,
    'view_mode' => $view_mode,
    'stats' => [
        'total_pengiriman' => $statsRow['total_p'],
        'total_unit' => $statsRow['total_l'],
        'total_label' => $statsRow['total_l'],
        'total_customer' => $statsRow['total_c'],
        'total_repeat' => 0,
        'bulan' => !empty($search) || !empty($item_filter) ? "Hasil Filter" : "Data Aktif"
    ]
]);
?>