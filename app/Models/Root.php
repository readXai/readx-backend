<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Root extends Model
{
    use HasFactory;

    protected $fillable = [
        'root_text',
        'root_normalized',
        'description',
    ];

    /**
     * Relation avec les unités de mots
     */
    public function wordUnits(): HasMany
    {
        return $this->hasMany(WordUnit::class);
    }

    /**
     * Scope pour rechercher par texte de racine
     */
    public function scopeByRootText($query, $rootText)
    {
        return $query->where('root_text', 'like', "%{$rootText}%");
    }
}
