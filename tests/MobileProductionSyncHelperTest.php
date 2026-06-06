<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/mobile_production_sync_helper.php';

assertSameValue('2026-03-08', normalizeMobileProductionDate('08-03-2026'), 'Tanggal mobile harus dinormalisasi ke Y-m-d');
assertSameValue(null, normalizeMobileProductionDate('2026-03-08'), 'Format tanggal yang tidak didukung harus ditolak');
assertSameValue('scan', normalizeMobileInputMethod(null), 'Input method kosong harus fallback ke scan');
assertSameValue('manual', normalizeMobileInputMethod(' MANUAL '), 'Input method manual harus dinormalisasi');

$range = buildMobileLabelRange(10, 5, '080326-01C-SED-1000-RAMA-100PCS');

assertSameValue(11, $range['first_label_no'], 'Label pertama harus melanjutkan copies lama');
assertSameValue(15, $range['last_label_no'], 'Label terakhir harus mengikuti copies baru');
assertSameValue([11, 12, 13, 14, 15], $range['label_nos'], 'Daftar label baru harus berurutan');
assertSameValue([
    '11-080326-01C-SED-1000-RAMA-100PCS',
    '12-080326-01C-SED-1000-RAMA-100PCS',
    '13-080326-01C-SED-1000-RAMA-100PCS',
    '14-080326-01C-SED-1000-RAMA-100PCS',
    '15-080326-01C-SED-1000-RAMA-100PCS',
], $range['qr_codes'], 'QR code baru harus mengikuti format label-batch');

echo "MobileProductionSyncHelperTest: PASS\n";
