<?php
include '../config.php';
verify_api_access();

// Fetch Items with their associated Units using JOIN
$items = $conn->query("
    SELECT i.name, u.name as unit 
    FROM master_items i 
    LEFT JOIN master_units u ON i.unit_id = u.id 
    ORDER BY i.name
")->fetch_all(MYSQLI_ASSOC);

$shifts = $conn->query("SELECT name FROM master_shifts ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$units = $conn->query("SELECT name FROM master_units ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$machines = $conn->query("SELECT name, status FROM master_machines ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$sizes = $conn->query("
    SELECT s.size_value, i.name as parent_item 
    FROM master_sizes s 
    JOIN master_items i ON s.item_id = i.id
")->fetch_all(MYSQLI_ASSOC);

$quantities = $conn->query("
    SELECT q.qty_value, m.name as parent_machine 
    FROM master_quantities q 
    JOIN master_machines m ON q.machine_id = m.id
")->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'items' => $items, // Now returns objects {name, unit}
    'shifts' => array_column($shifts, 'name'),
    'units' => array_column($units, 'name'),
    'machines' => $machines,
    'sizes' => $sizes,
    'quantities' => $quantities
]);
?>
