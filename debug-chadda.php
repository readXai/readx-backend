<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ArabicSyllableService;

echo "🔍 Debug Chadda - Backend Laravel\n\n";

// Créer une instance du service pour accéder aux méthodes privées via réflexion
$syllableService = new ArabicSyllableService();
$reflection = new ReflectionClass($syllableService);

// Accéder à la méthode privée expandChaddaLetters
$expandMethod = $reflection->getMethod('expandChaddaLetters');
$expandMethod->setAccessible(true);

// Accéder à la méthode privée preprocessArabicText
$preprocessMethod = $reflection->getMethod('preprocessArabicText');
$preprocessMethod->setAccessible(true);

// Test avec مُعَلِّمٌ
$testWord = 'مُعَلِّمٌ';
echo "Mot original: $testWord\n";

// Voir les caractères originaux
$originalChars = $preprocessMethod->invoke($syllableService, $testWord);
echo "Caractères originaux: [" . implode(', ', $originalChars) . "]\n";

// Voir l'expansion
$expandedWord = $expandMethod->invoke($syllableService, $testWord);
echo "Mot étendu: $expandedWord\n";

// Voir les caractères étendus
$expandedChars = $preprocessMethod->invoke($syllableService, $expandedWord);
echo "Caractères étendus: [" . implode(', ', $expandedChars) . "]\n\n";

// Test du découpage complet
echo "Test complet:\n";
$result = $syllableService->generateSyllables($testWord);

foreach ($result as $i => $syllable) {
    echo "Syllabe $i: '" . $syllable['syllable_text'] . "' (type: " . $syllable['syllable_type'] . ")\n";
}

echo "\n";

// Test avec d'autres mots avec Chadda
$chaddaWords = ['مُدَرِّسٌ', 'حَقٌّ', 'كُتُبٌّ'];

foreach ($chaddaWords as $word) {
    echo "=== Test: $word ===\n";
    
    $originalChars = $preprocessMethod->invoke($syllableService, $word);
    echo "Original: [" . implode(', ', $originalChars) . "]\n";
    
    $expandedWord = $expandMethod->invoke($syllableService, $word);
    $expandedChars = $preprocessMethod->invoke($syllableService, $expandedWord);
    echo "Étendu: [" . implode(', ', $expandedChars) . "]\n";
    
    $result = $syllableService->generateSyllables($word);
    echo "Syllabes: [";
    foreach ($result as $i => $syllable) {
        echo $syllable['syllable_text'];
        if ($i < count($result) - 1) echo ', ';
    }
    echo "]\n\n";
}

echo "✅ Debug terminé!\n";
