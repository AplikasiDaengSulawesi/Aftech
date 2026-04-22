<?php
$envPath = __DIR__ . '/../.env';

if (!is_file($envPath)) {
    die('File .env tidak ditemukan di root project.');
}

foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = array_map('trim', explode('=', $line, 2));

    if (strlen($value) >= 2
        && ($value[0] === '"' || $value[0] === "'")
        && $value[0] === substr($value, -1)) {
        $value = substr($value, 1, -1);
    }

    if (!array_key_exists($key, $_ENV)) {
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
        putenv("$key=$value");
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? '';
$pass = $_ENV['DB_PASS'] ?? '';
$db   = $_ENV['DB_NAME'] ?? '';
