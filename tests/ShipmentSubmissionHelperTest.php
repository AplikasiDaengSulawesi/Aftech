<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/shipment_submission_helper.php';

$cart = [
    10 => [1, 2, 3],
    11 => [4],
];

$persisted = persistShipmentLabelsWithGuard($cart, function (int $productionId, int $labelNo): bool {
    return true;
});

assertSameValue(4, $persisted, 'jumlah label yang disimpan harus sesuai total input');

assertThrowsValue(
    function () {
        persistShipmentLabelsWithGuard(
            [10 => [1, 2, 3]],
            function (int $productionId, int $labelNo): bool {
                return $labelNo !== 3;
            }
        );
    },
    'gagal insert label harus memicu exception'
);

echo "ShipmentSubmissionHelperTest: PASS\n";
