<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/seed_submit_bulk_conflict_helper.php';

$batch = buildDemoConflictBatchCode();

assertTrueValue((bool) preg_match('/^\d{6}-9A-DEM-200-CRASH-600ML-[A-F0-9]{4}$/', $batch), 'format batch dummy harus sesuai');

echo "SeedSubmitBulkConflictHelperTest: PASS\n";
