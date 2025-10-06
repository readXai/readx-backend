<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImageOptimizationService
{
    protected ImageManager $manager;
    
    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }
    
    /**
     * Optimise et stocke une image uploadée avec création de thumbnails
     */
    public function processUploadedImage(UploadedFile $file, string $directory = 'images'): array
    {
        // Générer un nom unique pour l'image
        $filename = time() . '_' . uniqid() . '.webp';
        $originalFilename = $file->getClientOriginalName();
        
        // Lire l'image uploadée
        $image = $this->manager->read($file->getPathname());
        
        // Créer les différentes versions optimisées
        $results = [];
        
        // 1. Image originale optimisée (max 1920px pour TBI 4K)
        $optimized = $this->optimizeForTBI($image);
        $originalPath = $directory . '/original/' . $filename;
        Storage::disk('images')->put($originalPath, $optimized->toWebp(85));
        $results['original'] = $originalPath;
        
        // 2. Thumbnail pour listes (200x200px)
        $thumbnail = $this->createThumbnail($image, 200, 200);
        $thumbnailPath = $directory . '/thumbnails/' . $filename;
        Storage::disk('images')->put($thumbnailPath, $thumbnail->toWebp(80));
        $results['thumbnail'] = $thumbnailPath;
        
        // 3. Preview pour dialogues (400x400px)
        $preview = $this->createThumbnail($image, 400, 400);
        $previewPath = $directory . '/previews/' . $filename;
        Storage::disk('images')->put($previewPath, $preview->toWebp(85));
        $results['preview'] = $previewPath;
        
        // 4. Version mobile (800px max)
        $mobile = $this->optimizeForMobile($image);
        $mobilePath = $directory . '/mobile/' . $filename;
        Storage::disk('images')->put($mobilePath, $mobile->toWebp(80));
        $results['mobile'] = $mobilePath;
        
        return [
            'original_name' => $originalFilename,
            'filename' => $filename,
            'paths' => $results,
            'urls' => $this->generateUrls($results),
            'size' => $file->getSize(),
            'mime_type' => 'image/webp'
        ];
    }
    
    /**
     * Optimise l'image pour affichage TBI (haute résolution)
     */
    protected function optimizeForTBI($image)
    {
        // Redimensionner si trop grande (max 1920px pour TBI)
        if ($image->width() > 1920 || $image->height() > 1920) {
            $image = $image->scaleDown(1920, 1920);
        }
        
        // Améliorer la netteté pour écrans TBI
        $image = $image->sharpen(10);
        
        return $image;
    }
    
    /**
     * Optimise l'image pour mobile/tablette
     */
    protected function optimizeForMobile($image)
    {
        // Redimensionner pour mobile (max 800px)
        if ($image->width() > 800 || $image->height() > 800) {
            $image = $image->scaleDown(800, 800);
        }
        
        return $image;
    }
    
    /**
     * Crée un thumbnail carré avec crop intelligent
     */
    protected function createThumbnail($image, int $width, int $height)
    {
        return $image->cover($width, $height);
    }
    
    /**
     * Génère les URLs publiques pour toutes les versions
     */
    protected function generateUrls(array $paths): array
    {
        $urls = [];
        $baseUrl = config('app.url') . '/storage/images';
        
        foreach ($paths as $type => $path) {
            $urls[$type] = $baseUrl . '/' . $path;
        }
        return $urls;
    }
    
    /**
     * Supprime toutes les versions d'une image
     */
    public function deleteImageVersions(array $paths): bool
    {
        $success = true;
        foreach ($paths as $path) {
            if (Storage::disk('images')->exists($path)) {
                $success = Storage::disk('images')->delete($path) && $success;
            }
        }
        return $success;
    }
    
    /**
     * Convertit une image existante en WebP
     */
    public function convertToWebP(string $imagePath): string
    {
        $image = $this->manager->read(Storage::disk('images')->path($imagePath));
        $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $imagePath);
        
        Storage::disk('images')->put($webpPath, $image->toWebp(85));
        
        return $webpPath;
    }
    
    /**
     * Valide si le fichier est une image supportée
     */
    public function isValidImage(UploadedFile $file): bool
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB max
        
        return in_array($file->getMimeType(), $allowedMimes) && 
               $file->getSize() <= $maxSize;
    }
}
