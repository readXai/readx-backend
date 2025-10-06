<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Lister tous les élèves
     */
    public function index(): JsonResponse
    {
        try {
            $students = Student::with(['classroom.level', 'interactions', 'readingSessions'])
                              ->orderBy('name')
                              ->get()
                              ->map(function ($student) {
                                  // Vérifications de sécurité pour éviter les erreurs null
                                  $classroom = $student->classroom;
                                  $level = $classroom ? $classroom->level : null;
                                  
                                  return [
                                      'id' => $student->id,
                                      'name' => $student->name,
                                      'classroom' => $classroom ? [
                                          'id' => $classroom->id,
                                          'name' => $classroom->name,
                                          'section' => $classroom->section,
                                          'level' => $level ? [
                                              'id' => $level->id,
                                              'name' => $level->name
                                          ] : null
                                      ] : null,
                                      'total_words_read' => $student->total_words_read ?? 0,
                                      'total_reading_time' => $student->total_reading_time ?? 0,
                                      'created_at' => $student->created_at
                                  ];
                              });

            return response()->json([
                'success' => true,
                'data' => $students
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des élèves: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des élèves'
            ], 500);
        }
    }

    /**
     * Créer un nouvel élève
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id'
        ]);

        $student = Student::create($validated);
        $student->load('classroom.level');

        return response()->json([
            'success' => true,
            'message' => 'Élève créé avec succès',
            'data' => [
                'id' => $student->id,
                'name' => $student->name,
                'classroom' => [
                    'id' => $student->classroom->id,
                    'name' => $student->classroom->name,
                    'section' => $student->classroom->section
                ],
                'level' => [
                    'id' => $student->classroom->level->id,
                    'name' => $student->classroom->level->name
                ],
                'created_at' => $student->created_at
            ]
        ], 201);
    }

    /**
     * Afficher un élève spécifique avec ses statistiques
     */
    public function show(string $id): JsonResponse
    {
        $student = Student::with([
            'interactions.word',
            'interactions.text',
            'readingSessions.text'
        ])->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        }

        // Statistiques détaillées
        $stats = [
            'total_words_read' => $student->interactions()->where('action_type', 'click')->count(),
            'words_with_help' => $student->interactions()->where('action_type', '!=', 'click')->count(),
            'total_reading_time' => $student->total_reading_time,
            'sessions_count' => $student->readingSessions()->count(),
            'familiar_words' => $student->interactions()
                                      ->selectRaw('word_id, COUNT(*) as read_count')
                                      ->groupBy('word_id')
                                      ->having('read_count', '>=', 3)
                                      ->count()
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $student,
                'statistics' => $stats
            ]
        ]);
    }

    /**
     * Mettre à jour un élève
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'classroom_id' => 'sometimes|exists:classrooms,id'
        ]);

        $student->update($validated);
        $student->load('classroom.level');

        return response()->json([
            'success' => true,
            'message' => 'Élève mis à jour avec succès',
            'data' => [
                'id' => $student->id,
                'name' => $student->name,
                'classroom' => [
                    'id' => $student->classroom->id,
                    'name' => $student->classroom->name,
                    'section' => $student->classroom->section
                ],
                'level' => [
                    'id' => $student->classroom->level->id,
                    'name' => $student->classroom->level->name
                ],
                'updated_at' => $student->updated_at
            ]
        ]);
    }

    /**
     * Supprimer un élève
     */
    public function destroy(string $id): JsonResponse
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Élève supprimé avec succès'
        ]);
    }
}
