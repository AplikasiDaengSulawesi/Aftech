<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/shipment_submission_helper.php';

$log = formatShipmentSubmissionFailureLog(
    [
        'shipment_id' => 77,
        'production_id' => 12,
        'label_no' => 45,
        'customer_name' => 'PT Contoh',
        'total_qty' => 200,
        'details' => ['stage' => 'persist', 'reason' => 'duplicate label'],
    ],
    'Gagal menyimpan label'
);

assertTrueValue(str_contains($log, 'shipment_submission_failed'), 'prefix log harus ada');
assertTrueValue(str_contains($log, 'shipment_id=77'), 'shipment_id harus ada di log');
assertTrueValue(str_contains($log, 'production_id=12'), 'production_id harus ada di log');
assertTrueValue(str_contains($log, 'label_no=45'), 'label_no harus ada di log');
assertTrueValue(str_contains($log, 'total_qty=200'), 'total_qty harus ada di log');
assertTrueValue(str_contains($log, 'duplicate label'), 'detail error harus ikut masuk');

echo "ShipmentSubmissionFailureLogTest: PASS\n";
