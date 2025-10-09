<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Word extends Model
{
    protected $fillable = [
        'text_id',
        'word_text',
        'word_normalized',
        'position',
        'is_compound'
    ];

    protected $casts = [
        'is_compound' => 'boolean',
        'position' => 'integer'
    ];

    /**
     * Relation avec le texte parent
     */
    public function text(): BelongsTo
    {
        return $this->belongsTo(Text::class);
    }

    /**
     * Relation avec les unités de ce mot
     */
    public function units(): HasMany
    {
        return $this->hasMany(WordUnit::class)->orderBy('position');
    }

    /**
     * Relation avec les syllabes de ce mot
     */
    public function syllables(): HasMany
    {
        return $this->hasMany(WordSyllable::class)->orderBy('syllable_position');
    }

    /**
     * Relation many-to-many avec les images (via les unités)
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Image::class, 'unit_images', 'word_unit_id', 'image_id')
                    ->join('word_units', 'word_units.id', '=', 'unit_images.word_unit_id')
                    ->where('word_units.word_id', $this->id);
    }

    /**
     * Relations avec les interactions des élèves sur ce mot
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(StudentInteraction::class);
    }

    /**
     * Générer automatiquement les syllabes pour ce mot
     */
    public function generateSyllables(): void
    {
        // Supprimer les anciennes syllabes
        $this->syllables()->delete();
        
        // Utiliser le service de découpage syllabique
        $syllableService = app(\App\Services\ArabicSyllableService::class);
        $syllables = $syllableService->generateSyllables($this->word_text);
        
        // Créer les nouvelles syllabes
        foreach ($syllables as $syllable) {
            $this->syllables()->create([
                'syllable_text' => $syllable['syllable_text'] ?? $syllable['text'],
                'syllable_position' => $syllable['position'],
                'syllable_type' => $syllable['syllable_type'] ?? $syllable['type'],
                'is_stressed' => false // Par défaut, peut être déterminé plus tard
            ]);
        }
    }

    /**
     * Obtenir les syllabes sous forme de texte concaténé
     */
    public function getSyllablesTextAttribute(): string
    {
        return $this->syllables->pluck('syllable_text')->implode('-');
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
                   ->with(['units', 'images'])
                   ->get();
    }

    /**
     * Décomposer automatiquement le mot en unités
     */
    public function decomposeIntoUnits(array $unitTexts): void
    {
        // Supprimer les anciennes unités
        $this->units()->delete();
        
        // Créer les nouvelles unités
        foreach ($unitTexts as $position => $unitText) {
            $this->units()->create([
                'unit_text' => $unitText,
                'unit_normalized' => Text::normalizeArabicWord($unitText),
                'position' => $position
            ]);
        }
        
        // Mettre à jour le statut composé
        $this->update(['is_compound' => count($unitTexts) > 1]);
    }

    /**
     * Fusionner des unités
     */
    public function mergeUnits(array $unitIds): WordUnit
    {
        $units = $this->units()->whereIn('id', $unitIds)->orderBy('position')->get();
        
        if ($units->count() < 2) {
            throw new \InvalidArgumentException('Au moins 2 unités sont nécessaires pour la fusion');
        }
        
        // Créer la nouvelle unité fusionnée
        $mergedText = $units->pluck('unit_text')->implode('');
        $firstPosition = $units->first()->position;
        
        $mergedUnit = $this->units()->create([
            'unit_text' => $mergedText,
            'unit_normalized' => Text::normalizeArabicWord($mergedText),
            'position' => $firstPosition
        ]);
        
        // Supprimer les anciennes unités
        $this->units()->whereIn('id', $unitIds)->delete();
        
        // Réorganiser les positions
        $this->reorganizeUnitPositions();
        
        return $mergedUnit;
    }

    /**
     * Réorganiser les positions des unités
     */
    private function reorganizeUnitPositions(): void
    {
        $units = $this->units()->orderBy('position')->get();
        
        foreach ($units as $index => $unit) {
            $unit->update(['position' => $index]);
        }
        
        // Mettre à jour le statut composé
        $this->update(['is_compound' => $units->count() > 1]);
    }

    /**
     * Obtenir les suggestions d'images pour ce mot
     */
    public function getImageSuggestions(): \Illuminate\Database\Eloquent\Collection
    {
        $similarWords = self::findSimilarWords($this->word_text);
        $imageIds = [];
        
        foreach ($similarWords as $similarWord) {
            foreach ($similarWord->images as $image) {
                $imageIds[] = $image->id;
            }
        }
        
        return \App\Models\Image::whereIn('id', array_unique($imageIds))->get();
    }

    /**
     * Vérifier si le mot a des images orphelines après fusion d'unités
     */
    public function hasOrphanedImages(): bool
    {
        return $this->units()->whereHas('images')->count() > 0;
    }

    /**
     * Obtenir toutes les images associées aux unités de ce mot
     */
    public function getAllUnitImages(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Image::whereHas('wordUnits', function ($query) {
            $query->where('word_id', $this->id);
        })->get();
    }
}
