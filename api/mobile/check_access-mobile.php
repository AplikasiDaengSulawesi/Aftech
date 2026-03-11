<?php
header('Content-Type: application/json');
include '../config.php';

// Mendukung POST (JSON) dan GET (Query Param)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$device_uuid = $conn->real_escape_string($input['device_uuid'] ?? $_GET['device_uuid'] ?? '');

if (empty($device_uuid)) {
    echo json_encode(["status" => "error", "message" => "UUID required"]);
    exit;
}

$check = $conn->query("SELECT status, api_key, reset_pin FROM api_keys WHERE device_uuid = '$device_uuid'");
if ($check->num_rows > 0) {
    $row = $check->fetch_assoc();
    if ($row['status'] === 'approved' && !empty($row['api_key'])) {
        echo json_encode([
            "status" => "approved", 
            "api_key" => $row['api_key'],
            "reset_pin" => $row['reset_pin'],
            "message" => "Akses disetujui."
        ]);
    } else {
        echo json_encode(["status" => "pending", "message" => "Masih menunggu persetujuan Admin."]);
    }
} else {
    echo json_encode(["status" => "not_found", "message" => "Belum pernah mengajukan permintaan."]);
}
?>