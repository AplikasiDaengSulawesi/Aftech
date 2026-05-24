<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../api/config.php';
require __DIR__ . '/../includes/shipment_reverse_helper.php';

$helper = new ShipmentReverseHelper($conn);
$parsed = $helper->parseBatch('080326-01C-SED-1000-RAMA-100PCS');
$resolved = $helper->resolveBatchReferences($parsed);

assertSameValue('SEDOTAN', $resolved['item'], 'Item reverse tidak sesuai');
assertSameValue('100', $resolved['size'], 'Size reverse tidak sesuai');
assertSameValue('PCS', $resolved['unit'], 'Unit reverse tidak sesuai');
assertSameValue('SHIFT C', $resolved['shift'], 'Shift reverse tidak sesuai');
assertSameValue('1000', $resolved['quantity'], 'Qty reverse tidak sesuai');
assertSameValue('rahmat', strtolower($resolved['operator']), 'Operator reverse tidak sesuai');
assertSameValue('mamat', strtolower($resolved['qc']), 'QC reverse tidak sesuai');

echo "ReverseShipmentDbSmokeTest: PASS\n";
