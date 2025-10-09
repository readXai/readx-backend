<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ArabicSyllableService;

echo "🔤 Tests de syllabification - Mots arabes non vocalisés\n\n";

// Initialiser le service
$syllableService = new ArabicSyllableService();

// Tests comparatifs : mots vocalisés vs non vocalisés
$testCases = [
    'Mots simples' => [
        'كتب' => 'كَتَبَ', // il écrivit
        'قلم' => 'قَلَمٌ', // stylo
        'بيت' => 'بَيْتٌ', // maison
        'ولد' => 'وَلَدٌ', // garçon
    ],
    'Mots avec voyelles longues' => [
        'كتاب' => 'كِتَابٌ', // livre
        'طالب' => 'طَالِبٌ', // élève
        'جميل' => 'جَمِيلٌ', // beau
    ],
    'Mots complexes' => [
        'مدرسة' => 'مَدْرَسَةٌ', // école
        'مستقبل' => 'مُسْتَقْبَلٌ', // avenir
        'مكتبة' => 'مَكْتَبَةٌ', // bibliothèque
    ]
];

foreach ($testCases as $category => $words) {
    echo "📚 Catégorie: $category\n";
    echo str_repeat('=', 60) . "\n";
    
    foreach ($words as $unvocalized => $vocalized) {
        echo "🔍 Test: $unvocalized (non vocalisé) vs $vocalized (vocalisé)\n";
        
        // Test du mot non vocalisé
        echo "   📝 Non vocalisé ($unvocalized):\n";
        try {
            $resultUnvocalized = $syllableService->generateSyllables($unvocalized);
            
            echo "      Syllabes: [";
            foreach ($resultUnvocalized as $i => $syllable) {
                echo $syllable['syllable_text'];
                if ($i < count($resultUnvocalized) - 1) echo ', ';
            }
            echo "]\n";
            
            echo "      Types: [";
            foreach ($resultUnvocalized as $i => $syllable) {
                echo $syllable['syllable_type'];
                if ($i < count($resultUnvocalized) - 1) echo ', ';
            }
            echo "]\n";
            
            // Afficher les suggestions si disponibles
            echo "      Suggestions: [";
            foreach ($resultUnvocalized as $i => $syllable) {
                if (isset($syllable['suggestion'])) {
                    echo $syllable['suggestion'];
                } else {
                    echo $syllable['syllable_text'];
                }
                if ($i < count($resultUnvocalized) - 1) echo ', ';
            }
            echo "]\n";
            
            // Vérifier si c'est inféré
            $isInferred = isset($resultUnvocalized[0]['is_inferred']) && $resultUnvocalized[0]['is_inferred'];
            echo "      Statut: " . ($isInferred ? "🤖 Inféré automatiquement" : "✅ Vocalisé") . "\n";
            
        } catch (Exception $e) {
            echo "      ❌ ERREUR: " . $e->getMessage() . "\n";
        }
        
        // Test du mot vocalisé pour comparaison
        echo "   ✅ Vocalisé ($vocalized):\n";
        try {
            $resultVocalized = $syllableService->generateSyllables($vocalized);
            
            echo "      Syllabes: [";
            foreach ($resultVocalized as $i => $syllable) {
                echo $syllable['syllable_text'];
                if ($i < count($resultVocalized) - 1) echo ', ';
            }
            echo "]\n";
            
            echo "      Types: [";
            foreach ($resultVocalized as $i => $syllable) {
                echo $syllable['syllable_type'];
                if ($i < count($resultVocalized) - 1) echo ', ';
            }
            echo "]\n";
            
            $isInferred = isset($resultVocalized[0]['is_inferred']) && $resultVocalized[0]['is_inferred'];
            echo "      Statut: " . ($isInferred ? "🤖 Inféré automatiquement" : "✅ Vocalisé") . "\n";
            
        } catch (Exception $e) {
            echo "      ❌ ERREUR: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "\n";
}

// Tests de cas spéciaux
echo "🎯 CAS SPÉCIAUX - MOTS NON VOCALISÉS\n";
echo str_repeat('=', 60) . "\n";

$specialCases = [
    'Mots avec Chadda' => [
        'معلم' => 'Enseignant (مُعَلِّمٌ)',
        'مدرس' => 'Professeur (مُدَرِّسٌ)',
        'محمد' => 'Mohammed (مُحَمَّدٌ)'
    ],
    'Mots avec article' => [
        'الكتاب' => 'Le livre (الْكِتَابُ)',
        'المدرسة' => 'L\'école (الْمَدْرَسَةُ)',
        'الطالب' => 'L\'élève (الطَّالِبُ)'
    ],
    'Mots très courts' => [
        'من' => 'De/Qui (مِنْ)',
        'في' => 'Dans (فِي)',
        'إلى' => 'Vers (إِلَى)'
    ]
];

foreach ($specialCases as $category => $words) {
    echo "📚 $category:\n";
    
    foreach ($words as $word => $meaning) {
        echo "   🔍 $word ($meaning):\n";
        
        try {
            $result = $syllableService->generateSyllables($word);
            
            echo "      Syllabes: [";
            foreach ($result as $i => $syllable) {
                echo $syllable['syllable_text'];
                if ($i < count($result) - 1) echo ', ';
            }
            echo "]\n";
            
            if (isset($result[0]['suggestion'])) {
                echo "      Suggestions: [";
                foreach ($result as $i => $syllable) {
                    echo $syllable['suggestion'] ?? $syllable['syllable_text'];
                    if ($i < count($result) - 1) echo ', ';
                }
                echo "]\n";
            }
            
        } catch (Exception $e) {
            echo "      ❌ ERREUR: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Recommandations pédagogiques
echo "🎓 RECOMMANDATIONS PÉDAGOGIQUES\n";
echo str_repeat('=', 60) . "\n";
echo "1. 🤖 Les mots non vocalisés sont automatiquement détectés\n";
echo "2. 📝 Des suggestions de vocalisation sont proposées (Fatha par défaut)\n";
echo "3. ⚠️  L'enseignant peut corriger les suggestions selon le contexte\n";
echo "4. 🎯 Les élèves sont encouragés à ajouter les voyelles courtes\n";
echo "5. 📊 Le système distingue les syllabes inférées des syllabes vocalisées\n\n";

echo "✅ Tests des mots non vocalisés terminés!\n";
