<?php
include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $item     = $conn->real_escape_string($data['item']);
    $size     = $conn->real_escape_string($data['size']);
    $unit     = $conn->real_escape_string($data['unit']);
    $batch    = $conn->real_escape_string($data['batch']);
    $machine  = $conn->real_escape_string($data['machine']);
    $shift    = $conn->real_escape_string($data['shift']);
    $quantity = $conn->real_escape_string($data['quantity']);
    $operator = $conn->real_escape_string($data['operator']);
    $qc       = $conn->real_escape_string($data['qc']);
    $device   = $conn->real_escape_string($data['device_model'] ?? 'Unknown');
    
    // --- FIX: KONVERSI TANGGAL dd-MM-yyyy ke yyyy-MM-dd ---
    $rawDate = $data['production_date']; // Contoh: 06-03-2026
    $dateObj = DateTime::createFromFormat('d-m-Y', $rawDate);
    $formattedDate = $dateObj ? $dateObj->format('Y-m-d') : date('Y-m-d');
    
    $time     = $conn->real_escape_string($data['production_time']);
    $copies   = (int)$data['copies'];

    $sql = "INSERT INTO production_labels (item, size, unit, batch, machine, shift, quantity, operator, qc, production_date, production_time, copies, device_model) 
            VALUES ('$item', '$size', '$unit', '$batch', '$machine', '$shift', '$quantity', '$operator', '$qc', '$formattedDate', '$time', $copies, '$device')
            ON DUPLICATE KEY UPDATE 
            copies = copies + VALUES(copies), 
            production_time = '$time',
            device_model = '$device'";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Berhasil Disimpan"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
?>
