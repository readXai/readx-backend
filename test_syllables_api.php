<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ArabicSyllableService;

$service = new ArabicSyllableService();

echo "=== Test de syllabification arabe via API ===\n\n";

// Test du cas problématique qui causait l'erreur CCV
echo "Test problématique - Mot avec chadda: مدرّس\n";
$result = $service->generateSyllables('مدرّس');
foreach ($result as $syllable) {
    echo "Syllabe: {$syllable['syllable_text']} → Type: {$syllable['syllable_type']}\n";
}
echo "\n";

// Test autres cas
echo "Test - Mot simple: كتب\n";
$result2 = $service->generateSyllables('كتب');
foreach ($result2 as $syllable) {
    echo "Syllabe: {$syllable['syllable_text']} → Type: {$syllable['syllable_type']}\n";
}
echo "\n";

// Test article défini
echo "Test - Article défini: الكتاب\n";
$result3 = $service->generateSyllables('الكتاب');
foreach ($result3 as $syllable) {
    echo "Syllabe: {$syllable['syllable_text']} → Type: {$syllable['syllable_type']}\n";
}

