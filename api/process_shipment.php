<?php
session_start();
include 'config.php';
verify_api_access();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$user = $_SESSION['full_name'] ?? 'Warehouse User';

function normalize_shipment_input_method($value) {
    $value = strtolower(trim((string)$value));
    if (in_array($value, ['scan', 'manual', 'campuran'], true)) {
        return $value;
    }
    return 'scan';
}

function merge_shipment_input_methods($existing, $incoming) {
    $existing = normalize_shipment_input_method($existing);
    $incoming = normalize_shipment_input_method($incoming);
    if ($existing === $incoming) {
        return $existing;
    }
    return 'campuran';
}

if ($action === 'get_batch_data') {
    $qr_input = isset($_GET['qr']) ? $conn->real_escape_string($_GET['qr']) : '';
    if (empty($qr_input)) die(json_encode(['status' => 'error', 'message' => 'QR Kosong']));

    $parts = explode('-', $qr_input, 2);
    if (count($parts) < 2) die(json_encode(['status' => 'error', 'message' => 'Format QR Tidak Valid']));

    $scanned_label = (int)$parts[0];
    $batch = $parts[1];

    // Cek Data Produksi
    $res = $conn->query("SELECT id, item, size, unit, machine, copies FROM production_labels WHERE batch = '$batch'");
    if ($res->num_rows === 0) die(json_encode(['status' => 'error', 'message' => 'Batch tidak ditemukan di sistem']));
    $prod = $res->fetch_assoc();
    $prod_id = $prod['id'];

    // Cek apakah barang yang di-scan ada di gudang
    $wh_check = $conn->query("SELECT id FROM warehouse_items WHERE production_id = $prod_id AND label_no = $scanned_label");
    if ($wh_check->num_rows === 0) die(json_encode(['status' => 'error', 'message' => "Dus #$scanned_label belum masuk ke Gudang!"]));

    // Cek apakah barang yang di-scan sudah terkirim
    $dist_check = $conn->query("SELECT id FROM distributor_shipments WHERE production_id = $prod_id AND label_no = $scanned_label");
    if ($dist_check->num_rows > 0) die(json_encode(['status' => 'error', 'message' => "Dus #$scanned_label sudah pernah dikirim!"]));

    // Ambil data stok gudang untuk batch ini
    $in_warehouse = [];
    $res_wh = $conn->query("SELECT label_no FROM warehouse_items WHERE production_id = $prod_id");
    while($r = $res_wh->fetch_assoc()) $in_warehouse[] = (int)$r['label_no'];

    // Ambil data yang sudah terkirim dari batch ini
    $already_shipped = [];
    $res_shipped = $conn->query("SELECT label_no FROM distributor_shipments WHERE production_id = $prod_id");
    while($r = $res_shipped->fetch_assoc()) $already_shipped[] = (int)$r['label_no'];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'production_id' => $prod_id,
            'batch' => $batch,
            'item' => $prod['item'],
            'size' => $prod['size'] . ' ' . $prod['unit'],
            'copies' => (int)$prod['copies'],
            'input_method' => 'scan',
            'scanned_label' => $scanned_label, // Label pemicu
            'in_warehouse' => $in_warehouse,
            'already_shipped' => $already_shipped
        ]
    ]);
} 
elseif ($action === 'search_batches') {
    // Cari batch dengan stok tersedia (untuk input manual tanpa barcode)
    $q = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';
    $where = '';
    if ($q !== '') {
        $where = "WHERE (p.batch LIKE '%$q%' OR p.item LIKE '%$q%' OR p.size LIKE '%$q%')";
    }
    $sql = "
        SELECT p.id, p.batch, p.item, p.size, p.unit, p.copies,
               (SELECT COUNT(*) FROM warehouse_items w WHERE w.production_id = p.id) AS wh_count,
               (SELECT COUNT(*) FROM distributor_shipments d WHERE d.production_id = p.id) AS ship_count
        FROM production_labels p
        $where
        ORDER BY p.id DESC
        LIMIT 30
    ";
    $res = $conn->query($sql);
    $data = [];
    while ($r = $res->fetch_assoc()) {
        $avail = (int)$r['wh_count'] - (int)$r['ship_count'];
        if ($avail <= 0) continue;
        $r['id'] = (int)$r['id'];
        $r['copies'] = (int)$r['copies'];
        $r['available'] = $avail;
        unset($r['wh_count'], $r['ship_count']);
        $data[] = $r;
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
}
elseif ($action === 'get_batch_manual') {
    // Ambil data batch untuk input manual (tanpa validasi scanned label)
    $prod_id = isset($_GET['production_id']) ? (int)$_GET['production_id'] : 0;
    if ($prod_id <= 0) die(json_encode(['status' => 'error', 'message' => 'Production ID tidak valid']));

    $res = $conn->query("SELECT id, item, size, unit, machine, batch, copies FROM production_labels WHERE id = $prod_id");
    if ($res->num_rows === 0) die(json_encode(['status' => 'error', 'message' => 'Batch tidak ditemukan']));
    $prod = $res->fetch_assoc();

    $in_warehouse = [];
    $res_wh = $conn->query("SELECT label_no FROM warehouse_items WHERE production_id = $prod_id");
    while ($r = $res_wh->fetch_assoc()) $in_warehouse[] = (int)$r['label_no'];

    $already_shipped = [];
    $res_shipped = $conn->query("SELECT label_no FROM distributor_shipments WHERE production_id = $prod_id");
    while ($r = $res_shipped->fetch_assoc()) $already_shipped[] = (int)$r['label_no'];

    if (count($in_warehouse) - count($already_shipped) <= 0) {
        die(json_encode(['status' => 'error', 'message' => 'Batch ini tidak memiliki stok tersedia']));
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'production_id' => (int)$prod['id'],
            'batch' => $prod['batch'],
            'item' => $prod['item'],
            'size' => $prod['size'] . ' ' . $prod['unit'],
            'copies' => (int)$prod['copies'],
            'input_method' => 'manual',
            'scanned_label' => null,
            'in_warehouse' => $in_warehouse,
            'already_shipped' => $already_shipped
        ]
    ]);
}
elseif ($action === 'submit_bulk') {
    $customer_name = isset($_POST['customer_name']) ? $conn->real_escape_string($_POST['customer_name']) : '';
    $customer_contact = isset($_POST['customer_contact']) ? $conn->real_escape_string($_POST['customer_contact']) : '';
    $customer_address = isset($_POST['customer_address']) ? $conn->real_escape_string($_POST['customer_address']) : '';
    $shipment_date = isset($_POST['shipment_date']) ? $conn->real_escape_string($_POST['shipment_date']) : date('Y-m-d');
    $cart_json = isset($_POST['cart']) ? $_POST['cart'] : '';
    $append_to = isset($_POST['append_to']) ? (int)$_POST['append_to'] : 0;
    $input_method = normalize_shipment_input_method($_POST['input_method'] ?? 'scan');

    $cart = json_decode($cart_json, true);

    if (empty($customer_name) || empty($cart)) {
        die(json_encode(['status' => 'error', 'message' => 'Data customer atau keranjang kosong!']));
    }

    // Hitung total quantity
    $total_qty = 0;
    foreach ($cart as $prod_id => $labels) {
        $total_qty += count($labels);
    }

    if ($total_qty === 0) die(json_encode(['status' => 'error', 'message' => 'Tidak ada dus yang dipilih!']));

    $conn->begin_transaction();
    try {
    if ($append_to == 0) {
    // Simpan ke Master Customer jika ini pengiriman baru
    $conn->query("INSERT INTO master_customers (name, contact, address, total_orders)
      VALUES ('$customer_name', '$customer_contact', '$customer_address', 1)
      ON DUPLICATE KEY UPDATE
        contact=VALUES(contact),
                            address=VALUES(address),
                        total_orders = total_orders + 1");
    }

        // 1. Hitung total aktual qty dan persiapkan data batch
    $total_actual_qty = 0;
    $batch_summaries = [];

        foreach ($cart as $prod_id => $labels) {
    $prod_id = (int)$prod_id;
    $label_count = count($labels);

    $p_res = $conn->query("SELECT quantity FROM production_labels WHERE id = $prod_id");
    $p_data = $p_res->fetch_assoc();

    $unit_count = $label_count * $p_data['quantity'];
    $total_actual_qty += $unit_count;

    $batch_summaries[] = [
        'production_id' => $prod_id,
            'label_qty' => $label_count,
                'unit_qty' => $unit_count
        ];
    }

    // 2. Insert Header (Nota) atau Update Header Lama
        if ($append_to > 0) {
        $shipment_id = $append_to;
        $existing_method = 'scan';
        $existing_res = $conn->query("SELECT input_method FROM outbound_shipments WHERE id = $shipment_id LIMIT 1");
        if ($existing_res && $existing_res->num_rows > 0) {
            $existing_method = $existing_res->fetch_assoc()['input_method'] ?? 'scan';
        }
        $final_input_method = $conn->real_escape_string(merge_shipment_input_methods($existing_method, $input_method));
        $conn->query("UPDATE outbound_shipments SET total_qty = total_qty + $total_qty, total_actual_qty = total_actual_qty + $total_actual_qty, input_method = '$final_input_method' WHERE id = $shipment_id");
    } else {
    $final_input_method = $conn->real_escape_string($input_method);
    $conn->query("INSERT INTO outbound_shipments (customer_name, customer_contact, customer_address, shipment_date, total_qty, shipped_by, total_actual_qty, input_method)
                  VALUES ('$customer_name', '$customer_contact', '$customer_address', '$shipment_date', $total_qty, '$user', $total_actual_qty, '$final_input_method')");
        $shipment_id = $conn->insert_id;
        }

    // 3. Insert atau Update Detail per Batch
        foreach ($batch_summaries as $b) {
        $s_id = $shipment_id;
    $p_id = $b['production_id'];
    $l_q = $b['label_qty'];
    $u_q = $b['unit_qty'];
            
    $chk = $conn->query("SELECT id FROM outbound_shipment_batches WHERE shipment_id = $s_id AND production_id = $p_id");
    if ($chk->num_rows > 0) {
    $conn->query("UPDATE outbound_shipment_batches SET label_qty = label_qty + $l_q, unit_qty = unit_qty + $u_q WHERE shipment_id = $s_id AND production_id = $p_id");
    } else {
    $conn->query("INSERT INTO outbound_shipment_batches (shipment_id, production_id, label_qty, unit_qty) VALUES ($s_id, $p_id, $l_q, $u_q)");
            }
    }

    // 4. Insert Detail Serialized (Label per Label)
    $stmt = $conn->prepare("INSERT INTO distributor_shipments (shipment_id, production_id, label_no) VALUES (?, ?, ?)");

    foreach ($cart as $prod_id => $labels) {
        $prod_id = (int)$prod_id;
            foreach ($labels as $label_no) {
                $label_no = (int)$label_no;

                // Pastikan tidak dobel
                $check = $conn->query("SELECT id FROM distributor_shipments WHERE production_id = $prod_id AND label_no = $label_no FOR UPDATE");
                if ($check->num_rows > 0) {
                    throw new Exception("Dus #$label_no pada Batch terkait sudah dikirim oleh proses lain.");
                }

                $stmt->bind_param("iii", $shipment_id, $prod_id, $label_no);
                $stmt->execute();
            }
        }

        // Log Aktivitas
        $stmtHeader = $conn->query("SELECT customer_name, shipped_at, shipment_date, total_qty FROM outbound_shipments WHERE id=$shipment_id");
        if ($stmtHeader && $headerData = $stmtHeader->fetch_assoc()) {
            $stmtSeq = $conn->prepare("SELECT COUNT(id) as seq FROM outbound_shipments WHERE shipment_date = ? AND id <= ?");
            $stmtSeq->bind_param("si", $headerData['shipment_date'], $shipment_id);
            $stmtSeq->execute();
            $seq = $stmtSeq->get_result()->fetch_assoc()['seq'];

            $datetime_str = date('dmYHi', strtotime($headerData['shipped_at']));
            $name_parts = explode(' ', trim($headerData['customer_name']));
            $initials = (count($name_parts) >= 2) ? strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1)) : strtoupper(substr(trim($headerData['customer_name']), 0, 2));
            $total_paket_all = $headerData['total_qty'];
            $no_resi = $seq . '-' . $datetime_str . '-' . $total_paket_all . '-' . $initials;
            
            if ($append_to > 0) {
                $conn->query("INSERT INTO activity_logs (action, details) VALUES ('PENGIRIMAN', 'Tambah susulan $total_qty dus ke No. Resi #$no_resi')");
            } else {
                $conn->query("INSERT INTO activity_logs (action, details) VALUES ('PENGIRIMAN', 'Kirim $total_qty dus ke {$headerData['customer_name']} (No. Resi #$no_resi)')");
            }
        } else {
            if ($append_to > 0) {
                $conn->query("INSERT INTO activity_logs (action, details) VALUES ('PENGIRIMAN', 'Tambah susulan $total_qty dus ke Nota #$shipment_id')");
            } else {
                $conn->query("INSERT INTO activity_logs (action, details) VALUES ('PENGIRIMAN', 'Kirim $total_qty dus ke $customer_name (Nota #$shipment_id)')");
            }
        }

        $conn->commit();
        session_write_close(); // Lepas lock session segera
        echo json_encode(['status' => 'success', 'message' => "Berhasil mengirim $total_qty dus ke $customer_name", 'shipment_id' => $shipment_id]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal simpan: ' . $e->getMessage()]);
    }
}
elseif ($action === 'history') {
    session_write_close();
    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');

    $currentMonth = date('m');
    $currentYear = date('Y');

    // Get Stats Bulan Ini
    $statsSql = "
        SELECT 
            COUNT(id) as total_pengiriman,
            COALESCE(SUM(total_qty), 0) as total_unit,
            COUNT(DISTINCT customer_name) as total_customer
        FROM outbound_shipments 
        WHERE MONTH(shipment_date) = '$currentMonth' AND YEAR(shipment_date) = '$currentYear'
    ";
    $statsRes = $conn->query($statsSql);
    $stats = $statsRes->fetch_assoc();

    $bulan_indonesia = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $bulan_ini = $bulan_indonesia[date('m')] . ' ' . date('Y');

    $sql = "SELECT s.*, 
                   (SELECT GROUP_CONCAT(CONCAT(p.item, ' (', p.size, ' ', p.unit, ')|', b.label_qty, '|', b.unit_qty) SEPARATOR ';') 
                    FROM outbound_shipment_batches b
                    JOIN production_labels p ON b.production_id = p.id 
                    WHERE b.shipment_id = s.id
                    GROUP BY b.shipment_id) as item_summary
            FROM outbound_shipments s 
            ORDER BY s.id DESC LIMIT 5";
    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) $data[] = $row;
    
    echo json_encode([
        'data' => $data,
        'stats' => [
            'total_pengiriman' => (int)($stats['total_pengiriman'] ?? 0),
            'total_unit' => (int)($stats['total_unit'] ?? 0),
            'total_customer' => (int)($stats['total_customer'] ?? 0),
            'bulan' => $bulan_ini
        ]
    ]);
}
?>
