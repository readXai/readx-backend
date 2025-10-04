<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Word;
use App\Models\Text;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WordController extends Controller
{
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
