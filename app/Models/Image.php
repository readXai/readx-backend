<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Image extends Model
{
    protected $fillable = [
        'image_path',
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
     * Obtenir l'URL complète de l'image
     */
    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }

    /**
     * Vérifier si l'image est utilisée par au moins un mot
     */
    public function isUsed(): bool
    {
        return $this->words()->exists();
    }
}
