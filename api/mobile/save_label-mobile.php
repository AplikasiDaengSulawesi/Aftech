<?php
include '../config.php';
verify_api_access();

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $item     = $conn->real_escape_string($data['item']);
    $size     = $conn->real_escape_string($data['size']);
    $unit     = $conn->real_escape_string($data['unit']);
    $batch    = $conn->real_escape_string($data['batch']);
    $machine  = $conn->real_escape_string($data['machine']);
    $shift    = $conn->real_escape_string($data['shift']);
    $quantity = $conn->real_escape_string($data['quantity']);
    $operator = $conn->real_escape_string($data['operator']);
    $qc       = $conn->real_escape_string($data['qc']);
    $device   = $conn->real_escape_string($data['device_model'] ?? 'Unknown');
    
    // --- FIX: KONVERSI TANGGAL dd-MM-yyyy ke yyyy-MM-dd ---
    $rawDate = $data['production_date']; // Contoh: 06-03-2026
    $dateObj = DateTime::createFromFormat('d-m-Y', $rawDate);
    $formattedDate = $dateObj ? $dateObj->format('Y-m-d') : date('Y-m-d');
    
    $time     = $conn->real_escape_string($data['production_time']);
    $copies   = (int)$data['copies'];

    // Cek jumlah copies sebelum INSERT (jika batch sudah ada)
    $curr_copies = 0;
    $prodId = 0;
    $resBatch = $conn->query("SELECT id, copies FROM production_labels WHERE batch='$batch' LIMIT 1");
    if ($resBatch && $resBatch->num_rows > 0) {
        $rowBatch = $resBatch->fetch_assoc();
        $curr_copies = (int)$rowBatch['copies'];
        $prodId = (int)$rowBatch['id'];
    }

    $sql = "INSERT INTO production_labels (item, size, unit, batch, machine, shift, quantity, operator, qc, production_date, production_time, copies, device_model) 
            VALUES ('$item', '$size', '$unit', '$batch', '$machine', '$shift', '$quantity', '$operator', '$qc', '$formattedDate', '$time', $copies, '$device')
            ON DUPLICATE KEY UPDATE 
            copies = copies + VALUES(copies), 
            shift = VALUES(shift),
            qc = VALUES(qc),
            production_time = '$time',
            device_model = '$device'";

    if ($conn->query($sql) === TRUE) {
        if ($prodId === 0) {
            $prodId = $conn->insert_id;
        }

        // Cek status QC Checker
        $qc_check_res = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key='qc_checker_enabled'");
        $is_qc_enabled = ($qc_check_res && $row = $qc_check_res->fetch_assoc()) ? (int)$row['setting_value'] : 0; 

        // Jika QC dimatikan, otomatis masuk ke gudang
        if (!$is_qc_enabled) {
            $conn->begin_transaction();
            try {
                $conn->query("INSERT IGNORE INTO warehouse_transfers (production_id, transferred_by) VALUES ($prodId, 'Auto-System')");
                
                for ($i = 1; $i <= $copies; $i++) {
                    $label_no = $curr_copies + $i;
                    $conn->query("INSERT IGNORE INTO warehouse_items (production_id, label_no, transferred_by) VALUES ($prodId, $label_no, 'Auto-System')");
                }
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
            }
        }

        echo json_encode(["status" => "success", "message" => "Berhasil Disimpan"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
?>
