<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Word;
use App\Models\Text;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WordController extends Controller
{
    /**
     * Lister tous les mots disponibles avec recherche avancée
     */
    public function index(Request $request): JsonResponse
    {
        // Débogage : afficher tous les paramètres de la requête
        \Illuminate\Support\Facades\Log::info('WordController.index - Paramètres de recherche', [
            'params' => $request->all(),
            'has_q' => $request->has('q'),
            'q_value' => $request->q ?? 'non défini'
        ]);
        
        $query = Word::with('images');
        $debugInfo = [];
        
        // Recherche avancée par lettres non consécutives
        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;
            
            // Normaliser le terme de recherche (enlever TOUS les harakat)
            $normalizedSearchTerm = $this->removeAllDiacritics($searchTerm);
            
            // Débogage : afficher le terme normalisé
            \Illuminate\Support\Facades\Log::info('WordController.index - Terme normalisé', [
                'original' => $searchTerm,
                'normalized' => $normalizedSearchTerm
            ]);
            
            // Convertir la requête en expression régulière pour trouver les lettres dans l'ordre
            // mais pas nécessairement consécutives
            $letters = mb_str_split($normalizedSearchTerm, 1, 'UTF-8');
            
            // Débogage : afficher les lettres extraites
            \Illuminate\Support\Facades\Log::info('WordController.index - Lettres extraites', [
                'letters' => $letters,
                'count' => count($letters)
            ]);
            
            // Approche 1: Utiliser LIKE avec des % entre chaque lettre
            // Cette approche est plus compatible avec toutes les bases de données
            if (count($letters) > 0) {
                $likePattern = '%';
                foreach ($letters as $letter) {
                    $likePattern .= $letter . '%';
                }
                
                // Débogage : afficher le pattern LIKE
                \Illuminate\Support\Facades\Log::info('WordController.index - Pattern LIKE', [
                    'pattern' => $likePattern
                ]);
                
                $query->where('word_normalized', 'LIKE', $likePattern);
                
                // Ajouter des informations de débogage
                $debugInfo = [
                    'search_term' => $searchTerm,
                    'normalized_search' => $normalizedSearchTerm,
                    'like_pattern' => $likePattern,
                    'letters' => $letters
                ];
            }
            
            // Approche 2: Utiliser des conditions multiples pour vérifier la présence de chaque lettre
            // dans l'ordre correct (plus robuste que REGEXP qui peut ne pas fonctionner correctement
            // avec les caractères arabes dans certaines configurations MySQL/MariaDB)
            if (count($letters) > 0 && false) { // Désactivé pour l'instant
                $query->where(function($q) use ($letters) {
                    $firstLetter = array_shift($letters);
                    $q->where('word_normalized', 'LIKE', "%$firstLetter%");
                    
                    $position = "LOCATE('$firstLetter', word_normalized)";
                    
                    foreach ($letters as $letter) {
                        $q->whereRaw("LOCATE('$letter', word_normalized) > $position");
                        $position = "LOCATE('$letter', word_normalized)";
                    }
                });
            }
        }
        
        // Exécuter la requête et récupérer les résultats
        $words = $query->orderBy('word')->get();
        
        // Débogage : afficher le nombre de résultats
        \Illuminate\Support\Facades\Log::info('WordController.index - Résultats de la requête', [
            'count' => $words->count(),
            'first_5' => $words->take(5)->pluck('word')->toArray()
        ]);
        
        // Transformer les résultats
        $transformedWords = $words->map(function ($word) use ($request, $debugInfo) {
            $item = [
                'id' => $word->id,
                'word' => $word->word,
                'word_normalized' => $word->word_normalized,
                'images_count' => $word->images()->count(),
                'texts_count' => $word->texts()->count()
            ];
            
            // Si on est en mode recherche, ajouter le score de pertinence
            if ($request->has('q') && !empty($request->q)) {
                // Normaliser le terme de recherche (enlever TOUS les harakat)
                $searchTerm = $this->removeAllDiacritics($request->q);
                $wordText = $word->word_normalized;
                
                // Calcul de score de pertinence (plus bas = meilleur)
                // Basé sur la distance entre les lettres recherchées et leur position
                $score = 0;
                $lastPos = -1;
                $found = true;
                $letters = mb_str_split($searchTerm, 1, 'UTF-8');
                
                foreach ($letters as $letter) {
                    // Trouver la position de la lettre après la position précédente
                    $pos = mb_strpos($wordText, $letter, $lastPos + 1, 'UTF-8');
                    if ($pos === false) {
                        $found = false;
                        break;
                    }
                    
                    // Ajouter la distance entre les lettres au score
                    if ($lastPos >= 0) {
                        // Plus les lettres sont éloignées, plus le score augmente
                        $score += ($pos - $lastPos);
                    } else {
                        // Bonus pour les mots qui commencent par la première lettre recherchée
                        $score += $pos * 2;
                    }
                    
                    $lastPos = $pos;
                }
                
                // Bonus pour les mots plus courts (plus précis)
                if ($found) {
                    $wordLength = mb_strlen($wordText, 'UTF-8');
                    $searchLength = mb_strlen($searchTerm, 'UTF-8');
                    $item['relevance_score'] = $score + ($wordLength - $searchLength);
                }
            }
            
            return $item;
        });
        
        // Débogage : afficher les mots transformés
        \Illuminate\Support\Facades\Log::info('WordController.index - Mots transformés', [
            'count' => $transformedWords->count(),
            'first_5' => $transformedWords->take(5)->toArray()
        ]);
        
        // Si on est en mode recherche, trier par score de pertinence
        if ($request->has('q') && !empty($request->q)) {
            $transformedWords = $transformedWords->filter(function ($word) {
                return isset($word['relevance_score']);
            })->sortBy('relevance_score')->values();
            
            // Ajouter des informations de débogage pour la recherche
            $debugInfo = [
                'search_term' => $request->q,
                'normalized_search' => $this->removeAllDiacritics($request->q),
                'total_results' => count($transformedWords),
                'like_pattern' => '%' . implode('%', mb_str_split($this->removeAllDiacritics($request->q), 1, 'UTF-8')) . '%',
                'example_matches' => $transformedWords->take(5)->map(function ($word) {
                    return [
                        'id' => $word['id'],
                        'word' => $word['word'],
                        'score' => $word['relevance_score'] ?? null
                    ];
                })
            ];
            
            // Débogage : afficher les résultats filtrés et triés
            \Illuminate\Support\Facades\Log::info('WordController.index - Résultats filtrés et triés', [
                'count' => $transformedWords->count(),
                'first_5' => $transformedWords->take(5)->pluck('word')->toArray(),
                'debug_info' => $debugInfo
            ]);
        }

        // Débogage : afficher la réponse finale
        \Illuminate\Support\Facades\Log::info('WordController.index - Réponse finale', [
            'success' => true,
            'data_count' => $transformedWords->count(),
            'has_debug_info' => isset($debugInfo)
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $transformedWords,
            'debug' => $debugInfo ?? null
        ]);
    }

    /**
     * Rechercher des mots similaires pour suggestions d'images
     */
    public function searchSimilar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'word' => 'required|string'
        ]);

        $similarWords = Word::findSimilarWords($validated['word']);

        return response()->json([
            'success' => true,
            'data' => $similarWords->map(function ($word) {
                return [
                    'id' => $word->id,
                    'word' => $word->word,
                    'word_normalized' => $word->word_normalized,
                    'images' => $word->images,
                    'texts_count' => $word->texts()->count()
                ];
            })
        ]);
    }

    /**
     * Obtenir les suggestions d'images pour un mot
     */
    public function getImageSuggestions(string $id): JsonResponse
    {
        $word = Word::find($id);

        if (!$word) {
            return response()->json([
                'success' => false,
                'message' => 'Mot non trouvé'
            ], 404);
        }

        $suggestions = $word->getImageSuggestions();

        return response()->json([
            'success' => true,
            'data' => [
                'word' => $word,
                'current_images' => $word->images,
                'suggested_images' => $suggestions
            ]
        ]);
    }

    /**
     * Afficher un mot avec ses détails
     */
    public function show(string $id): JsonResponse
    {
        $word = Word::with(['images', 'texts', 'interactions.student'])->find($id);

        if (!$word) {
            return response()->json([
                'success' => false,
                'message' => 'Mot non trouvé'
            ], 404);
        }

        // Statistiques du mot
        $stats = [
            'total_interactions' => $word->interactions()->count(),
            'students_who_read' => $word->interactions()->distinct('student_id')->count(),
            'help_requests' => $word->interactions()->where('action_type', '!=', 'click')->count(),
            'texts_containing' => $word->texts()->count(),
            'images_associated' => $word->images()->count()
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'word' => $word,
                'statistics' => $stats
            ]
        ]);
    }

    /**
     * Découper un mot en syllabes (fonction d'aide pour l'interface)
     */
    public function getSyllables(string $id): JsonResponse
    {
        $word = Word::find($id);

        if (!$word) {
            return response()->json([
                'success' => false,
                'message' => 'Mot non trouvé'
            ], 404);
        }

        // Découpage simple en syllabes pour l'arabe
        // Cette logique peut être améliorée selon les règles de syllabation arabe
        $wordText = $word->word;
        $syllables = $this->splitIntoSyllables($wordText);

        return response()->json([
            'success' => true,
            'data' => [
                'word' => $wordText,
                'syllables' => $syllables
            ]
        ]);
    }

    /**
     * Découper un mot en lettres individuelles
     */
    public function getLetters(string $id): JsonResponse
    {
        $word = Word::find($id);

        if (!$word) {
            return response()->json([
                'success' => false,
                'message' => 'Mot non trouvé'
            ], 404);
        }

        // Séparer en caractères individuels (compatible UTF-8 pour l'arabe)
        $letters = mb_str_split($word->word, 1, 'UTF-8');

        return response()->json([
            'success' => true,
            'data' => [
                'word' => $word->word,
                'letters' => $letters,
                'letters_count' => count($letters)
            ]
        ]);
    }

    /**
     * Obtenir la version sans vocalisation d'un mot
     */
    public function getWithoutVocalization(string $id): JsonResponse
    {
        $word = Word::find($id);

        if (!$word) {
            return response()->json([
                'success' => false,
                'message' => 'Mot non trouvé'
            ], 404);
        }

        $withoutVocalization = Text::normalizeArabicWord($word->word);

        return response()->json([
            'success' => true,
            'data' => [
                'original_word' => $word->word,
                'without_vocalization' => $withoutVocalization,
                'normalized' => $word->word_normalized
            ]
        ]);
    }

    /**
     * Fonction privée pour découper en syllabes (logique simplifiée)
     */
    /**
     * Fonction améliorée pour supprimer TOUS les signes diacritiques (harakat) des mots arabes
     * Plus complète que la fonction normalizeArabicWord standard
     */
    private function removeAllDiacritics(string $word): string
    {
        if (empty($word)) {
            return '';
        }
        
        // Débogage : afficher le mot avant normalisation
        \Illuminate\Support\Facades\Log::debug('removeAllDiacritics - Avant normalisation', ['word' => $word]);
        
        // Liste complète des signes diacritiques arabes à supprimer
        $diacritics = [
            // Harakat (voyelles courtes)
            'َ', // fatha
            'ُ', // damma
            'ِ', // kasra
            'ْ', // sukun
            
            // Tanwin (nounation)
            'ً', // fathatan
            'ٌ', // dammatan
            'ٍ', // kasratan
            
            // Autres signes
            'ّ', // shadda
            'ٰ', // alif khanjareeya
            'ـ', // tatweel
            
            // Signes supplémentaires
            'ٓ', // maddah
            'ٔ', // hamza above
            'ٕ', // hamza below
            'ٖ', // subscript alef
            'ٗ', // inverted damma
            '٘', // mark noon ghunna
            'ٙ', // zwarakay
            'ٚ', // vowel sign small v above
            'ٛ', // vowel sign inverted small v
            'ٜ', // vowel sign dot below
            'ٝ', // reversed damma
            'ٞ', // fatha with two dots
            'ٟ', // wavy hamza below
            'ٰ', // superscript alef
        ];
        
        // Méthode 1 : Utiliser str_replace
        $normalized = str_replace($diacritics, '', $word);
        
        // Méthode 2 : Utiliser une expression régulière (plus complète)
        // Cette expression régulière supprime tous les signes diacritiques arabes
        $normalized = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $normalized);
        
        // Débogage : afficher le mot après normalisation
        \Illuminate\Support\Facades\Log::debug('removeAllDiacritics - Après normalisation', ['word' => $normalized]);
        
        return $normalized;
    }
    
    private function splitIntoSyllables(string $word): array
    {
        // Logique simplifiée de découpage en syllabes pour l'arabe
        // Dans une implémentation complète, il faudrait des règles plus sophistiquées
        $letters = mb_str_split($word, 1, 'UTF-8');
        $syllables = [];
        $currentSyllable = '';
        
        foreach ($letters as $letter) {
            $currentSyllable .= $letter;
            
            // Règle simple : chaque voyelle longue ou consonne + voyelle forme une syllabe
            if (in_array($letter, ['ا', 'و', 'ي', 'ى']) || 
                (mb_strlen($currentSyllable) >= 2 && !in_array($letter, ['ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ْ', 'ّ', 'ٰ']))) {
                $syllables[] = $currentSyllable;
                $currentSyllable = '';
            }
        }
        
        if (!empty($currentSyllable)) {
            $syllables[] = $currentSyllable;
        }
        
        return empty($syllables) ? [$word] : $syllables;
    }
}
