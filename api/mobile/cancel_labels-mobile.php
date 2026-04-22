<?php
include '../config.php';
verify_api_access();
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['production_id']) || !isset($data['label_nos']) || !is_array($data['label_nos'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Input tidak valid. Wajib: production_id (int), label_nos (array of int)."
    ]);
    exit;
}

$production_id = (int)$data['production_id'];
$label_nos     = array_values(array_unique(array_map('intval', $data['label_nos'])));
$reason        = $conn->real_escape_string(trim($data['reason'] ?? ''));
$cancelled_by  = $conn->real_escape_string(trim($data['cancelled_by'] ?? ($_SESSION['full_name'] ?? 'Mobile User')));
$device_id_raw = trim((string)($data['device_id'] ?? ''));
$device_id     = $device_id_raw !== '' ? $device_id_raw : null;

if ($production_id <= 0 || empty($label_nos)) {
    echo json_encode(["status" => "error", "message" => "production_id / label_nos kosong."]);
    exit;
}

// Ambil data batch produksi
$res = $conn->query("SELECT id, batch, copies FROM production_labels WHERE id = $production_id LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Batch produksi tidak ditemukan."]);
    exit;
}
$prod      = $res->fetch_assoc();
$copies    = (int)$prod['copies'];
$batch_str = $prod['batch'];

$summary = [
    'cancelled_production' => 0,
    'cancelled_warehouse'  => 0,
    'blocked_shipped'      => 0,
    'skipped_duplicate'    => 0,
    'out_of_range'         => 0,
];
$details = [];

$conn->begin_transaction();
try {
    $stmt_ins = $conn->prepare(
        "INSERT INTO cancelled_labels (production_id, label_no, category, reason, cancelled_by, device_id)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($label_nos as $label_no) {
        if ($label_no < 1 || $label_no > $copies) {
            $summary['out_of_range']++;
            $details[] = [
                'label_no' => $label_no,
                'action'   => 'out_of_range',
                'reason'   => "di luar jangkauan (1..$copies)"
            ];
            continue;
        }

        // Sudah dibatalkan sebelumnya?
        $chk_cancel = $conn->query(
            "SELECT id, category FROM cancelled_labels
             WHERE production_id = $production_id AND label_no = $label_no LIMIT 1"
        );
        if ($chk_cancel && $chk_cancel->num_rows > 0) {
            $row = $chk_cancel->fetch_assoc();
            $summary['skipped_duplicate']++;
            $details[] = [
                'label_no' => $label_no,
                'action'   => 'skipped',
                'reason'   => "sudah pernah dibatalkan (category={$row['category']})"
            ];
            continue;
        }

        // Sudah dikirim ke distributor? → BLOKIR
        $chk_ship = $conn->query(
            "SELECT id FROM distributor_shipments
             WHERE production_id = $production_id AND label_no = $label_no LIMIT 1"
        );
        if ($chk_ship && $chk_ship->num_rows > 0) {
            $summary['blocked_shipped']++;
            $details[] = [
                'label_no' => $label_no,
                'action'   => 'blocked',
                'reason'   => 'sudah dikirim ke distributor, gunakan proses retur'
            ];
            continue;
        }

        // Sudah masuk gudang? → category=warehouse + hapus dari warehouse_items
        $chk_wh = $conn->query(
            "SELECT id FROM warehouse_items
             WHERE production_id = $production_id AND label_no = $label_no LIMIT 1"
        );

        if ($chk_wh && $chk_wh->num_rows > 0) {
            $conn->query(
                "DELETE FROM warehouse_items
                 WHERE production_id = $production_id AND label_no = $label_no"
            );
            $category = 'warehouse';
            $summary['cancelled_warehouse']++;
        } else {
            $category = 'production';
            $summary['cancelled_production']++;
        }

        $stmt_ins->bind_param('iissss', $production_id, $label_no, $category, $reason, $cancelled_by, $device_id);
        $stmt_ins->execute();

        $details[] = [
            'label_no' => $label_no,
            'action'   => 'cancelled',
            'category' => $category
        ];
    }

    // Catat ke activity_logs hanya jika ada yang benar-benar dibatalkan
    $total_cancelled = $summary['cancelled_production'] + $summary['cancelled_warehouse'];
    if ($total_cancelled > 0) {
        $reason_log = $reason !== '' ? " | reason: $reason" : '';
        $detail_log =
            "Batal $total_cancelled label batch #$batch_str "
          . "(production={$summary['cancelled_production']}, "
          . "warehouse={$summary['cancelled_warehouse']}) "
          . "oleh $cancelled_by" . $reason_log;
        $detail_log_esc = $conn->real_escape_string($detail_log);
        $conn->query("INSERT INTO activity_logs (action, details) VALUES ('BATAL_LABEL', '$detail_log_esc')");
    }

    $conn->commit();

    echo json_encode([
        'status'        => 'success',
        'production_id' => $production_id,
        'batch'         => $batch_str,
        'summary'       => $summary,
        'details'       => $details
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal membatalkan label: ' . $e->getMessage()
    ]);
}
