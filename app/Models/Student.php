<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'name',
        'level',
        'age'
    ];

    protected $casts = [
        'level' => 'string',
        'age' => 'integer'
    ];

    /**
     * Relations avec les interactions de l'élève
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(StudentInteraction::class);
    }

    /**
     * Relations avec les sessions de lecture de l'élève
     */
    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    /**
     * Obtenir le nombre total de mots lus par l'élève
     */
    public function getTotalWordsReadAttribute(): int
    {
        return $this->interactions()->where('action_type', 'click')->count();
    }

    /**
     * Obtenir le temps total passé au tableau par l'élève
     */
    public function getTotalReadingTimeAttribute(): int
    {
        return $this->readingSessions()->whereNotNull('duration')->sum('duration');
    }
}
