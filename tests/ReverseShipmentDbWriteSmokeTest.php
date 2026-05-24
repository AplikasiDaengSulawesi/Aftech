<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../api/config.php';
require __DIR__ . '/../includes/shipment_reverse_helper.php';

$batch = '010126-01C-SED-1000-RAMA-100PCS';
$detailsPrefix = 'Reverse scan ';

$cleanup = function () use ($conn, $batch, $detailsPrefix): void {
    $stmt = $conn->prepare("SELECT id FROM production_labels WHERE batch = ?");
    $stmt->bind_param('s', $batch);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $productionId = (int)$row['id'];

        $stmtDeleteWarehouse = $conn->prepare("DELETE FROM warehouse_items WHERE production_id = ?");
        $stmtDeleteWarehouse->bind_param('i', $productionId);
        $stmtDeleteWarehouse->execute();

        $stmtDeleteTransfers = $conn->prepare("DELETE FROM warehouse_transfers WHERE production_id = ?");
        $stmtDeleteTransfers->bind_param('i', $productionId);
        $stmtDeleteTransfers->execute();

        $stmtDeleteProd = $conn->prepare("DELETE FROM production_labels WHERE id = ?");
        $stmtDeleteProd->bind_param('i', $productionId);
        $stmtDeleteProd->execute();
    }

    $like = $detailsPrefix . '%';
    $stmtDeleteLogs = $conn->prepare("DELETE FROM activity_logs WHERE action = 'REVERSE_SCAN' AND details LIKE ?");
    $stmtDeleteLogs->bind_param('s', $like);
    $stmtDeleteLogs->execute();
};

$cleanup();

$helper = new ShipmentReverseHelper($conn);

$result1 = $helper->reverseScan('3-' . $batch, 'Smoke Test');
assertSameValue('created_batch', $result1['reverse_status'], 'reverse pertama harus membuat batch');
assertSameValue(3, $result1['copies'], 'copies batch baru harus mengikuti label tertinggi');
assertTrueValue(in_array(3, $result1['in_warehouse'], true), 'label 3 harus masuk gudang');

$result2 = $helper->reverseScan('3-' . $batch, 'Smoke Test');
assertSameValue('existing_label', $result2['reverse_status'], 'scan ulang label yang sama harus existing');
assertSameValue(3, $result2['copies'], 'copies tidak boleh bertambah saat existing');

$result3 = $helper->reverseScan('5-' . $batch, 'Smoke Test');
assertSameValue('created_label', $result3['reverse_status'], 'label baru pada batch existing harus ditambah');
assertSameValue(5, $result3['copies'], 'copies harus naik ke label tertinggi baru');
assertTrueValue(in_array(5, $result3['in_warehouse'], true), 'label 5 harus masuk gudang');

$cleanup();

echo "ReverseShipmentDbWriteSmokeTest: PASS\n";
