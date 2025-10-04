<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * Lister tous les élèves
     */
    public function index(): JsonResponse
    {
        $students = Student::with(['interactions', 'readingSessions'])
                          ->orderBy('name')
                          ->get()
                          ->map(function ($student) {
                              return [
                                  'id' => $student->id,
                                  'name' => $student->name,
                                  'level' => $student->level,
                                  'age' => $student->age,
                                  'total_words_read' => $student->total_words_read,
                                  'total_reading_time' => $student->total_reading_time,
                                  'created_at' => $student->created_at
                              ];
                          });

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Créer un nouvel élève
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => ['required', Rule::in(['CE1', 'CE2', 'CM1', 'CM2'])],
            'age' => 'required|integer|min:6|max:12'
        ]);

        $student = Student::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Élève créé avec succès',
            'data' => $student
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
            'level' => ['sometimes', Rule::in(['CE1', 'CE2', 'CM1', 'CM2'])],
            'age' => 'sometimes|integer|min:6|max:12'
        ]);

        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Élève mis à jour avec succès',
            'data' => $student
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
