<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/shipment_submission_helper.php';

assertSameValue(20, resolveExpectedShipmentCount(20, 0, 0), 'non-append harus pakai jumlah label request');
assertSameValue(40, resolveExpectedShipmentCount(20, 20, 99), 'append harus menambahkan count existing');
assertSameValue(40, resolveExpectedShipmentCount(20, 20, 1), 'append tetap harus menghitung existing + request');

echo "ShipmentSubmissionCountHelperTest: PASS\n";
