<?php
/**
 * Endpoint API Mobile untuk mengambil daftar antrian cetak label yang belum dicetak.
 * URL: /api/mobile/get_print_queue-mobile.php
 * Method: GET
 * Autentikasi: Diperlukan (API Key atau Session Web)
 */

header('Content-Type: application/json');

// Sertakan file konfigurasi dan fungsi verifikasi akses
include '../config.php';
verify_api_access();

try {
    // Query untuk mengambil semua antrean cetak yang masih berstatus 'pending'
    // Asumsi tabel `print_queues` memiliki kolom `status` dengan nilai default 'pending'.
    // Jika tidak ada kolom `status`, hapus `WHERE status = 'pending'`.
    $sql = "SELECT id, production_id, batch, label_no, qr_code, created_at FROM print_queues WHERE status = 'pending' ORDER BY created_at ASC";
    $result = $conn->query($sql);

    $grouped_data = [];
    while ($row = $result->fetch_assoc()) {
        $batch = $row['batch'];
        
        // Jika batch belum ada di array, buat format dasarnya
        if (!isset($grouped_data[$batch])) {
            $grouped_data[$batch] = [
                'production_id' => $row['production_id'],
                'batch'         => $batch,
                'created_at'    => $row['created_at'],
                'labels'        => []
            ];
        }
        
        // Masukkan data spesifik label ke dalam array 'labels' pada batch tersebut
        $grouped_data[$batch]['labels'][] = [
            'id'       => $row['id'],
            'label_no' => $row['label_no'],
            'qr_code'  => $row['qr_code']
        ];
    }

    // Ubah associative array menjadi indexed array (list array biasa)
    $data = array_values($grouped_data);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Gagal mengambil antrian cetak: " . $e->getMessage()]);
}

?>