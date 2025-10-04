<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentInteraction;
use App\Models\Student;
use App\Models\Text;
use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class InteractionController extends Controller
{
    /**
     * Enregistrer une nouvelle interaction élève-mot
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'text_id' => 'required|exists:texts,id',
            'word_id' => 'required|exists:words,id',
            'action_type' => ['required', Rule::in([
                'click', 'double_click', 'help_syllables', 
                'help_letters', 'help_image', 'toggle_vocalization'
            ])],
            'metadata' => 'sometimes|array'
        ]);

        // Vérifier si une interaction existe déjà pour ce mot par cet élève
        $existingInteraction = StudentInteraction::where([
            'student_id' => $validated['student_id'],
            'word_id' => $validated['word_id']
        ])->first();

        if ($existingInteraction) {
            // Incrémenter le compteur de lecture
            $existingInteraction->increment('read_count');
            $existingInteraction->update([
                'action_type' => $validated['action_type'],
                'metadata' => $validated['metadata'] ?? null
            ]);
            $interaction = $existingInteraction;
        } else {
            // Créer une nouvelle interaction
            $interaction = StudentInteraction::create([
                'student_id' => $validated['student_id'],
                'text_id' => $validated['text_id'],
                'word_id' => $validated['word_id'],
                'action_type' => $validated['action_type'],
                'read_count' => 1,
                'metadata' => $validated['metadata'] ?? null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Interaction enregistrée avec succès',
            'data' => [
                'interaction' => $interaction,
                'is_word_familiar' => $interaction->isWordFamiliar(),
                'should_hide_vocalization' => $interaction->read_count >= 3
            ]
        ], 201);
    }

    /**
     * Obtenir les interactions d'un élève pour un texte spécifique
     */
    public function getStudentTextInteractions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'text_id' => 'required|exists:texts,id'
        ]);

        $interactions = StudentInteraction::with(['word', 'student', 'text'])
            ->where('student_id', $validated['student_id'])
            ->where('text_id', $validated['text_id'])
            ->orderBy('created_at')
            ->get()
            ->map(function ($interaction) {
                return [
                    'id' => $interaction->id,
                    'word' => $interaction->word->word,
                    'word_id' => $interaction->word_id,
                    'action_type' => $interaction->action_type,
                    'read_count' => $interaction->read_count,
                    'is_help_action' => $interaction->isHelpAction(),
                    'is_word_familiar' => $interaction->isWordFamiliar(),
                    'created_at' => $interaction->created_at
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $interactions
        ]);
    }

    /**
     * Obtenir les statistiques d'interactions pour un élève
     */
    public function getStudentStats(string $studentId): JsonResponse
    {
        $student = Student::find($studentId);
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        }

        $stats = [
            'total_interactions' => $student->interactions()->count(),
            'words_read_without_help' => $student->interactions()->where('action_type', 'click')->count(),
            'words_read_with_help' => $student->interactions()->where('action_type', '!=', 'click')->count(),
            'familiar_words_count' => $student->interactions()
                                            ->selectRaw('word_id, COUNT(*) as read_count')
                                            ->groupBy('word_id')
                                            ->having('read_count', '>=', 3)
                                            ->count(),
            'most_difficult_words' => $student->interactions()
                                            ->with('word')
                                            ->where('action_type', '!=', 'click')
                                            ->selectRaw('word_id, COUNT(*) as help_count')
                                            ->groupBy('word_id')
                                            ->orderByDesc('help_count')
                                            ->limit(10)
                                            ->get()
                                            ->map(function ($interaction) {
                                                return [
                                                    'word' => $interaction->word->word,
                                                    'help_count' => $interaction->help_count
                                                ];
                                            })
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Obtenir les mots familiers d'un élève (pour masquer la vocalisation)
     */
    public function getFamiliarWords(string $studentId): JsonResponse
    {
        $student = Student::find($studentId);
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        }

        $familiarWords = $student->interactions()
                               ->with('word')
                               ->selectRaw('word_id, COUNT(*) as read_count')
                               ->groupBy('word_id')
                               ->having('read_count', '>=', 3)
                               ->get()
                               ->pluck('word.word', 'word_id');

        return response()->json([
            'success' => true,
            'data' => $familiarWords
        ]);
    }

    /**
     * Supprimer toutes les interactions d'un élève pour un texte (reset)
     */
    public function resetStudentTextInteractions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'text_id' => 'required|exists:texts,id'
        ]);

        $deletedCount = StudentInteraction::where('student_id', $validated['student_id'])
                                         ->where('text_id', $validated['text_id'])
                                         ->delete();

        return response()->json([
            'success' => true,
            'message' => "Interactions réinitialisées avec succès ({$deletedCount} interactions supprimées)"
        ]);
    }
}
