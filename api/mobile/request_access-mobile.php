<?php
header('Content-Type: application/json');
include '../config.php';

// Endpoint ini terbuka (tanpa verify_api_access) karena device belum punya API Key.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

// Ambil input JSON
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$device_name = $conn->real_escape_string($input['device_name'] ?? '');
$device_uuid = $conn->real_escape_string($input['device_uuid'] ?? '');

if (empty($device_name) || empty($device_uuid)) {
    echo json_encode(["status" => "error", "message" => "Device Name & UUID required"]);
    exit;
}

// Cek apakah sudah pernah request
$check = $conn->query("SELECT id, status FROM api_keys WHERE device_uuid = '$device_uuid'");
if ($check->num_rows > 0) {
    $row = $check->fetch_assoc();
    if ($row['status'] === 'approved') {
        echo json_encode(["status" => "success", "message" => "Sudah disetujui sebelumnya."]);
    } else {
        echo json_encode(["status" => "pending", "message" => "Permintaan sedang menunggu persetujuan Admin."]);
    }
    exit;
}

// Insert request baru
$sql = "INSERT INTO api_keys (device_name, device_uuid, status, is_active) VALUES ('$device_name', '$device_uuid', 'pending', 0)";
if ($conn->query($sql)) {
    echo json_encode(["status" => "success", "message" => "Permintaan akses terkirim! Silakan hubungi Admin untuk persetujuan."]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal mengirim permintaan: " . $conn->error]);
}
?>