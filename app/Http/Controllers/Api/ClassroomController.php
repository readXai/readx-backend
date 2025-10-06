<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ClassroomController extends Controller
{
    /**
     * Lister toutes les classes avec leurs élèves
     */
    public function index(): JsonResponse
    {
        $classrooms = Classroom::with(['level', 'students'])
                              ->ordered()
                              ->get()
                              ->map(function ($classroom) {
                                  return [
                                      'id' => $classroom->id,
                                      'name' => $classroom->name,
                                      'section' => $classroom->section,
                                      'description' => $classroom->description,
                                      'level' => $classroom->level ? [
                                          'id' => $classroom->level->id,
                                          'name' => $classroom->level->name,
                                          'description' => $classroom->level->description
                                      ] : null,
                                      'total_students' => $classroom->total_students,
                                      'total_reading_time' => $classroom->total_reading_time,
                                      'total_words_read' => $classroom->total_words_read,
                                      'students' => $classroom->students->map(function ($student) {
                                          return [
                                              'id' => $student->id,
                                              'name' => $student->name,
                                              'age' => $student->age
                                          ];
                                      })
                                  ];
                              });

        return response()->json([
            'success' => true,
            'data' => $classrooms
        ]);
    }

    /**
     * Créer une nouvelle classe
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'level_id' => 'required|exists:levels,id',
                'section' => 'required|string|max:1|regex:/^[A-Z]$/',
                'description' => 'nullable|string|max:255'
            ]);

            // Vérifier que la combinaison level_id + section n'existe pas déjà
            $exists = Classroom::where('level_id', $validated['level_id'])
                              ->where('section', $validated['section'])
                              ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une classe avec cette section existe déjà pour ce niveau'
                ], 422);
            }

            $level = Level::find($validated['level_id']);
            $classroom = Classroom::create([
                'level_id' => $validated['level_id'],
                'section' => $validated['section'],
                'name' => $level->name . $validated['section'],
                'description' => $validated['description'] ?? 'Classe ' . $level->name . ' section ' . $validated['section']
            ]);

            $classroom->load('level');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'section' => $classroom->section,
                    'description' => $classroom->description,
                    'level' => [
                        'id' => $classroom->level->id,
                        'name' => $classroom->level->name,
                        'description' => $classroom->level->description
                    ],
                    'total_students' => 0,
                    'total_reading_time' => 0,
                    'total_words_read' => 0
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Obtenir une classe spécifique avec ses élèves
     */
    public function show(Classroom $classroom): JsonResponse
    {
        $classroom->load(['level', 'students']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'section' => $classroom->section,
                'description' => $classroom->description,
                'level' => [
                    'id' => $classroom->level->id,
                    'name' => $classroom->level->name,
                    'description' => $classroom->level->description
                ],
                'total_students' => $classroom->total_students,
                'total_reading_time' => $classroom->total_reading_time,
                'total_words_read' => $classroom->total_words_read,
                'students' => $classroom->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'age' => $student->age,
                        'total_words_read' => $student->total_words_read,
                        'total_reading_time' => $student->total_reading_time
                    ];
                })
            ]
        ]);
    }

    /**
     * Mettre à jour une classe
     */
    public function update(Request $request, Classroom $classroom): JsonResponse
    {
        try {
            $validated = $request->validate([
                'section' => 'sometimes|string|max:1|regex:/^[A-Z]$/',
                'description' => 'nullable|string|max:255'
            ]);

            // Si la section change, vérifier qu'elle n'existe pas déjà pour ce niveau
            if (isset($validated['section']) && $validated['section'] !== $classroom->section) {
                $exists = Classroom::where('level_id', $classroom->level_id)
                                  ->where('section', $validated['section'])
                                  ->where('id', '!=', $classroom->id)
                                  ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Une classe avec cette section existe déjà pour ce niveau'
                    ], 422);
                }

                // Mettre à jour le nom si la section change
                $validated['name'] = $classroom->level->name . $validated['section'];
            }

            $classroom->update($validated);
            $classroom->load('level');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'section' => $classroom->section,
                    'description' => $classroom->description,
                    'level' => [
                        'id' => $classroom->level->id,
                        'name' => $classroom->level->name,
                        'description' => $classroom->level->description
                    ],
                    'total_students' => $classroom->total_students,
                    'total_reading_time' => $classroom->total_reading_time,
                    'total_words_read' => $classroom->total_words_read
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Supprimer une classe
     */
    public function destroy(Classroom $classroom): JsonResponse
    {
        // Vérifier s'il y a des élèves dans cette classe
        if ($classroom->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une classe qui contient des élèves'
            ], 422);
        }

        $classroom->delete();

        return response()->json([
            'success' => true,
            'message' => 'Classe supprimée avec succès'
        ]);
    }

    /**
     * Attribuer un niveau à une classe orpheline
     */
    public function assignLevel(Request $request, Classroom $classroom): JsonResponse
    {
        try {
            $validated = $request->validate([
                'level_id' => 'required|exists:levels,id'
            ]);

            $level = Level::find($validated['level_id']);
            
            // Vérifier que la combinaison level_id + section n'existe pas déjà
            $exists = Classroom::where('level_id', $validated['level_id'])
                              ->where('section', $classroom->section)
                              ->where('id', '!=', $classroom->id)
                              ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une classe avec la section "' . $classroom->section . '" existe déjà pour le niveau ' . $level->name . '. Veuillez modifier la section de la classe "' . $classroom->name . '" avant d\'attribuer ce niveau.'
                ], 422);
            }

            // Mettre à jour la classe avec le nouveau niveau (sans changer le nom)
            $classroom->update([
                'level_id' => $validated['level_id']
            ]);

            $classroom->load('level');

            return response()->json([
                'success' => true,
                'message' => 'Niveau attribué avec succès',
                'data' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'section' => $classroom->section,
                    'description' => $classroom->description,
                    'level' => [
                        'id' => $classroom->level->id,
                        'name' => $classroom->level->name,
                        'description' => $classroom->level->description
                    ],
                    'total_students' => $classroom->total_students,
                    'total_reading_time' => $classroom->total_reading_time,
                    'total_words_read' => $classroom->total_words_read
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);
        }
    }
}
