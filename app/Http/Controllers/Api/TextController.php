<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Text;
use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TextController extends Controller
{
    /**
     * Lister tous les textes avec filtrage optionnel par classe
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Text::with(['words', 'interactions', 'readingSessions', 'classrooms.level']);
            
            // Filtrer par classe si spécifié
            if ($request->has('classroom_id')) {
                $query->whereHas('classrooms', function ($q) use ($request) {
                    $q->where('classroom_id', $request->classroom_id);
                });
            }
            
            // Filtrer par niveau si spécifié (pour compatibilité)
            if ($request->has('level')) {
                $query->whereHas('classrooms.level', function ($q) use ($request) {
                    $q->where('name', $request->level);
                });
            }

            $texts = $query->orderBy('title')
                        ->get()
                        ->map(function ($text) {
                            return [
                                'id' => $text->id,
                                'title' => $text->title,
                                'content' => $text->content,
                                'content_preview' => mb_substr($text->content, 0, 100),
                                'difficulty_level' => $text->difficulty_level, // Gardé pour compatibilité
                                'classrooms' => $text->classrooms->map(function ($classroom) {
                                    // Vérifications de sécurité pour éviter les erreurs null
                                    $level = $classroom->level;
                                    
                                    return [
                                        'id' => $classroom->id,
                                        'name' => $classroom->name,
                                        'level' => $level ? [
                                            'id' => $level->id,
                                            'name' => $level->name,
                                            'order' => $level->order
                                        ] : null
                                    ];
                                }),
                                'words_count' => $text->words()->count(),
                                'interactions_count' => $text->interactions()->count(),
                                'created_at' => $text->created_at
                            ];
                        });

            return response()->json([
                'success' => true,
                'data' => $texts
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des textes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des textes'
            ], 500);
        }
    }

    /**
     * Créer un nouveau texte avec analyse automatique des mots
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'difficulty_level' => ['required', Rule::in(['CE1', 'CE2', 'CM1', 'CM2'])]
        ]);

        DB::beginTransaction();
        try {
            // Créer le texte
            $text = Text::create($validated);
            
            // Analyser et créer les mots
            $this->processTextWords($text);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Texte créé avec succès',
                'data' => $text->load('words')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du texte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un texte avec ses mots et suggestions d'images
     */
    public function show(string $id): JsonResponse
    {
        $text = Text::with([
            'words.images',
            'interactions.student',
            'readingSessions.student'
        ])->find($id);

        if (!$text) {
            return response()->json([
                'success' => false,
                'message' => 'Texte non trouvé'
            ], 404);
        }

        // Ajouter les suggestions d'images pour chaque mot
        $wordsWithSuggestions = $text->words->map(function ($word) {
            return [
                'id' => $word->id,
                'word' => $word->word,
                'word_normalized' => $word->word_normalized,
                'position' => $word->pivot->position,
                'current_images' => $word->images,
                'suggested_images' => $word->getImageSuggestions()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'text' => $text,
                'words_with_suggestions' => $wordsWithSuggestions
            ]
        ]);
    }

    /**
     * Mettre à jour un texte
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $text = Text::find($id);

        if (!$text) {
            return response()->json([
                'success' => false,
                'message' => 'Texte non trouvé'
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'difficulty_level' => ['sometimes', Rule::in(['CE1', 'CE2', 'CM1', 'CM2'])]
        ]);

        DB::beginTransaction();
        try {
            $text->update($validated);
            
            // Si le contenu a changé, reanalyser les mots
            if (isset($validated['content'])) {
                // Supprimer les anciennes associations
                $text->words()->detach();
                // Recréer les mots
                $this->processTextWords($text);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Texte mis à jour avec succès',
                'data' => $text->load('words')
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un texte
     */
    public function destroy(string $id): JsonResponse
    {
        $text = Text::find($id);

        if (!$text) {
            return response()->json([
                'success' => false,
                'message' => 'Texte non trouvé'
            ], 404);
        }

        $text->delete();

        return response()->json([
            'success' => true,
            'message' => 'Texte supprimé avec succès'
        ]);
    }

    /**
     * Analyser le texte et créer/associer les mots
     */
    private function processTextWords(Text $text): void
    {
        $words = $text->parseWordsFromContent();
        
        foreach ($words as $position => $wordText) {
            $normalized = Text::normalizeArabicWord($wordText);
            
            // Chercher ou créer le mot
            $word = Word::firstOrCreate(
                ['word' => $wordText],
                ['word_normalized' => $normalized]
            );
            
            // Associer le mot au texte avec sa position
            $text->words()->attach($word->id, ['position' => $position + 1]);
        }
    }
}
