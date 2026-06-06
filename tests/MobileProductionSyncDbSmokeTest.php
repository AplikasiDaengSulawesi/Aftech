<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../api/config.php';
require __DIR__ . '/../includes/mobile_production_sync_helper.php';

$batch = 'SMOKE-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));

$cleanup = function () use ($conn, $batch): void {
    $stmt = $conn->prepare("SELECT id FROM production_labels WHERE batch = ?");
    $stmt->bind_param('s', $batch);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $productionId = (int) $row['id'];

        $stmtDeleteWarehouse = $conn->prepare("DELETE FROM warehouse_items WHERE production_id = ?");
        $stmtDeleteWarehouse->bind_param('i', $productionId);
        $stmtDeleteWarehouse->execute();

        $stmtDeleteTransfers = $conn->prepare("DELETE FROM warehouse_transfers WHERE production_id = ?");
        $stmtDeleteTransfers->bind_param('i', $productionId);
        $stmtDeleteTransfers->execute();

        $stmtDeletePrintQueues = $conn->prepare("DELETE FROM print_queues WHERE production_id = ?");
        $stmtDeletePrintQueues->bind_param('i', $productionId);
        $stmtDeletePrintQueues->execute();

        $stmtDeleteProd = $conn->prepare("DELETE FROM production_labels WHERE id = ?");
        $stmtDeleteProd->bind_param('i', $productionId);
        $stmtDeleteProd->execute();
    }
};

$cleanup();

$payload = [
    'item' => 'SEDOTAN',
    'size' => '100',
    'unit' => 'PCS',
    'batch' => $batch,
    'machine' => 'THERMO TINGGI 01',
    'shift' => 'SHIFT C',
    'quantity' => '1000',
    'operator' => 'rahmat',
    'qc' => 'mamat',
    'production_date' => '08-03-2026',
    'production_time' => '02:53:49',
    'copies' => 3,
    'input_method' => 'manual',
    'device_model' => 'SM-A556E',
    'device_id' => 'BP2A.250605.031.A3',
];

$result = persistMobileProductionRecord($conn, normalizeMobileProductionRecord($payload));

assertSameValue('success', $result['status'], 'status sinkronisasi harus success');
assertSameValue($batch, $result['batch'], 'batch harus cocok');
assertSameValue(3, $result['copies'], 'copies harus cocok');
assertSameValue(1, $result['first_label_no'], 'label awal harus 1 pada batch baru');
assertSameValue(3, $result['last_label_no'], 'label akhir harus 3 pada batch baru');
assertSameValue([1, 2, 3], $result['label_nos'], 'range label harus utuh');
assertSameValue([
    '1-' . $batch,
    '2-' . $batch,
    '3-' . $batch,
], $result['qr_codes'], 'qr code harus mengikuti batch');

$stmt = $conn->prepare("SELECT id, production_date, production_time, copies, device_model, device_id FROM production_labels WHERE batch = ? LIMIT 1");
$stmt->bind_param('s', $batch);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();

assertTrueValue(is_array($prod), 'production_labels harus terisi');
assertSameValue('2026-03-08', $prod['production_date'], 'production_date harus tersimpan dalam format Y-m-d');
assertSameValue('02:53:49', $prod['production_time'], 'production_time harus tersimpan');
assertSameValue(3, (int) $prod['copies'], 'copies tersimpan harus 3');
assertSameValue('SM-A556E', $prod['device_model'], 'device_model harus tersimpan');
assertSameValue('BP2A.250605.031.A3', $prod['device_id'], 'device_id harus tersimpan');

$productionId = (int) $prod['id'];

$stmtCountQueue = $conn->prepare("SELECT COUNT(*) AS total FROM print_queues WHERE production_id = ?");
$stmtCountQueue->bind_param('i', $productionId);
$stmtCountQueue->execute();
$queueCount = (int) $stmtCountQueue->get_result()->fetch_assoc()['total'];
assertSameValue(3, $queueCount, 'print_queues harus berisi 3 label');

$stmtQc = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key='qc_checker_enabled'");
$qcEnabled = $stmtQc && ($rowQc = $stmtQc->fetch_assoc()) ? (int) $rowQc['setting_value'] : 0;

if ($qcEnabled === 0) {
    $stmtCountWh = $conn->prepare("SELECT COUNT(*) AS total FROM warehouse_items WHERE production_id = ?");
    $stmtCountWh->bind_param('i', $productionId);
    $stmtCountWh->execute();
    $warehouseCount = (int) $stmtCountWh->get_result()->fetch_assoc()['total'];
    assertSameValue(3, $warehouseCount, 'warehouse_items harus berisi 3 label saat QC nonaktif');
}

$cleanup();

echo "MobileProductionSyncDbSmokeTest: PASS\n";
