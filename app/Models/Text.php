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
        'level',
        'description'
    ];



    /**
     * Relation many-to-many avec les classes
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_text');
    }

    /**
     * Relation avec les mots de ce texte
     */
    public function words(): HasMany
    {
        return $this->hasMany(Word::class)->orderBy('position');
    }

    /**
     * Relation many-to-many avec les images
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Image::class, 'text_images');
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
     * Analyser le contenu du texte et créer les mots avec leurs unités
     */
    public function parseAndCreateWords(): void
    {
        // Supprimer les anciens mots
        $this->words()->delete();
        
        // Séparer le texte arabe en mots (espaces et signes de ponctuation)
        $wordTexts = preg_split('/[\s\p{P}]+/u', $this->content, -1, PREG_SPLIT_NO_EMPTY);
        $wordTexts = array_filter($wordTexts);
        
        // Créer les mots avec leurs unités par défaut
        foreach ($wordTexts as $position => $wordText) {
            $word = $this->words()->create([
                'word_text' => $wordText,
                'word_normalized' => self::normalizeArabicWord($wordText),
                'position' => $position,
                'is_compound' => false
            ]);
            
            // Créer une unité par défaut (mot simple)
            $word->units()->create([
                'unit_text' => $wordText,
                'unit_normalized' => self::normalizeArabicWord($wordText),
                'position' => 0
            ]);
        }
    }

    /**
     * Analyser le contenu du texte et extraire les mots (version simple)
     */
    public function parseWordsFromContent(): array
    {
        // Séparer le texte arabe en mots (espaces et signes de ponctuation)
        $words = preg_split('/[\s\p{P}]+/u', $this->content, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter($words);
    }

    /**
     * Obtenir toutes les unités de mots de ce texte
     */
    public function getAllWordUnits(): \Illuminate\Database\Eloquent\Collection
    {
        return WordUnit::whereHas('word', function ($query) {
            $query->where('text_id', $this->id);
        })->with(['word', 'root', 'images'])->get();
    }

    /**
     * Obtenir les statistiques du texte
     */
    public function getStatistics(): array
    {
        $wordsCount = $this->words()->count();
        $unitsCount = $this->getAllWordUnits()->count();
        $compoundWordsCount = $this->words()->where('is_compound', true)->count();
        $typedUnitsCount = $this->getAllWordUnits()->whereNotNull('linguistic_type')->count();
        $unitsWithRootsCount = $this->getAllWordUnits()->whereNotNull('root_id')->count();
        $unitsWithImagesCount = $this->getAllWordUnits()->whereHas('images')->count();
        
        return [
            'words_count' => $wordsCount,
            'units_count' => $unitsCount,
            'compound_words_count' => $compoundWordsCount,
            'simple_words_count' => $wordsCount - $compoundWordsCount,
            'typed_units_count' => $typedUnitsCount,
            'units_with_roots_count' => $unitsWithRootsCount,
            'units_with_images_count' => $unitsWithImagesCount,
            'completion_percentage' => $unitsCount > 0 ? round(($typedUnitsCount / $unitsCount) * 100, 2) : 0
        ];
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

    /**
     * Vérifier si le texte est prêt pour la lecture
     */
    public function isReadyForReading(): bool
    {
        $stats = $this->getStatistics();
        return $stats['completion_percentage'] >= 80; // Au moins 80% des unités typées
    }

    /**
     * Obtenir le niveau de difficulté basé sur les statistiques
     */
    public function getDifficultyLevel(): string
    {
        $stats = $this->getStatistics();
        $avgUnitsPerWord = $stats['words_count'] > 0 ? $stats['units_count'] / $stats['words_count'] : 1;
        
        if ($avgUnitsPerWord <= 1.2) {
            return 'Facile';
        } elseif ($avgUnitsPerWord <= 1.8) {
            return 'Moyen';
        } else {
            return 'Difficile';
        }
    }
}
