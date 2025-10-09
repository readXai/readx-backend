<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WordUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'word_id',
        'root_id',
        'unit_text',
        'unit_normalized',
        'linguistic_type',
        'position',
    ];

    protected $casts = [
        'position' => 'integer'
    ];

    /**
     * Types linguistiques disponibles
     */
    public const LINGUISTIC_TYPES = [
        'اسم' => 'اسم', // Nom
        'فعل' => 'فعل', // Verbe
        'حرف' => 'حرف', // Particule
    ];

    /**
     * Relation avec le mot parent
     */
    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    /**
     * Relation avec la racine
     */
    public function root(): BelongsTo
    {
        return $this->belongsTo(Root::class);
    }

    /**
     * Relation many-to-many avec les images
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Image::class, 'unit_images');
    }

    /**
     * Scope pour filtrer par type linguistique
     */
    public function scopeByLinguisticType($query, $type)
    {
        return $query->where('linguistic_type', $type);
    }

    /**
     * Scope pour ordonner par position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Assigner une racine à cette unité
     */
    public function assignRoot(Root $root): void
    {
        $this->update(['root_id' => $root->id]);
    }

    /**
     * Assigner un type linguistique à cette unité
     */
    public function assignLinguisticType(string $type): void
    {
        if (!array_key_exists($type, self::LINGUISTIC_TYPES)) {
            throw new \InvalidArgumentException("Type linguistique invalide: {$type}");
        }
        
        $this->update(['linguistic_type' => $type]);
    }

    /**
     * Associer des images à cette unité
     */
    public function associateImages(array $imageIds): void
    {
        $this->images()->sync($imageIds);
    }

    /**
     * Dissocier toutes les images de cette unité
     */
    public function dissociateAllImages(): void
    {
        $this->images()->detach();
    }

    /**
     * Obtenir le texte formaté avec le type linguistique
     */
    public function getFormattedTextAttribute(): string
    {
        $text = $this->unit_text;
        
        if ($this->linguistic_type) {
            $text .= " ({$this->linguistic_type})";
        }
        
        if ($this->root) {
            $text .= " [جذر: {$this->root->root_text}]";
        }
        
        return $text;
    }

    /**
     * Vérifier si l'unité a des images associées
     */
    public function hasImages(): bool
    {
        return $this->images()->count() > 0;
    }
}
