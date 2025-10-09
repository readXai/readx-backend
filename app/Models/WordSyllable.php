<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordSyllable extends Model
{
    use HasFactory;

    protected $fillable = [
        'word_id',
        'syllable_text',
        'syllable_position',
        'syllable_type',
        'is_stressed'
    ];

    protected $casts = [
        'syllable_position' => 'integer',
        'is_stressed' => 'boolean'
    ];

    /**
     * Relation avec le mot
     */
    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    /**
     * Scope pour ordonner par position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('syllable_position');
    }

    /**
     * Scope pour les syllabes accentuées
     */
    public function scopeStressed($query)
    {
        return $query->where('is_stressed', true);
    }

    /**
     * Scope par type de syllabe
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('syllable_type', $type);
    }
}
