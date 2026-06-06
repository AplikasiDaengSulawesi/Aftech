<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/mobile_production_sync_helper.php';

$envelope = normalizeMobileProductionSyncEnvelope([
    'device_id' => 'BP2A.250605.031.A3',
    'device_model' => 'SM-A556E',
    'input_method' => 'manual',
    'items' => [
        [
            'batch' => '080326-01C-SED-1000-RAMA-100PCS',
            'item' => 'SEDOTAN',
            'size' => '100',
            'unit' => 'PCS',
            'machine' => 'THERMO TINGGI 01',
            'shift' => 'SHIFT C',
            'quantity' => '1000',
            'operator' => 'rahmat',
            'qc' => 'mamat',
            'production_date' => '08-03-2026',
            'production_time' => '02:53:49',
            'copies' => 10,
        ],
        [
            'batch' => '080326-1A-BOT-500-RAMA-330ML',
            'item' => 'BOTOL',
            'size' => '330',
            'unit' => 'ML',
            'machine' => 'MACHINE 02',
            'shift' => 'SHIFT A',
            'quantity' => '500',
            'operator' => 'sinta',
            'qc' => 'dedi',
            'production_date' => '09-03-2026',
            'production_time' => '10:15:00',
            'copies' => 5,
            'input_method' => 'scan',
            'device_model' => 'SM-S911B',
        ],
    ],
]);

assertSameValue('BP2A.250605.031.A3', $envelope['device_id'], 'Device ID top-level harus terbaca');
assertSameValue('SM-A556E', $envelope['device_model'], 'Device model top-level harus terbaca');
assertSameValue('manual', $envelope['input_method'], 'Input method top-level harus normal');
assertSameValue(2, count($envelope['items']), 'Jumlah item sync harus sesuai');
assertSameValue('BP2A.250605.031.A3', $envelope['items'][0]['device_id'], 'Item pertama harus mewarisi device_id');
assertSameValue('SM-A556E', $envelope['items'][0]['device_model'], 'Item pertama harus mewarisi device_model');
assertSameValue('manual', $envelope['items'][0]['input_method'], 'Item pertama harus mewarisi input_method');
assertSameValue('SM-S911B', $envelope['items'][1]['device_model'], 'Item kedua boleh override device_model');
assertSameValue('scan', $envelope['items'][1]['input_method'], 'Item kedua mempertahankan input method sendiri');

$single = normalizeMobileProductionSyncEnvelope([
    'batch' => '080326-01C-SED-1000-RAMA-100PCS',
    'item' => 'SEDOTAN',
    'size' => '100',
    'unit' => 'PCS',
    'machine' => 'THERMO TINGGI 01',
    'shift' => 'SHIFT C',
    'quantity' => '1000',
    'operator' => 'rahmat',
    'qc' => 'mamat',
    'production_date' => '08-03-2026',
    'production_time' => '02:53:49',
    'copies' => 10,
]);

assertSameValue(1, count($single['items']), 'Payload single item harus dinormalisasi menjadi array item');
assertSameValue('scan', $single['items'][0]['input_method'], 'Input method default untuk single item harus scan');

echo "MobileProductionSyncEnvelopeTest: PASS\n";
