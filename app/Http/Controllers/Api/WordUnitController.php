<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Word;
use App\Models\WordUnit;
use App\Models\Root;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WordUnitController extends Controller
{
    /**
     * Décomposer un mot en unités
     */
    public function decomposeWord(Request $request, string $wordId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'units' => 'required|array|min:1',
            'units.*.text' => 'required|string',
            'units.*.position' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $word = Word::find($wordId);
        if (!$word) {
            return response()->json([
                'success' => false,
                'message' => 'Mot non trouvé'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Sauvegarder les images des anciennes unités pour réaffectation
            $oldImages = $word->getAllUnitImages();

            // Supprimer les anciennes unités
            $word->units()->delete();

            // Créer les nouvelles unités
            $units = [];
            foreach ($request->units as $unitData) {
                $unit = $word->units()->create([
                    'unit_text' => $unitData['text'],
                    'unit_normalized' => \App\Models\Text::normalizeArabicWord($unitData['text']),
                    'position' => $unitData['position']
                ]);
                $units[] = $unit;
            }

            // Mettre à jour le statut composé du mot
            $word->update(['is_compound' => count($units) > 1]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mot décomposé avec succès',
                'data' => [
                    'word' => $word->load('units'),
                    'orphaned_images' => $oldImages,
                    'requires_image_reassignment' => $oldImages->count() > 0
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la décomposition du mot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fusionner des unités d'un mot
     */
    public function mergeUnits(Request $request, string $wordId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'unit_ids' => 'required|array|min:2',
            'unit_ids.*' => 'required|integer|exists:word_units,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $word = Word::find($wordId);
        if (!$word) {
            return response()->json([
                'success' => false,
                'message' => 'Mot non trouvé'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Vérifier que les unités appartiennent bien à ce mot
            $units = $word->units()->whereIn('id', $request->unit_ids)->get();
            if ($units->count() !== count($request->unit_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certaines unités ne correspondent pas à ce mot'
                ], 422);
            }

            // Collecter les images des unités à fusionner
            $orphanedImages = collect();
            foreach ($units as $unit) {
                $orphanedImages = $orphanedImages->merge($unit->images);
            }

            // Fusionner les unités
            $mergedUnit = $word->mergeUnits($request->unit_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unités fusionnées avec succès',
                'data' => [
                    'word' => $word->load('units'),
                    'merged_unit' => $mergedUnit,
                    'orphaned_images' => $orphanedImages->unique('id')->values(),
                    'requires_image_reassignment' => $orphanedImages->count() > 0
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la fusion des unités',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assigner un type linguistique à une unité
     */
    public function assignLinguisticType(Request $request, string $unitId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'linguistic_type' => 'required|string|in:اسم,فعل,حرف'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Type linguistique invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        $unit = WordUnit::find($unitId);
        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unité non trouvée'
            ], 404);
        }

        try {
            $unit->assignLinguisticType($request->linguistic_type);

            return response()->json([
                'success' => true,
                'message' => 'Type linguistique assigné avec succès',
                'data' => $unit->load(['root', 'images'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation du type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assigner une racine à une unité
     */
    public function assignRoot(Request $request, string $unitId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'root_id' => 'nullable|integer|exists:roots,id',
            'root_text' => 'nullable|string|required_without:root_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $unit = WordUnit::find($unitId);
        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unité non trouvée'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $root = null;

            if ($request->root_id) {
                $root = Root::find($request->root_id);
            } elseif ($request->root_text) {
                // Créer ou trouver la racine
                $root = Root::firstOrCreate(
                    ['root_text' => $request->root_text],
                    ['root_normalized' => \App\Models\Text::normalizeArabicWord($request->root_text)]
                );
            }

            if ($root) {
                $unit->assignRoot($root);
            } else {
                $unit->update(['root_id' => null]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Racine assignée avec succès',
                'data' => $unit->load(['root', 'images'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation de la racine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Associer des images à une unité
     */
    public function associateImages(Request $request, string $unitId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image_ids' => 'required|array',
            'image_ids.*' => 'integer|exists:images,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $unit = WordUnit::find($unitId);
        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unité non trouvée'
            ], 404);
        }

        try {
            $unit->associateImages($request->image_ids);

            return response()->json([
                'success' => true,
                'message' => 'Images associées avec succès',
                'data' => $unit->load(['root', 'images'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'association des images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir toutes les racines disponibles
     */
    public function getRoots(Request $request): JsonResponse
    {
        $query = Root::query();

        if ($request->has('search') && !empty($request->search)) {
            $query->byRootText($request->search);
        }

        $roots = $query->orderBy('root_text')->get();

        return response()->json([
            'success' => true,
            'data' => $roots
        ]);
    }

    /**
     * Obtenir les types linguistiques disponibles
     */
    public function getLinguisticTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => WordUnit::LINGUISTIC_TYPES
        ]);
    }

    /**
     * Réassigner des images orphelines après fusion/décomposition
     */
    public function reassignOrphanedImages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reassignments' => 'required|array',
            'reassignments.*.image_id' => 'required|integer|exists:images,id',
            'reassignments.*.unit_id' => 'required|integer|exists:word_units,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->reassignments as $reassignment) {
                $unit = WordUnit::find($reassignment['unit_id']);
                $imageIds = $unit->images()->pluck('images.id')->toArray();
                
                if (!in_array($reassignment['image_id'], $imageIds)) {
                    $imageIds[] = $reassignment['image_id'];
                    $unit->associateImages($imageIds);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Images réassignées avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réassignation des images',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
