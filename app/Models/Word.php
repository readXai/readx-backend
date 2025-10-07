<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Word extends Model
{
    protected $fillable = [
        'word',
        'word_normalized'
    ];

    /**
     * Relation many-to-many avec les images
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Image::class, 'word_images');
    }

    /**
     * Relation many-to-many avec les textes via text_words
     */
    public function texts(): BelongsToMany
    {
        return $this->belongsToMany(Text::class, 'text_words')
                    ->withPivot('position');
    }

    /**
     * Relations avec les interactions des élèves sur ce mot
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(StudentInteraction::class);
    }

    /**
     * Trouver des mots similaires basés sur la normalisation
     */
    public static function findSimilarWords(string $word): \Illuminate\Database\Eloquent\Collection
    {
        $normalized = Text::normalizeArabicWord($word);
        
        return self::where('word_normalized', 'LIKE', '%' . $normalized . '%')
                   ->orWhere('word_normalized', 'LIKE', $normalized . '%')
                   ->orWhere('word_normalized', 'LIKE', '%' . $normalized)
                   ->with('images')
                   ->get();
    }

    /**
     * Obtenir les suggestions d'images pour ce mot
     */
    public function getImageSuggestions(): \Illuminate\Database\Eloquent\Collection
    {
        $similarWords = self::findSimilarWords($this->word);
        $imageIds = [];
        
        foreach ($similarWords as $similarWord) {
            foreach ($similarWord->images as $image) {
                $imageIds[] = $image->id;
            }
        }
        
        return \App\Models\Image::whereIn('id', array_unique($imageIds))->get();
    }
}
