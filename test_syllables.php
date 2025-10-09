<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ArabicSyllableService;

$service = new ArabicSyllableService();

echo "=== Test de syllabification arabe ===\n\n";

// Test 1 - Mot simple
echo "Test 1 - Mot simple: كتب\n";
$result1 = $service->splitIntoSyllables('كتب');
print_r($result1);
echo "\n";

// Test 2 - Mot avec article défini
echo "Test 2 - Mot avec article défini: الكتاب\n";
$result2 = $service->splitIntoSyllables('الكتاب');
print_r($result2);
echo "\n";

// Test 3 - Mot avec chadda
echo "Test 3 - Mot avec chadda: مدرّس\n";
$result3 = $service->splitIntoSyllables('مدرّس');
print_r($result3);
echo "\n";

// Test 4 - Format API
echo "Test 4 - Format API: كتب\n";
$result4 = $service->generateSyllables('كتب');
print_r($result4);
echo "\n";

// Test 5 - Mot avec voyelles courtes
echo "Test 5 - Mot avec voyelles courtes: كَتَبَ\n";
$result5 = $service->splitIntoSyllables('كَتَبَ');
print_r($result5);
echo "\n";

