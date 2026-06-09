<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/shipment_reverse_helper.php';

$reflection = new ReflectionClass(ShipmentReverseHelper::class);
/** @var ShipmentReverseHelper $helper */
$helper = $reflection->newInstanceWithoutConstructor();

assertTrueValue(
    $helper->canBypassReverseCutoff('batch_not_found'),
    'alasan batch_not_found harus bisa bypass cutoff'
);

assertTrueValue(
    ! $helper->canBypassReverseCutoff('out_of_quota'),
    'alasan out_of_quota tidak boleh bypass cutoff'
);

assertTrueValue(
    ! $helper->canBypassReverseCutoff(null),
    'reason kosong tidak boleh bypass cutoff'
);

echo "ShipmentReverseCutoffBypassTest: PASS\n";
