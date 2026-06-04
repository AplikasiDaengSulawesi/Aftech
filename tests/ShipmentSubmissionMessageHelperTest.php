<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/shipment_submission_helper.php';

$normal = formatShipmentCountMismatchMessage(0, 20, 20, 18);
assertTrueValue(str_contains($normal, 'Jumlah label tersimpan tidak cocok'), 'pesan normal harus generik');
assertTrueValue(str_contains($normal, 'Diminta 20, tersimpan 18'), 'pesan normal harus memuat angka');

$append = formatShipmentCountMismatchMessage(77, 20, 40, 38);
assertTrueValue(str_contains($append, 'shipment susulan'), 'pesan append harus spesifik susulan');
assertTrueValue(str_contains($append, 'total akhir 40'), 'pesan append harus memuat expected total');
assertTrueValue(str_contains($append, 'Label submit saat ini: 20'), 'pesan append harus memuat jumlah submit saat ini');

echo "ShipmentSubmissionMessageHelperTest: PASS\n";
