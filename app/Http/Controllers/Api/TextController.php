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
                            // Dériver les niveaux à partir des classes associées
                            $levels = $text->classrooms->map(function ($classroom) {
                                return $classroom->level ? $classroom->level->name : null;
                            })->filter()->unique()->values()->toArray();
                            
                            // Calculer les statistiques de lecture
                            $readingSessions = \App\Models\ReadingSession::where('text_id', $text->id)->get();
                            $uniqueStudents = $readingSessions->pluck('student_id')->unique()->count();
                            $totalReadingTime = $readingSessions->sum('duration') ?? 0; // en secondes
                            
                            return [
                                'id' => $text->id,
                                'title' => $text->title,
                                'content' => $text->content,
                                'content_preview' => mb_substr($text->content, 0, 100),
                                'derived_levels' => $levels, // Niveaux dérivés des classes
                                'classrooms' => $text->classrooms->map(function ($classroom) {
                                    // Vérifications de sécurité pour éviter les erreurs null
                                    $level = $classroom->level;
                                    return [
                                        'id' => $classroom->id,
                                        'name' => $classroom->name,
                                        'level' => $level ? [
                                            'id' => $level->id,
                                            'name' => $level->name,
                                        ] : null,
                                    ];
                                }),
                                'words_count' => $text->words_count ?? 0,
                                'interactions_count' => $text->interactions_count ?? 0,
                                'students_read_count' => $uniqueStudents, // Nombre d'élèves ayant lu
                                'total_reading_time' => $totalReadingTime, // Temps total en secondes
                                'created_at' => $text->created_at,
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
            'classroom_ids' => 'sometimes|array|min:1',
            'classroom_ids.*' => 'exists:classrooms,id'
        ]);

        DB::beginTransaction();
        try {
            // Créer le texte (sans difficulty_level)
            $text = Text::create([
                'title' => $validated['title'],
                'content' => $validated['content']
            ]);
            
            // Associer les classes si spécifiées
            if (isset($validated['classroom_ids'])) {
                $text->classrooms()->sync($validated['classroom_ids']);
            }
            
            // Analyser et créer les mots
            $this->processTextWords($text);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Texte créé avec succès',
                'data' => $text->load(['words', 'classrooms.level'])
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
            'classroom_ids' => 'sometimes|array',
            'classroom_ids.*' => 'exists:classrooms,id'
        ]);

        DB::beginTransaction();
        try {
            // Mettre à jour le texte (sans difficulty_level)
            $text->update([
                'title' => $validated['title'] ?? $text->title,
                'content' => $validated['content'] ?? $text->content
            ]);
            
            // Mettre à jour les associations de classes si spécifiées
            if (isset($validated['classroom_ids'])) {
                $text->classrooms()->sync($validated['classroom_ids']);
            }
            
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
        // Utiliser la méthode parseAndCreateWords du modèle Text
        // qui gère correctement la nouvelle structure avec word_text
        $text->parseAndCreateWords();
    }

    /**
     * Analyser et créer la structure linguistique d'un texte
     */
    public function parseTextStructure(Request $request, $textId): JsonResponse
    {
        try {
            $text = Text::findOrFail($textId);
            
            // Analyser et créer les mots avec leurs unités
            $text->parseAndCreateWords();
            
            // Charger la structure complète
            $text->load(['words.units.root', 'words.units.images']);
            
            return response()->json([
                'success' => true,
                'message' => 'Structure linguistique analysée avec succès',
                'data' => [
                    'text' => $text,
                    'words_count' => $text->words->count(),
                    'units_count' => $text->words->sum(function($word) {
                        return $word->units->count();
                    })
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'analyse de la structure linguistique: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse de la structure linguistique: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la structure complète d'un texte
     */
    public function getTextStructure($textId): JsonResponse
    {
        try {
            $text = Text::with([
                'words.units.root', 
                'words.units.images',
                'classrooms.level'
            ])->findOrFail($textId);
            
            return response()->json([
                'success' => true,
                'data' => $text
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la structure: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la structure'
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques linguistiques d'un texte
     */
    public function getTextStatistics($textId): JsonResponse
    {
        try {
            $text = Text::with(['words.units'])->findOrFail($textId);
            
            $stats = [
                'words_count' => $text->words->count(),
                'units_count' => $text->words->sum(function($word) {
                    return $word->units->count();
                }),
                'compound_words_count' => $text->words->where('is_compound', true)->count(),
                'simple_words_count' => $text->words->where('is_compound', false)->count(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul des statistiques: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques'
            ], 500);
        }
    }

    /**
     * Générer les syllabes pour tous les mots d'un texte
     */
    public function generateSyllables($textId): JsonResponse
    {
        try {
            $text = Text::with('words')->findOrFail($textId);
            
            $syllablesCount = 0;
            foreach ($text->words as $word) {
                $word->generateSyllables();
                $syllablesCount += $word->syllables()->count();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Syllabes générées avec succès',
                'data' => [
                    'words_count' => $text->words->count(),
                    'syllables_count' => $syllablesCount
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération des syllabes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des syllabes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les syllabes d'un mot spécifique
     */
    public function getWordSyllables($textId, $wordId): JsonResponse
    {
        try {
            $word = Word::with('syllables')
                       ->where('text_id', $textId)
                       ->findOrFail($wordId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'word' => $word->word_text,
                    'syllables' => $word->syllables->map(function($syllable) {
                        return [
                            'id' => $syllable->id,
                            'text' => $syllable->syllable_text,
                            'position' => $syllable->syllable_position,
                            'type' => $syllable->syllable_type,
                            'is_stressed' => $syllable->is_stressed
                        ];
                    })
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des syllabes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des syllabes'
            ], 500);
        }
    }

    /**
     * Mettre à jour les syllabes d'un mot (correction manuelle)
     */
    public function updateWordSyllables(Request $request, $textId, $wordId): JsonResponse
    {
        try {
            $word = Word::where('text_id', $textId)->findOrFail($wordId);
            
            $validated = $request->validate([
                'syllables' => 'required|array',
                'syllables.*.text' => 'required|string|max:10',
                'syllables.*.position' => 'required|integer|min:1',
                'syllables.*.type' => 'required|in:CV,CVC,CVCC,V',
                'syllables.*.is_stressed' => 'boolean'
            ]);
            
            // Supprimer les anciennes syllabes
            $word->syllables()->delete();
            
            // Créer les nouvelles syllabes
            foreach ($validated['syllables'] as $syllableData) {
                $word->syllables()->create([
                    'syllable_text' => $syllableData['text'],
                    'syllable_position' => $syllableData['position'],
                    'syllable_type' => $syllableData['type'],
                    'is_stressed' => $syllableData['is_stressed'] ?? false
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Syllabes mises à jour avec succès',
                'data' => $word->load('syllables')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des syllabes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des syllabes'
            ], 500);
        }
    }
}
