<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Image extends Model
{
    protected $fillable = [
        'image_path',
        'thumbnail_path',
        'preview_path',
        'mobile_path',
        'original_name',
        'filename',
        'file_size',
        'mime_type',
        'description'
    ];

    /**
     * Relation many-to-many avec les mots
     */
    public function words(): BelongsToMany
    {
        return $this->belongsToMany(Word::class, 'word_images');
    }

    /**
     * Obtenir l'URL complète de l'image originale
     */
    public function getImageUrlAttribute(): string
    {
        return config('app.url') . '/storage/images/' . $this->image_path;
    }
    
    /**
     * Obtenir l'URL du thumbnail
     */
    public function getThumbnailUrlAttribute(): string
    {
        return config('app.url') . '/storage/images/' . $this->thumbnail_path;
    }
    
    /**
     * Obtenir l'URL de la preview
     */
    public function getPreviewUrlAttribute(): string
    {
        return config('app.url') . '/storage/images/' . $this->preview_path;
    }
    
    /**
     * Obtenir l'URL de la version mobile
     */
    public function getMobileUrlAttribute(): string
    {
        return config('app.url') . '/storage/images/' . $this->mobile_path;
    }
    
    /**
     * Obtenir toutes les URLs des versions
     */
    public function getAllUrlsAttribute(): array
    {
        return [
            'original' => $this->image_url,
            'thumbnail' => $this->thumbnail_url,
            'preview' => $this->preview_url,
            'mobile' => $this->mobile_url
        ];
    }

    /**
     * Vérifier si l'image est utilisée par au moins un mot
     */
    public function isUsed(): bool
    {
        return $this->words()->exists();
    }
}
