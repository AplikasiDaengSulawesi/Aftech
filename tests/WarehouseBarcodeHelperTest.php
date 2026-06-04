<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/warehouse_barcode_helper.php';

$entries = buildWarehouseBarcodeEntries(4, [1, 2], [2, 4]);

assertSameValue(4, count($entries), 'harus membentuk semua label dalam batch');
assertSameValue(1, $entries[0]['label_no'], 'label pertama salah');
assertSameValue('Belum Terkirim', $entries[0]['status'], 'label 1 harus belum terkirim');
assertSameValue('Terkirim', $entries[1]['status'], 'label 2 harus terkirim');
assertSameValue('Belum Terkirim', $entries[2]['status'], 'label 3 harus belum terkirim');
assertSameValue('Terkirim', $entries[3]['status'], 'label 4 harus terkirim');

echo "WarehouseBarcodeHelperTest: PASS\n";
