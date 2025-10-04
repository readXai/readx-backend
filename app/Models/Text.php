<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Text extends Model
{
    protected $fillable = [
        'title',
        'content',
        'difficulty_level'
    ];

    protected $casts = [
        'difficulty_level' => 'string'
    ];

    /**
     * Relation many-to-many avec les mots via text_words
     */
    public function words(): BelongsToMany
    {
        return $this->belongsToMany(Word::class, 'text_words')
                    ->withPivot('position')
                    ->orderBy('text_words.position');
    }

    /**
     * Relations avec les interactions des élèves sur ce texte
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(StudentInteraction::class);
    }

    /**
     * Relations avec les sessions de lecture de ce texte
     */
    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    /**
     * Analyser le contenu du texte et extraire les mots
     */
    public function parseWordsFromContent(): array
    {
        // Séparer le texte arabe en mots (espaces et signes de ponctuation)
        $words = preg_split('/[\s\p{P}]+/u', $this->content, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter($words);
    }

    /**
     * Normaliser un mot arabe (supprimer ال et harakat optionnelles)
     */
    public static function normalizeArabicWord(string $word): string
    {
        // Supprimer l'article ال au début
        $normalized = preg_replace('/^ال/', '', $word);
        
        // Supprimer les harakat (voyelles) - caractères Unicode arabes diacritiques
        $harakat = ['ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ْ', 'ّ', 'ٰ', 'ٖ', 'ٗ', '٘ ', 'ٙ', 'ٚ', 'ٛ', 'ٜ', 'ٝ', 'ٞ', 'ٟ'];
        $normalized = str_replace($harakat, '', $normalized);
        
        return trim($normalized);
    }
}
