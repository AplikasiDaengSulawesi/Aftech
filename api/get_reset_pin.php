<?php
include 'config.php';
header('Content-Type: application/json');

// Ambil PIN terbaru dari tabel app_config
$res = $conn->query("SELECT reset_pin FROM app_config LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo json_encode(['pin' => $row['reset_pin']]);
} else {
    echo json_encode(['pin' => '1234']); // Default fallback
}
?>
