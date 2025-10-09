<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ArabicSyllableService;

echo "📚 Validation pédagogique - Textes scolaires arabes\n\n";

// Initialiser le service
$syllableService = new ArabicSyllableService();

// Textes scolaires authentiques par niveau
$schoolTexts = [
    'CP (6-7 ans)' => [
        'بَيْتٌ' => 'Maison',
        'كِتَابٌ' => 'Livre', 
        'قَلَمٌ' => 'Stylo',
        'وَلَدٌ' => 'Garçon',
        'بِنْتٌ' => 'Fille'
    ],
    'CE1 (7-8 ans)' => [
        'مَدْرَسَةٌ' => 'École',
        'مُعَلِّمٌ' => 'Enseignant',
        'طَالِبٌ' => 'Élève',
        'دَرْسٌ' => 'Leçon',
        'كُرَّاسَةٌ' => 'Cahier'
    ],
    'CE2 (8-9 ans)' => [
        'الْمَكْتَبَةُ' => 'La bibliothèque',
        'الْحَدِيقَةُ' => 'Le jardin',
        'الْمُسْتَشْفَى' => 'L\'hôpital',
        'الْجَامِعَةُ' => 'L\'université',
        'الْمُتَحَدِّثُ' => 'L\'orateur'
    ],
    'CM1-CM2 (9-11 ans)' => [
        'الْاِسْتِقْلَالُ' => 'L\'indépendance',
        'الْمُسْتَقْبَلُ' => 'L\'avenir',
        'الْمُشَارَكَةُ' => 'La participation',
        'الْاِنْتِصَارُ' => 'La victoire',
        'الْمُحَافَظَةُ' => 'La conservation'
    ]
];

$totalWords = 0;
$totalSyllables = 0;
$complexityStats = [
    'CV' => 0,    // Consonne-Voyelle
    'CVC' => 0,   // Consonne-Voyelle-Consonne
    'CVV' => 0,   // Consonne-Voyelle-Voyelle (longue)
    'CVVC' => 0,  // Consonne-Voyelle-Voyelle-Consonne
    'V' => 0,     // Voyelle seule
    'VC' => 0     // Voyelle-Consonne
];

foreach ($schoolTexts as $level => $words) {
    echo "🎓 Niveau: $level\n";
    echo str_repeat('=', 50) . "\n";
    
    foreach ($words as $word => $meaning) {
        echo "📝 Mot: $word ($meaning)\n";
        
        try {
            $result = $syllableService->generateSyllables($word);
            $totalWords++;
            $totalSyllables += count($result);
            
            echo "   Syllabes: [";
            foreach ($result as $i => $syllable) {
                echo $syllable['syllable_text'];
                if ($i < count($result) - 1) echo ', ';
                
                // Compter les types de syllabes
                $type = $syllable['syllable_type'];
                if (isset($complexityStats[$type])) {
                    $complexityStats[$type]++;
                } else {
                    $complexityStats['CV']++; // Par défaut
                }
            }
            echo "]\n";
            
            echo "   Types: [";
            foreach ($result as $i => $syllable) {
                echo $syllable['syllable_type'];
                if ($i < count($result) - 1) echo ', ';
            }
            echo "]\n";
            
            echo "   Nombre de syllabes: " . count($result) . "\n";
            
        } catch (Exception $e) {
            echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "\n";
}

// Statistiques globales
echo "📊 STATISTIQUES PÉDAGOGIQUES\n";
echo str_repeat('=', 50) . "\n";
echo "Total des mots analysés: $totalWords\n";
echo "Total des syllabes générées: $totalSyllables\n";
echo "Moyenne syllabes/mot: " . round($totalSyllables / $totalWords, 2) . "\n\n";

echo "🔍 RÉPARTITION DES TYPES DE SYLLABES:\n";
foreach ($complexityStats as $type => $count) {
    $percentage = round(($count / $totalSyllables) * 100, 1);
    echo "   $type: $count syllabes ($percentage%)\n";
}

echo "\n📈 ANALYSE PÉDAGOGIQUE:\n";
echo "   • CV (simple): " . $complexityStats['CV'] . " - Idéal pour débutants\n";
echo "   • CVC (fermée): " . $complexityStats['CVC'] . " - Niveau intermédiaire\n";
echo "   • CVV (longue): " . $complexityStats['CVV'] . " - Apprentissage des voyelles longues\n";
echo "   • Complexes: " . ($complexityStats['CVVC'] + $complexityStats['V'] + $complexityStats['VC']) . " - Niveau avancé\n";

// Test de cas spéciaux pédagogiques
echo "\n🎯 CAS SPÉCIAUX PÉDAGOGIQUES:\n";
echo str_repeat('=', 50) . "\n";

$specialCases = [
    'Lam solaire' => [
        'الشَّمْسُ' => 'Le soleil',
        'الطَّالِبُ' => 'L\'élève',
        'الرَّجُلُ' => 'L\'homme'
    ],
    'Lam lunaire' => [
        'الْقَمَرُ' => 'La lune',
        'الْكِتَابُ' => 'Le livre',
        'الْبَيْتُ' => 'La maison'
    ],
    'Chadda complexe' => [
        'مُدَرِّسَةٌ' => 'Enseignante',
        'مُهَنِّدٌ' => 'Ingénieur',
        'مُتَرَجِّمٌ' => 'Traducteur'
    ]
];

foreach ($specialCases as $category => $words) {
    echo "📚 $category:\n";
    
    foreach ($words as $word => $meaning) {
        echo "   $word ($meaning): ";
        
        try {
            $result = $syllableService->generateSyllables($word);
            echo "[";
            foreach ($result as $i => $syllable) {
                echo $syllable['syllable_text'];
                if ($i < count($result) - 1) echo ', ';
            }
            echo "]\n";
            
        } catch (Exception $e) {
            echo "ERREUR: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}

echo "✅ Validation pédagogique terminée!\n";
