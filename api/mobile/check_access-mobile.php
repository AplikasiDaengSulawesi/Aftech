<?php
header('Content-Type: application/json');
include '../config.php';

// Mendukung POST (JSON) dan GET (Query Param)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$device_uuid = $conn->real_escape_string($input['device_uuid'] ?? $_GET['device_uuid'] ?? '');
$device_name = $conn->real_escape_string($input['device_name'] ?? $_GET['device_name'] ?? '');

if (empty($device_uuid)) {
    echo json_encode(["status" => "error", "message" => "Device UUID required"]);
    exit;
}

// 1. Cari semua record dengan UUID ini
$check_all = $conn->query("SELECT id, device_name, status, api_key, reset_pin FROM api_keys WHERE device_uuid = '$device_uuid'");
$records = [];
while($r = $check_all->fetch_assoc()) { $records[] = $r; }

$target_record = null;

if (count($records) === 1) {
    // Hanya ada 1 perangkat dengan UUID ini (Aman/Unik)
    $target_record = $records[0];
} else if (count($records) > 1) {
    // ADA KONFLIK: Lebih dari 1 nama perangkat menggunakan UUID yang sama
    // Jika APK mengirim Nama, kita filter. Jika APK tidak mengirim Nama, kita tolak demi keamanan.
    if (!empty($device_name)) {
        foreach($records as $r) {
            if ($r['device_name'] === $device_name) {
                $target_record = $r;
                break;
            }
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Conflict: Multiple devices with same UUID. Please delete unused record in Admin Panel."]);
        exit;
    }
}

if ($target_record) {
    if ($target_record['status'] === 'approved' && !empty($target_record['api_key'])) {
        echo json_encode([
            "status" => "approved", 
            "api_key" => $target_record['api_key'],
            "reset_pin" => $target_record['reset_pin'],
            "message" => "Otorisasi Berhasil."
        ]);
    } else {
        echo json_encode(["status" => "pending", "message" => "Akses ditangguhkan. Silakan hubungi Admin untuk persetujuan."]);
    }
} else {
    echo json_encode(["status" => "not_found", "message" => "ID Perangkat tidak dikenali. Silakan ajukan permintaan akses."]);
}
?>