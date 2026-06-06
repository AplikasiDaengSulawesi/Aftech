<?php
/**
 * Endpoint API Mobile untuk memperbarui status antrian cetak.
 * URL: /api/mobile/update_print_queue-mobile.php
 * Method: POST
 * Autentikasi: Diperlukan (API Key atau Session Web)
 */

header('Content-Type: application/json');
include '../config.php';
verify_api_access();

// Ambil payload JSON dari body request
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['queue_ids']) || !is_array($data['queue_ids']) || empty($data['queue_ids'])) {
    echo json_encode(["status" => "error", "message" => "Input tidak valid. Wajib menyertakan array queue_ids."]);
    exit;
}

// Sanitasi input untuk memastikan semuanya berupa integer (keamanan dari SQL Injection)
$queue_ids = array_map('intval', $data['queue_ids']);
$ids_string = implode(',', $queue_ids);

$now = date('Y-m-d H:i:s');

try {
    $sql = "UPDATE print_queues
            SET status = 'printed', printed_at = '$now'
            WHERE id IN ($ids_string) AND status = 'pending'";
    if ($conn->query($sql)) {
        echo json_encode([
            "status" => "success",
            "message" => "Status antrian berhasil diupdate",
            "updated_at" => $now
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal update antrian: " . $conn->error]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Gagal update antrian: " . $e->getMessage()]);
}
?>
