<?php
header('Content-Type: application/json');
include '../config.php';
verify_api_access();
require_once __DIR__ . '/../../includes/mobile_production_sync_helper.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !is_array($data)) {
    echo json_encode(["status" => "error", "message" => "Input tidak valid. Payload JSON tidak ditemukan."]);
    exit;
}

try {
    $envelope = normalizeMobileProductionSyncEnvelope($data);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}

$results = [];
$successCount = 0;
$failedCount = 0;

foreach ($envelope['items'] as $index => $record) {
    try {
        $result = persistMobileProductionRecord($conn, $record);
        $result['index'] = $index;
        $results[] = $result;
        $successCount++;
    } catch (Throwable $e) {
        $failedCount++;
        $results[] = [
            'index' => $index,
            'status' => 'error',
            'message' => $e->getMessage(),
            'batch' => $record['batch'] ?? null,
        ];
    }
}

echo json_encode([
    'status' => $failedCount > 0 ? 'partial_success' : 'success',
    'message' => $failedCount > 0
        ? 'Sebagian data berhasil disimpan.'
        : 'Semua data berhasil disimpan.',
    'summary' => [
        'total' => count($envelope['items']),
        'success' => $successCount,
        'failed' => $failedCount,
    ],
    'items' => $results,
]);
