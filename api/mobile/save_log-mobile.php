<?php
include '../config.php';
verify_api_access();

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $action = $conn->real_escape_string($data['action']);
    $details = $conn->real_escape_string($data['details']);
    
    // Pastikan log mencatat aktivitas dari mobile dengan benar
    $sql = "INSERT INTO activity_logs (action, details) VALUES ('$action', '$details')";
    
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No data received"]);
}
?>