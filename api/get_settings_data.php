<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$data = [];

if ($type == 'item') {
    // Ambil item lengkap dengan nama unitnya
    $res = $conn->query("SELECT i.*, u.name as unit_name FROM master_items i LEFT JOIN master_units u ON i.unit_id = u.id ORDER BY i.name ASC");
} elseif ($type == 'size') {
    // Ambil size lengkap dengan nama itemnya
    $res = $conn->query("SELECT s.*, i.name as item_name FROM master_sizes s JOIN master_items i ON s.item_id = i.id ORDER BY i.name ASC");
} elseif ($type == 'machine') {
    $res = $conn->query("SELECT * FROM master_machines ORDER BY name ASC");
} elseif ($type == 'quantity') {
    // Ambil qty lengkap dengan nama mesinnya
    $res = $conn->query("SELECT q.*, m.name as machine_name FROM master_quantities q JOIN master_machines m ON q.machine_id = m.id ORDER BY m.name ASC");
} elseif ($type == 'user') {
    $res = $conn->query("SELECT id, username, full_name, role FROM users ORDER BY username ASC");
} elseif ($type == 'customer') {
    $res = $conn->query("SELECT * FROM master_customers ORDER BY name ASC");
} elseif ($type == 'unit') {
    $res = $conn->query("SELECT * FROM master_units ORDER BY name ASC");
} elseif ($type == 'shift') {
    $res = $conn->query("SELECT * FROM master_shifts ORDER BY name ASC");
} elseif ($type == 'template') {
    $res = $conn->query("SELECT * FROM master_templates ORDER BY template_name ASC");
} elseif ($type == 'api_key') {
    $res = $conn->query("SELECT * FROM api_keys ORDER BY created_at DESC");
} elseif ($type == 'role_permissions') {
    $res = $conn->query("SELECT * FROM role_permissions");
}

if (isset($res) && $res) {
    while($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
?>
