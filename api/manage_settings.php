<?php
session_start();
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$action = $_GET['action'] ?? '';
$admin_name = $_SESSION['full_name'] ?? 'Admin';

$response = ['status' => 'error', 'message' => 'Unknown action'];

if ($action == 'save') {
    $id = $_POST['id'] ?? '';
    $type = $_GET['type'] ?? '';
    $sql = "";
    $log_detail = "";

    if ($type == 'item') {
        $name = $conn->real_escape_string($_POST['name']);
        $u_id = (int)$_POST['unit_id'];
        $sql = (!empty($id)) ? "UPDATE master_items SET name='$name', unit_id=$u_id WHERE id=$id" : "INSERT INTO master_items (name, unit_id) VALUES ('$name', $u_id)";
        $log_detail = "Admin Update Item: $name";
    } elseif ($type == 'size') {
        $val = $conn->real_escape_string($_POST['size_value']);
        $i_id = (int)$_POST['item_id'];
        $sql = (!empty($id)) ? "UPDATE master_sizes SET size_value='$val', item_id=$i_id WHERE id=$id" : "INSERT INTO master_sizes (size_value, item_id) VALUES ('$val', $i_id)";
        $log_detail = "Admin Update Size: $val";
    } elseif ($type == 'quantity') {
        $val = $conn->real_escape_string($_POST['qty_value']);
        $m_id = (int)$_POST['machine_id'];
        $sql = (!empty($id)) ? "UPDATE master_quantities SET qty_value='$val', machine_id=$m_id WHERE id=$id" : "INSERT INTO master_quantities (qty_value, machine_id) VALUES ('$val', $m_id)";
        $log_detail = "Admin Update Qty: $val";
    } elseif ($type == 'machine') {
        $name = $conn->real_escape_string($_POST['name']);
        $status = $conn->real_escape_string($_POST['status'] ?? 'active');
        $sql = (!empty($id)) ? "UPDATE master_machines SET name='$name', status='$status' WHERE id=$id" : "INSERT INTO master_machines (name, status) VALUES ('$name', '$status')";
        $log_detail = "Admin Update Mesin: $name ($status)";
    } elseif ($type == 'unit') {
        $name = $conn->real_escape_string($_POST['name']);
        $sql = (!empty($id)) ? "UPDATE master_units SET name='$name' WHERE id=$id" : "INSERT INTO master_units (name) VALUES ('$name')";
        $log_detail = "Admin Update Unit: $name";
    } elseif ($type == 'shift') {
        $name = $conn->real_escape_string($_POST['name']);
        $sql = (!empty($id)) ? "UPDATE master_shifts SET name='$name' WHERE id=$id" : "INSERT INTO master_shifts (name) VALUES ('$name')";
        $log_detail = "Admin Update Shift: $name";
    } elseif ($type == 'template') {
        $name = $conn->real_escape_string($_POST['template_name']);
        $item = $conn->real_escape_string($_POST['item']);
        $size = $conn->real_escape_string($_POST['size']);
        $unit = $conn->real_escape_string($_POST['unit']);
        $machine = $conn->real_escape_string($_POST['machine']);
        $shift = $conn->real_escape_string($_POST['shift']);
        $quantity = $conn->real_escape_string($_POST['quantity']);
        $sql = (!empty($id)) 
            ? "UPDATE master_templates SET template_name='$name', item='$item', size='$size', unit='$unit', machine='$machine', shift='$shift', quantity='$quantity' WHERE id=$id" 
            : "INSERT INTO master_templates (template_name, item, size, unit, machine, shift, quantity) VALUES ('$name', '$item', '$size', '$unit', '$machine', '$shift', '$quantity')";
        $log_detail = "Admin Update Template: $name";
    } elseif ($type == 'api_key') {
        $device_name = $conn->real_escape_string($_POST['device_name']);
        // Generate a random 4-block key
        $new_key = 'AFTECH-' . strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . strtoupper(substr(md5(uniqid()), 4, 4)) . '-' . date('Y');
        $sql = "INSERT INTO api_keys (device_name, api_key) VALUES ('$device_name', '$new_key')";
        $log_detail = "Admin Generate API Key untuk: $device_name";
    } elseif ($type == 'user') {
        $username = $conn->real_escape_string($_POST['username']);
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $role_user = $conn->real_escape_string($_POST['role']);
        $pass = $_POST['password'] ?? '';
        if (!empty($id)) {
            $sql = "UPDATE users SET username='$username', full_name='$full_name', role='$role_user' WHERE id=$id";
            if (!empty($pass)) { $h = password_hash($pass, PASSWORD_DEFAULT); $sql = "UPDATE users SET username='$username', full_name='$full_name', role='$role_user', password='$h' WHERE id=$id"; }
        } else {
            $h = password_hash($pass ?: $username.'123', PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, full_name, role, password) VALUES ('$username', '$full_name', '$role_user', '$h')";
        }
        $log_detail = "Admin Update User: $username";
    } elseif ($type == 'customer') {
        $name = $conn->real_escape_string($_POST['name']);
        $contact = $conn->real_escape_string($_POST['contact']);
        $address = $conn->real_escape_string($_POST['address']);
        $sql = (!empty($id)) ? "UPDATE master_customers SET name='$name', contact='$contact', address='$address' WHERE id=$id" : "INSERT INTO master_customers (name, contact, address) VALUES ('$name', '$contact', '$address')";
        $log_detail = "Admin Update Customer: $name";
    } elseif ($type == 'production') {
        $item = $conn->real_escape_string($_POST['item']);
        $size = $conn->real_escape_string($_POST['size']);
        $unit = $conn->real_escape_string($_POST['unit']);
        $qty = $conn->real_escape_string($_POST['quantity']);
        $copies = (int)$_POST['copies'];
        $machine = $conn->real_escape_string($_POST['machine']);
        $shift = $conn->real_escape_string($_POST['shift']);
        $operator = $conn->real_escape_string($_POST['operator']);
        $qc = $conn->real_escape_string($_POST['qc']);
        $p_date = $conn->real_escape_string($_POST['production_date']);
        $p_time = $conn->real_escape_string($_POST['production_time']);
        
        // --- FIX FORMAT WAKTU (Pastikan ada detik HH:MM:SS) ---
        if (strlen($p_time) == 5) { $p_time .= ":00"; }
        
        $batch = $conn->real_escape_string($_POST['batch'] ?? '');
        $sql = "UPDATE production_labels SET 
                item='$item', size='$size', unit='$unit', quantity='$qty', copies=$copies, 
                machine='$machine', shift='$shift', operator='$operator', qc='$qc', 
                production_date='$p_date', production_time='$p_time' 
                WHERE id=$id";
        $log_detail = "Koreksi Data Produksi: Batch #$batch ($item)";
    }

    if ($sql && $conn->query($sql)) {
        $log_action = (!empty($id)) ? 'EDIT' : 'TAMBAH';
        $conn->query("INSERT INTO activity_logs (action, details) VALUES ('$log_action', '$log_detail')");
        $response = ['status' => 'success'];
    } else {
        $response = ['status' => 'error', 'message' => $conn->error ?: 'Query error'];
    }
} elseif ($action == 'transfer_partial_warehouse') {
    $prod_id = (int)$_POST['id'];
    $labels = json_decode($_POST['labels'], true);
    if (empty($labels)) { $response = ['status' => 'error', 'message' => 'No labels selected']; }
    else {
        $conn->begin_transaction();
        try {
            $batch_res = $conn->query("SELECT batch FROM production_labels WHERE id=$prod_id");
            $batch_name = ($batch_res && $b_row = $batch_res->fetch_assoc()) ? $b_row['batch'] : $prod_id;
            
            foreach ($labels as $no) {
                $conn->query("INSERT IGNORE INTO warehouse_items (production_id, label_no, transferred_by) VALUES ($prod_id, $no, '$admin_name')");
            }
            $conn->query("INSERT IGNORE INTO warehouse_transfers (production_id, transferred_by) VALUES ($prod_id, '$admin_name')");
            $conn->query("INSERT INTO activity_logs (action, details) VALUES ('TRANSFER', 'Kirim ".count($labels)." unit Batch #$batch_name ke Gudang')");
            $conn->commit();
            $response = ['status' => 'success'];
        } catch (Exception $e) { $conn->rollback(); $response = ['status' => 'error', 'message' => $e->getMessage()]; }
    }
} elseif ($action == 'return_from_warehouse') {
    $prod_id = (int)$_POST['id'];
    $labels = json_decode($_POST['labels'], true);
    if (!empty($labels)) {
        $label_list = implode(',', array_map('intval', $labels));
        if ($conn->query("DELETE FROM warehouse_items WHERE production_id=$prod_id AND label_no IN ($label_list)")) {
            $batch_res = $conn->query("SELECT batch FROM production_labels WHERE id=$prod_id");
            $batch_name = ($batch_res && $b_row = $batch_res->fetch_assoc()) ? $b_row['batch'] : $prod_id;
            
            $conn->query("INSERT INTO activity_logs (action, details) VALUES ('RETUR', 'Kembalikan ".count($labels)." unit Batch #$batch_name ke Produksi')");
            $response = ['status' => 'success'];
        }
    }
} elseif ($action == 'approve_api_key') {
    $id = (int)$_POST['id'];
    $pin = $conn->real_escape_string($_POST['reset_pin'] ?? '0503');
    $new_key = 'AFTECH-' . strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . strtoupper(substr(md5(uniqid()), 4, 4)) . '-' . date('Y');
    $sql = "UPDATE api_keys SET api_key='$new_key', reset_pin='$pin', status='approved', is_active=1 WHERE id=$id";
    
    if ($conn->query($sql)) {
        $res = $conn->query("SELECT device_name FROM api_keys WHERE id=$id");
        $d_name = ($res && $r = $res->fetch_assoc()) ? $r['device_name'] : $id;
        $conn->query("INSERT INTO activity_logs (action, details) VALUES ('SETUJU', 'Admin Menyetujui Akses Perangkat: $d_name (PIN Reset: $pin)')");
        $response = ['status' => 'success'];
    } else {
        $response = ['status' => 'error', 'message' => $conn->error];
    }
} elseif ($action == 'update_reset_pin') {
    $id = (int)$_POST['id'];
    $pin = $conn->real_escape_string($_POST['reset_pin']);
    $sql = "UPDATE api_keys SET reset_pin='$pin' WHERE id=$id";
    
    if ($conn->query($sql)) {
        $res = $conn->query("SELECT device_name FROM api_keys WHERE id=$id");
        $d_name = ($res && $r = $res->fetch_assoc()) ? $r['device_name'] : $id;
        $conn->query("INSERT INTO activity_logs (action, details) VALUES ('EDIT', 'Admin Update PIN Reset Perangkat: $d_name menjadi $pin')");
        $response = ['status' => 'success'];
    } else {
        $response = ['status' => 'error', 'message' => $conn->error];
    }
} elseif ($action == 'save_role_permissions') {
    $perms = json_decode($_POST['permissions'], true);
    if (!empty($perms)) {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM role_permissions");
            foreach ($perms as $p) {
                $role = $conn->real_escape_string($p['role']);
                $page = $conn->real_escape_string($p['page']);
                $conn->query("INSERT INTO role_permissions (role, page_slug) VALUES ('$role', '$page')");
            }
            $conn->query("INSERT INTO activity_logs (action, details) VALUES ('EDIT', 'Admin Memperbarui Hak Akses Role (Permissions)')");
            $conn->commit();
            $response = ['status' => 'success'];
        } catch (Exception $e) { $conn->rollback(); $response = ['status' => 'error', 'message' => $e->getMessage()]; }
    }
} elseif ($action == 'save_app_settings') {
    $qc_enabled = (int)$_POST['qc_checker_enabled'];
    $sql = "INSERT INTO app_settings (setting_key, setting_value) VALUES ('qc_checker_enabled', '$qc_enabled') 
            ON DUPLICATE KEY UPDATE setting_value='$qc_enabled'";
    
    if ($conn->query($sql)) {
        $status_text = $qc_enabled ? 'Aktif' : 'Non-Aktif';
        $conn->query("INSERT INTO activity_logs (action, details) VALUES ('EDIT', 'Admin Update QC Checker menjadi $status_text')");

        // Jika QC dimatikan, otomatis masukkan SEMUA label yang belum ada di gudang
        $auto_transferred = 0;
        $auto_batches = 0;
        $transfer_details = []; // Detail per-batch untuk laporan
        if (!$qc_enabled) {
            $conn->begin_transaction();
            try {
                // Ambil semua batch produksi lengkap dengan info produk
                $res_prod = $conn->query("SELECT id, batch, item, size, unit, quantity, copies, machine, production_date FROM production_labels ORDER BY production_date DESC, batch ASC");
                while ($prod = $res_prod->fetch_assoc()) {
                    $prod_id = (int)$prod['id'];
                    $copies = (int)$prod['copies'];
                    
                    // Hitung yang sudah ada di gudang
                    $existing_res = $conn->query("SELECT COUNT(*) as cnt FROM warehouse_items WHERE production_id = $prod_id");
                    $existing_count = ($existing_res && $row = $existing_res->fetch_assoc()) ? (int)$row['cnt'] : 0;
                    
                    $batch_new_count = 0;
                    
                    for ($i = 1; $i <= $copies; $i++) {
                        $conn->query("INSERT IGNORE INTO warehouse_items (production_id, label_no, transferred_by) VALUES ($prod_id, $i, 'Auto-System')");
                        if ($conn->affected_rows > 0) {
                            $auto_transferred++;
                            $batch_new_count++;
                        }
                    }
                    
                    if ($batch_new_count > 0) {
                        $auto_batches++;
                        $conn->query("INSERT IGNORE INTO warehouse_transfers (production_id, transferred_by) VALUES ($prod_id, 'Auto-System')");
                        
                        // Simpan detail untuk laporan
                        $transfer_details[] = [
                            'batch' => $prod['batch'],
                            'item' => $prod['item'],
                            'size' => $prod['size'],
                            'unit' => $prod['unit'],
                            'quantity' => $prod['quantity'],
                            'machine' => $prod['machine'],
                            'production_date' => $prod['production_date'],
                            'total_copies' => $copies,
                            'already_in_warehouse' => $existing_count,
                            'auto_transferred' => $batch_new_count
                        ];
                    }
                }
                
                if ($auto_transferred > 0) {
                    $conn->query("INSERT INTO activity_logs (action, details) VALUES ('TRANSFER', 'Auto-System: QC dimatikan, $auto_transferred dus dari $auto_batches batch otomatis masuk Gudang')");
                }
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
            }
        }

        $response = ['status' => 'success', 'auto_transferred' => $auto_transferred, 'auto_batches' => $auto_batches, 'transfer_details' => $transfer_details];
    } else {
        $response = ['status' => 'error', 'message' => $conn->error];
    }
} elseif ($action == 'clear_logs') {
    if ($conn->query("TRUNCATE TABLE activity_logs")) {
        $response = ['status' => 'success'];
    } else {
        $response = ['status' => 'error', 'message' => $conn->error];
    }
} elseif ($action == 'delete') {
    $id = (int)$_POST['id'];
    $type = $_GET['type'] ?? '';
    $table = ""; $where = "id=$id";
    
    if ($type == 'item') $table = "master_items";
    elseif ($type == 'machine') $table = "master_machines";
    elseif ($type == 'customer') $table = "master_customers";
    elseif ($type == 'unit') $table = "master_units";
    elseif ($type == 'shift') $table = "master_shifts";
    elseif ($type == 'template') $table = "master_templates";
    elseif ($type == 'size') $table = "master_sizes";
    elseif ($type == 'quantity') $table = "master_quantities";
    elseif ($type == 'user') $table = "users";
    elseif ($type == 'production') $table = "production_labels";
    elseif ($type == 'api_key') $table = "api_keys";
    elseif ($type == 'warehouse_batch') { $table = "warehouse_items"; $where = "production_id=$id"; }
    elseif ($type == 'shipment_item') {
        $shipment_id = (int)$_POST['shipment_id'];
        $prod_id = (int)$_POST['production_id'];

        $conn->begin_transaction();
        try {
            // 1. Ambil info Batch String untuk log
            $resB = $conn->query("SELECT batch FROM production_labels WHERE id=$prod_id");
            $batchStr = ($resB && $rowB = $resB->fetch_assoc()) ? $rowB['batch'] : $prod_id;

            // 2. Hitung jumlah yang akan di-return (untuk log)
            $resCount = $conn->query("SELECT COUNT(*) as total FROM distributor_shipments WHERE shipment_id=$shipment_id AND production_id=$prod_id");
            $qty = ($resCount && $rowCount = $resCount->fetch_assoc()) ? $rowCount['total'] : 0;

            if ($qty == 0) throw new Exception("Data rincian tidak ditemukan.");

            // 3. Hapus data label individual agar kembali ke stok
            $conn->query("DELETE FROM distributor_shipments WHERE shipment_id=$shipment_id AND production_id=$prod_id");

            // 4. Hapus rincian batch dari tabel bantu
            $conn->query("DELETE FROM outbound_shipment_batches WHERE shipment_id=$shipment_id AND production_id=$prod_id");

            // 5. Update total_qty di header nota
            $conn->query("UPDATE outbound_shipments SET total_qty = (SELECT COALESCE(SUM(label_qty), 0) FROM outbound_shipment_batches WHERE shipment_id=$shipment_id) WHERE id=$shipment_id");

            $conn->query("INSERT INTO activity_logs (action, details) VALUES ('RETUR', 'Return $qty dus Batch #$batchStr dari Nota ID #$shipment_id ke Gudang')");
            
            $conn->commit();
            echo json_encode(['status' => 'success', 'parent_id' => $shipment_id]);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
    if ($type == 'shipment' || $type == 'distributor_shipment') {
    $conn->begin_transaction();
    try {
    // Ambil data header untuk log resi
    $stmtHeader = $conn->query("SELECT customer_name, shipped_at, shipment_date, total_qty FROM outbound_shipments WHERE id=$id");
    $headerData = $stmtHeader->fetch_assoc();
    
    $log_msg = "Batalkan Pengiriman Nota ID #$id";
    if ($headerData) {
    // Generate nomor resi
        $stmtSeq = $conn->prepare("SELECT COUNT(id) as seq FROM outbound_shipments WHERE shipment_date = ? AND id <= ?");
                $stmtSeq->bind_param("si", $headerData['shipment_date'], $id);
        $stmtSeq->execute();
        $seqData = $stmtSeq->get_result()->fetch_assoc();
        $seq = $seqData['seq'];

                $datetime_str = date('dmYHi', strtotime($headerData['shipped_at']));
                $name_parts = explode(' ', trim($headerData['customer_name']));
                $initials = (count($name_parts) >= 2) ? strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1)) : strtoupper(substr(trim($headerData['customer_name']), 0, 2));
                $total_paket = $headerData['total_qty'];
                $no_resi = $seq . '-' . $datetime_str . '-' . $total_paket . '-' . $initials;
                
                $log_msg = "Batalkan Pengiriman No. Resi #$no_resi ke " . $headerData['customer_name'];
            }

            // Hapus detailSerialized (Label per label) agar kembali ke stok gudang
            $conn->query("DELETE FROM distributor_shipments WHERE shipment_id=$id");
            // Hapus detail rincian batch (Tabel bantu)
            $conn->query("DELETE FROM outbound_shipment_batches WHERE shipment_id=$id");
            // Hapus header nota
            if (!$conn->query("DELETE FROM outbound_shipments WHERE id=$id")) {
                throw new Exception($conn->error);
            }

            // Kurangi total_orders di master_customers
            $conn->query("UPDATE master_customers SET total_orders = GREATEST(0, total_orders - 1) WHERE name = '" . $conn->real_escape_string($headerData['customer_name']) . "'");

            $conn->query("INSERT INTO activity_logs (action, details) VALUES ('HAPUS', '$log_msg')");
            $conn->commit();
            echo json_encode(['status' => 'success']);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($table) {
        $name_field = 'name';
        if ($type == 'size') $name_field = 'size_value';
        elseif ($type == 'quantity') $name_field = 'qty_value';
        elseif ($type == 'user') $name_field = 'username';
        elseif ($type == 'production') $name_field = 'batch';
        elseif ($type == 'template') $name_field = 'template_name';
        elseif ($type == 'api_key') $name_field = 'device_name';
        
        $log_delete_detail = "Hapus $type ID $id";
        if ($type == 'warehouse_batch') {
            $res = $conn->query("SELECT batch FROM production_labels WHERE id=$id");
            if ($res && $row = $res->fetch_assoc()) $log_delete_detail = "Hapus Stok Gudang Batch: #" . $row['batch'];
        } else {
            $res = $conn->query("SELECT $name_field FROM $table WHERE $where");
            if ($res && $row = $res->fetch_assoc()) {
                if ($type == 'production') $log_delete_detail = "Hapus Produksi Batch: #" . $row[$name_field];
                else $log_delete_detail = "Hapus " . ucfirst($type) . ": " . $row[$name_field];
            }
        }
        
        if ($conn->query("DELETE FROM $table WHERE $where")) {
            if ($type == 'warehouse_batch') $conn->query("DELETE FROM warehouse_transfers WHERE production_id=$id");
            $conn->query("INSERT INTO activity_logs (action, details) VALUES ('HAPUS', '$log_delete_detail')");
            $response = ['status' => 'success'];
        }
    }
}

echo json_encode($response);
?>
