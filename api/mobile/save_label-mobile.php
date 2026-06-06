<?php
include '../config.php';
verify_api_access();
require_once __DIR__ . '/../../includes/mobile_production_sync_helper.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Input tidak valid. Payload JSON tidak ditemukan."]);
    exit;
}

$requiredFields = ['item', 'size', 'unit', 'batch', 'machine', 'shift', 'quantity', 'operator', 'qc', 'production_date', 'production_time', 'copies'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
        echo json_encode(["status" => "error", "message" => "Input tidak valid. Field $field wajib diisi."]);
        exit;
    }
}

$input_method_raw = normalizeMobileInputMethod($data['input_method'] ?? 'scan');
$item = $conn->real_escape_string(trim((string) $data['item']));
$size = $conn->real_escape_string(trim((string) $data['size']));
$unit = $conn->real_escape_string(trim((string) $data['unit']));
$batch = $conn->real_escape_string(trim((string) $data['batch']));
$machine = $conn->real_escape_string(trim((string) $data['machine']));
$shift = $conn->real_escape_string(trim((string) $data['shift']));
$quantity = $conn->real_escape_string(trim((string) $data['quantity']));
$operator = $conn->real_escape_string(trim((string) $data['operator']));
$qc = $conn->real_escape_string(trim((string) $data['qc']));
$device = $conn->real_escape_string(trim((string) ($data['device_model'] ?? 'Unknown')));
$device_id = $conn->real_escape_string(trim((string) ($data['device_id'] ?? '')));
$formattedDate = normalizeMobileProductionDate((string) $data['production_date']);
$time = $conn->real_escape_string(trim((string) $data['production_time']));
$copies = (int) $data['copies'];

if ($formattedDate === null) {
    echo json_encode(["status" => "error", "message" => "Input tidak valid. production_date harus berformat dd-MM-yyyy."]);
    exit;
}

if ($copies <= 0) {
    echo json_encode(["status" => "error", "message" => "Input tidak valid. copies harus lebih besar dari 0."]);
    exit;
}

$conn->begin_transaction();

try {
    $curr_copies = 0;
    $prodId = 0;

    $resBatch = $conn->query("SELECT id, copies FROM production_labels WHERE batch='$batch' LIMIT 1 FOR UPDATE");
    if ($resBatch === false) {
        throw new RuntimeException($conn->error);
    }

    if ($resBatch->num_rows > 0) {
        $rowBatch = $resBatch->fetch_assoc();
        $curr_copies = (int) $rowBatch['copies'];
        $prodId = (int) $rowBatch['id'];
    }

    $sql = "INSERT INTO production_labels (item, size, unit, batch, machine, shift, quantity, operator, qc, production_date, production_time, copies, device_model, device_id)
            VALUES ('$item', '$size', '$unit', '$batch', '$machine', '$shift', '$quantity', '$operator', '$qc', '$formattedDate', '$time', $copies, '$device', '$device_id')
            ON DUPLICATE KEY UPDATE
            copies = copies + VALUES(copies),
            shift = VALUES(shift),
            qc = VALUES(qc),
            production_time = VALUES(production_time),
            device_model = VALUES(device_model),
            device_id = VALUES(device_id)";

    if ($conn->query($sql) !== true) {
        throw new RuntimeException($conn->error);
    }

    if ($prodId === 0) {
        $prodId = (int) $conn->insert_id;
    }

    $range = buildMobileLabelRange($curr_copies, $copies, $batch);
    $label_nos = $range['label_nos'];
    $qr_codes = $range['qr_codes'];

    foreach ($label_nos as $index => $label_no) {
        $qr = $qr_codes[$index];
        if ($conn->query("INSERT INTO print_queues (production_id, batch, label_no, qr_code) VALUES ($prodId, '$batch', $label_no, '$qr')") !== true) {
            throw new RuntimeException($conn->error);
        }
    }

    $qc_check_res = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key='qc_checker_enabled'");
    if ($qc_check_res === false) {
        throw new RuntimeException($conn->error);
    }

    $is_qc_enabled = ($row = $qc_check_res->fetch_assoc()) ? (int) $row['setting_value'] : 0;

    if (!$is_qc_enabled) {
        if ($conn->query("INSERT IGNORE INTO warehouse_transfers (production_id, transferred_by) VALUES ($prodId, 'Auto-System')") !== true) {
            throw new RuntimeException($conn->error);
        }

        foreach ($label_nos as $label_no) {
            if ($conn->query("INSERT IGNORE INTO warehouse_items (production_id, label_no, transferred_by, input_method) VALUES ($prodId, $label_no, 'Auto-System', '$input_method_raw')") !== true) {
                throw new RuntimeException($conn->error);
            }
        }
    }

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Berhasil Disimpan",
        "production_id" => $prodId,
        "batch" => $batch,
        "copies" => $copies,
        "first_label_no" => $range['first_label_no'],
        "last_label_no" => $range['last_label_no'],
        "input_method" => $input_method_raw,
        "label_nos" => $label_nos,
        "qr_codes" => $qr_codes,
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
