<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/shipment_reverse_helper.php';

$reflection = new ReflectionClass(ShipmentReverseHelper::class);
/** @var ShipmentReverseHelper $helper */
$helper = $reflection->newInstanceWithoutConstructor();

$parsedBarcode = $helper->parseBarcode('12-080326-01C-SED-1000-RAMA-100ML');
assertSameValue(12, $parsedBarcode['label_no'], 'label_no barcode harus 12');
assertSameValue('080326-01C-SED-1000-RAMA-100ML', $parsedBarcode['batch'], 'batch barcode tidak sesuai');

$parsedBatch = $helper->parseBatch('080326-01C-SED-1000-RAMA-100ML');
assertSameValue('080326', $parsedBatch['date_code']);
assertSameValue('01', $parsedBatch['machine_code']);
assertSameValue('C', $parsedBatch['shift_code']);
assertSameValue('SED', $parsedBatch['item_code']);
assertSameValue('1000', $parsedBatch['quantity']);
assertSameValue('RAMA', $parsedBatch['operator_qc_code']);
assertSameValue('100', $parsedBatch['size_value']);
assertSameValue('ML', $parsedBatch['unit_code']);

assertSameValue('2026-03-08', $helper->parseProductionDate('080326'));
assertThrowsValue(fn() => $helper->assertReverseAllowedDate('2026-05-25'), 'tanggal cutoff harus ditolak');
assertThrowsValue(fn() => $helper->parseBatch('BATCH-SALAH'), 'format batch salah harus ditolak');
assertTrueValue(true, 'smoke reverse parser harus lulus');

echo "ReverseShipmentScanTest: PASS\n";
