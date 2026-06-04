<?php

if (!function_exists('buildDemoConflictBatchCode')) {
    function buildDemoConflictBatchCode(string $prefix = 'DEM'): string
    {
        $date = date('dmy');
        $token = strtoupper(bin2hex(random_bytes(2)));

        return sprintf('%s-9A-%s-200-CRASH-600ML-%s', $date, $prefix, $token);
    }
}
