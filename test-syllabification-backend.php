<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ArabicSyllableService;

echo "🧪 Tests de syllabification arabe - Backend Laravel\n\n";

// Initialiser le service
$syllableService = new ArabicSyllableService();

// Tests de base
$testWords = [
    'كَتَبَ' => ['كَ', 'تَ', 'بَ'],
    'كَتْبٌ' => ['كَتْ', 'بٌ'],
    'كِتَابٌ' => ['كِ', 'تَا', 'بٌ'],
    'بِسْمِ' => ['بِسْ', 'مِ'],
    'طَالِبٌ' => ['طَا', 'لِ', 'بٌ'],
    'مَدْرَسَةٌ' => ['مَدْ', 'رَ', 'سَ', 'ةٌ'],
    'مُعَلِّمٌ' => ['مُ', 'عَ', 'لِّ', 'مٌ'], // Chadda
    'جَمِيلَةٌ' => ['جَ', 'مِي', 'لَ', 'ةٌ'],
    'مُسْتَقْبَلٌ' => ['مُسْ', 'تَقْ', 'بَ', 'لٌ']
];

$passed = 0;
$total = count($testWords);

foreach ($testWords as $word => $expected) {
    echo "Test: $word\n";
    
    try {
        $result = $syllableService->generateSyllables($word);
        
        // Extraire seulement le texte des syllabes
        $actualSyllables = array_map(function($syllable) {
            return $syllable['syllable_text'];
        }, $result);
        
        echo "  Attendu: [" . implode(', ', $expected) . "]\n";
        echo "  Obtenu:  [" . implode(', ', $actualSyllables) . "]\n";
        
        if ($actualSyllables === $expected) {
            echo "  ✅ RÉUSSI\n";
            $passed++;
        } else {
            echo "  ❌ ÉCHOUÉ\n";
        }
        
        // Afficher les types de syllabes
        echo "  Types: [";
        foreach ($result as $i => $syllable) {
            echo $syllable['syllable_type'];
            if ($i < count($result) - 1) echo ', ';
        }
        echo "]\n";
        
    } catch (Exception $e) {
        echo "  ❌ ERREUR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "📊 Résultats: $passed réussis, " . ($total - $passed) . " échoués sur $total tests\n\n";

// Tests spéciaux pour Chadda
echo "🔍 Tests spéciaux Chadda:\n\n";

$chaddaTests = [
    'مُعَلِّمٌ' => 'Chadda sur ل',
    'مُدَرِّسٌ' => 'Chadda sur ر', 
    'كُتُبٌّ' => 'Chadda sur ب',
    'حَقٌّ' => 'Chadda finale'
];

foreach ($chaddaTests as $word => $description) {
    echo "Test Chadda: $word ($description)\n";
    
    try {
        $result = $syllableService->generateSyllables($word);
        
        echo "  Syllabes: [";
        foreach ($result as $i => $syllable) {
            echo $syllable['syllable_text'];
            if ($i < count($result) - 1) echo ', ';
        }
        echo "]\n";
        
        echo "  Types: [";
        foreach ($result as $i => $syllable) {
            echo $syllable['syllable_type'];
            if ($i < count($result) - 1) echo ', ';
        }
        echo "]\n";
        
    } catch (Exception $e) {
        echo "  ❌ ERREUR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Tests Lam solaire/lunaire
echo "🌙 Tests Lam solaire/lunaire:\n\n";

$lamTests = [
    'الْكِتَابُ' => 'Lam lunaire (ك)',
    'الشَّمْسُ' => 'Lam solaire (ش)',
    'الْقَمَرُ' => 'Lam lunaire (ق)',
    'الطَّالِبُ' => 'Lam solaire (ط)'
];

foreach ($lamTests as $word => $description) {
    echo "Test Lam: $word ($description)\n";
    
    try {
        $result = $syllableService->generateSyllables($word);
        
        echo "  Syllabes: [";
        foreach ($result as $i => $syllable) {
            echo $syllable['syllable_text'];
            if ($i < count($result) - 1) echo ', ';
        }
        echo "]\n";
        
    } catch (Exception $e) {
        echo "  ❌ ERREUR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "✅ Tests backend terminés!\n";
