<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Lister toutes les images
     */
    public function index(): JsonResponse
    {
        $images = Image::with('words')
                      ->orderBy('created_at', 'desc')
                      ->get()
                      ->map(function ($image) {
                          return [
                              'id' => $image->id,
                              'image_path' => $image->image_path,
                              'image_url' => $image->image_url,
                              'description' => $image->description,
                              'words_count' => $image->words()->count(),
                              'is_used' => $image->isUsed(),
                              'created_at' => $image->created_at
                          ];
                      });

        return response()->json([
            'success' => true,
            'data' => $images
        ]);
    }

    /**
     * Uploader une nouvelle image
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'sometimes|string|max:255'
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
            
            $image = Image::create([
                'image_path' => $imagePath,
                'description' => $validated['description'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploadée avec succès',
                'data' => [
                    'id' => $image->id,
                    'image_path' => $image->image_path,
                    'image_url' => $image->image_url,
                    'description' => $image->description
                ]
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucune image fournie'
        ], 400);
    }

    /**
     * Afficher une image spécifique avec ses mots associés
     */
    public function show(string $id): JsonResponse
    {
        $image = Image::with('words')->find($id);

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'image' => $image,
                'image_url' => $image->image_url,
                'associated_words' => $image->words
            ]
        ]);
    }

    /**
     * Mettre à jour une image
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $image = Image::find($id);

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvée'
            ], 404);
        }

        $validated = $request->validate([
            'description' => 'sometimes|string|max:255'
        ]);

        $image->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Image mise à jour avec succès',
            'data' => $image
        ]);
    }

    /**
     * Supprimer une image
     */
    public function destroy(string $id): JsonResponse
    {
        $image = Image::find($id);

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvée'
            ], 404);
        }

        // Supprimer le fichier physique
        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image supprimée avec succès'
        ]);
    }

    /**
     * Associer une image à un mot
     */
    public function attachToWord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image_id' => 'required|exists:images,id',
            'word_id' => 'required|exists:words,id'
        ]);

        $image = Image::find($validated['image_id']);
        $word = Word::find($validated['word_id']);

        // Vérifier si l'association existe déjà
        if ($image->words()->where('word_id', $word->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette image est déjà associée à ce mot'
            ], 409);
        }

        $image->words()->attach($word->id);

        return response()->json([
            'success' => true,
            'message' => 'Image associée au mot avec succès'
        ]);
    }

    /**
     * Détacher une image d'un mot
     */
    public function detachFromWord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image_id' => 'required|exists:images,id',
            'word_id' => 'required|exists:words,id'
        ]);

        $image = Image::find($validated['image_id']);
        $word = Word::find($validated['word_id']);

        $image->words()->detach($word->id);

        return response()->json([
            'success' => true,
            'message' => 'Image détachée du mot avec succès'
        ]);
    }
}
