<?php
include 'config.php';
$data = json_decode(file_get_contents("php://input"), true);
if ($data) {
    $action = $conn->real_escape_string($data['action']);
    $details = $conn->real_escape_string($data['details']);
    $conn->query("INSERT INTO activity_logs (action, details) VALUES ('$action', '$details')");
}
?>
