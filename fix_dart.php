<?php
$file = 'aftech_mobile/lib/services/database_service.dart';
$content = file_get_contents($file);

$lines = explode("\n", $content);
foreach ($lines as &$line) {
    if (strpos($line, 'http.get(Uri.parse') !== false && strpos($line, 'headers: _headers') === false) {
        // find the matching closing parenthesis of http.get(
        // Actually, it's easier to just do a string replace since we know the exact pattern
        $line = preg_replace('/(http\.get\(Uri\.parse\([^)]+\)[^)]*\))/', '$1', $line); // too complex for regex
    }
}
