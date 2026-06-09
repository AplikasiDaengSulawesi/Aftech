<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/shipment_reverse_helper.php';

$reflection = new ReflectionClass(ShipmentReverseHelper::class);
/** @var ShipmentReverseHelper $helper */
$helper = $reflection->newInstanceWithoutConstructor();

assertTrueValue(
    $helper->isReverseEligibleBarcode('9-010126-01C-SED-1000-RAMA-100PCS'),
    'barcode lama sebelum cutoff harus eligible untuk reverse'
);

assertTrueValue(
    ! $helper->isReverseEligibleBarcode('9-310526-01C-SED-1000-RAMA-100PCS'),
    'barcode produksi setelah cutoff tidak boleh reverse'
);

assertTrueValue(
    ! $helper->isReverseEligibleBarcode('QR-SALAH'),
    'format barcode salah tidak boleh reverse'
);

echo "ShipmentReverseEligibilityTest: PASS\n";
