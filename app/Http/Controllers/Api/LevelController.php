<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LevelController extends Controller
{
    /**
     * Lister tous les niveaux avec leurs classes
     */
    public function index(): JsonResponse
    {
        $levels = Level::with(['classrooms.students'])
                      ->orderBy('name')
                      ->get()
                      ->map(function ($level) {
                          return [
                              'id' => $level->id,
                              'name' => $level->name,
                              'total_classrooms' => $level->total_classrooms,
                              'total_students' => $level->total_students,
                              'texts_count' => $level->texts_count,
                              'classrooms' => $level->classrooms->map(function ($classroom) {
                                  return [
                                      'id' => $classroom->id,
                                      'name' => $classroom->name,
                                      'section' => $classroom->section,
                                      'total_students' => $classroom->total_students,
                                      'total_reading_time' => $classroom->total_reading_time,
                                      'total_words_read' => $classroom->total_words_read
                                  ];
                              })
                          ];
                      });

        return response()->json([
            'success' => true,
            'data' => $levels
        ]);
    }

    /**
     * Obtenir un niveau spécifique avec ses classes et élèves
     */
    public function show(Level $level): JsonResponse
    {
        $level->load(['classrooms.students']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $level->id,
                'name' => $level->name,
                'total_classrooms' => $level->total_classrooms,
                'total_students' => $level->total_students,
                'classrooms' => $level->classrooms->map(function ($classroom) {
                    return [
                        'id' => $classroom->id,
                        'name' => $classroom->name,
                        'section' => $classroom->section,
                        'total_students' => $classroom->total_students,
                        'students' => $classroom->students->map(function ($student) {
                            return [
                                'id' => $student->id,
                                'name' => $student->name,
                                'age' => $student->age,
                                'total_words_read' => $student->total_words_read,
                                'total_reading_time' => $student->total_reading_time
                            ];
                        })
                    ];
                })
            ]
        ]);
    }

    /**
     * Créer un nouveau niveau
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:levels'
        ]);

        $level = Level::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $level->id,
                'name' => $level->name,
                'total_classrooms' => 0,
                'total_students' => 0,
                'texts_count' => 0
            ]
        ], 201);
    }

    /**
     * Mettre à jour un niveau existant
     */
    public function update(Request $request, Level $level): JsonResponse
    {
        // Empêcher la modification d'un niveau déjà créé
        return response()->json([
            'success' => false,
            'message' => 'Modification interdite : Un niveau déjà créé ne peut pas être modifié pour préserver l\'intégrité des données pédagogiques.'
        ], 403);
    }

    /**
     * Supprimer un niveau
     */
    public function destroy(Level $level): JsonResponse
    {
        try {
            // Vérifier s'il y a des classes associées
            $classroomsCount = $level->classrooms()->count();
            
            if ($classroomsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Suppression impossible : Ce niveau contient {$classroomsCount} classe(s). Supprimez d'abord toutes les classes associées avant de supprimer le niveau."
                ], 422);
            }

            // Vérifier s'il y a des élèves associés via les classes (sécurité supplémentaire)
            $studentsCount = $level->total_students;
            if ($studentsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Suppression impossible : Ce niveau contient {$studentsCount} élève(s). Supprimez d'abord tous les élèves et classes associés."
                ], 422);
            }

            // Vérifier s'il y a des textes associés (sécurité supplémentaire)
            $textsCount = \App\Models\Text::where('difficulty_level', $level->name)->count();
            if ($textsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Suppression impossible : Ce niveau contient {$textsCount} texte(s) associé(s). Dissociez d'abord tous les textes."
                ], 422);
            }

            $levelName = $level->name;
            $level->delete();

            Log::info("Niveau '{$levelName}' supprimé avec succès");

            return response()->json([
                'success' => true,
                'message' => "Niveau '{$levelName}' supprimé avec succès."
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du niveau: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du niveau: ' . $e->getMessage()
            ], 500);
        }
    }


}
