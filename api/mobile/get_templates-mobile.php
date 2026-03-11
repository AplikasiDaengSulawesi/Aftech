<?php
// backend_files/api/get_templates.php
header("Content-Type: application/json");
include '../config.php';
verify_api_access();

try {
    $result = $conn->query("SELECT * FROM master_templates ORDER BY template_name ASC");
    $templates = [];
    while($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    echo json_encode($templates);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
$conn->close();
?>
