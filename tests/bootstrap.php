<?php

function assertSameValue($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message !== '' ? $message : 'Assertion gagal: nilai tidak sama');
    }
}

function assertTrueValue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new RuntimeException($message !== '' ? $message : 'Assertion gagal: kondisi false');
    }
}

function assertThrowsValue(callable $fn, string $message = ''): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        return;
    }

    throw new RuntimeException($message !== '' ? $message : 'Assertion gagal: exception diharapkan');
}
