<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Word;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    protected ImageOptimizationService $imageService;
    
    public function __construct(ImageOptimizationService $imageService)
    {
        $this->imageService = $imageService;
    }
    
    /**
     * Récupérer les mots associés à une image
     */
    public function getWords(string $id): JsonResponse
    {
        $image = Image::find($id);
        
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvée'
            ], 404);
        }
        
        $words = $image->words()->get()->map(function ($word) {
            return [
                'id' => $word->id,
                'word' => $word->word,
                'word_normalized' => $word->word_normalized,
                'texts_count' => $word->texts()->count()
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $words
        ]);
    }
    
    /**
     * Connecter une image à plusieurs mots ou dissocier tous les mots
     */
    public function connectWords(Request $request, string $id): JsonResponse
    {
        $image = Image::find($id);
        
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvée'
            ], 404);
        }
        
        // Validation modifiée pour accepter un tableau vide (dissociation totale)
        $validated = $request->validate([
            'word_ids' => 'present|array',
            'word_ids.*' => 'exists:words,id'
        ]);
        
        // Synchroniser les mots (remplacer les associations existantes)
        // Sync avec un tableau vide supprime toutes les associations
        $image->words()->sync($validated['word_ids']);
        
        // Message adapté selon qu'il y a des mots connectés ou non
        $message = count($validated['word_ids']) > 0 
            ? count($validated['word_ids']) . ' mot(s) connecté(s) à l\'image avec succès'
            : 'Tous les mots ont été dissociés de l\'image avec succès';
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'image_id' => $image->id,
                'connected_words_count' => count($validated['word_ids'])
            ]
        ]);
    }
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
     * Uploader une nouvelle image avec optimisation automatique
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|file|max:10240', // 10MB max
            'description' => 'sometimes|string|max:255'
        ]);

        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune image fournie'
            ], 400);
        }

        $uploadedFile = $request->file('image');
        
        // Valider que c'est bien une image
        if (!$this->imageService->isValidImage($uploadedFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Format d\'image non supporté ou fichier trop volumineux'
            ], 422);
        }

        try {
            // Optimiser et stocker l'image avec toutes ses versions
            $optimizedData = $this->imageService->processUploadedImage($uploadedFile, 'uploads');
            
            // Créer l'entrée en base avec les métadonnées
            $image = Image::create([
                'image_path' => $optimizedData['paths']['original'],
                'thumbnail_path' => $optimizedData['paths']['thumbnail'],
                'preview_path' => $optimizedData['paths']['preview'],
                'mobile_path' => $optimizedData['paths']['mobile'],
                'original_name' => $optimizedData['original_name'],
                'filename' => $optimizedData['filename'],
                'file_size' => $optimizedData['size'],
                'mime_type' => $optimizedData['mime_type'],
                'description' => $validated['description'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploadée et optimisée avec succès',
                'data' => [
                    'id' => $image->id,
                    'original_name' => $image->original_name,
                    'description' => $image->description,
                    'file_size' => $image->file_size,
                    'mime_type' => $image->mime_type,
                    'urls' => $optimizedData['urls'],
                    'optimized' => true,
                    'versions' => ['original', 'thumbnail', 'preview', 'mobile']
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'optimisation de l\'image: ' . $e->getMessage()
            ], 500);
        }
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
     * Supprimer une image et toutes ses versions optimisées
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

        try {
            // Supprimer toutes les versions physiques
            $paths = [
                $image->image_path,
                $image->thumbnail_path,
                $image->preview_path,
                $image->mobile_path
            ];
            
            $this->imageService->deleteImageVersions($paths);
            
            // Supprimer l'entrée en base
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Image et toutes ses versions supprimées avec succès'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
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
