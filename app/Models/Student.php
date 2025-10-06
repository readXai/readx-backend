<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'name',
        'classroom_id'
    ];

    protected $casts = [
        'classroom_id' => 'integer'
    ];

    /**
     * Relation avec la classe de l'élève
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

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

    /**
     * Obtenir le niveau scolaire (CE1, CE2, CM1, CM2)
     */
    public function getSchoolLevelAttribute(): string
    {
        return $this->classroom->level->name;
    }

    /**
     * Obtenir la classe complète (CE1A, CE1B, CE2A, etc.)
     */
    public function getFullClassAttribute(): string
    {
        return $this->classroom->name;
    }

    /**
     * Obtenir la section de la classe (A, B, C, etc.)
     */
    public function getClassSectionAttribute(): string
    {
        return $this->classroom->section;
    }

    /**
     * Scope pour filtrer par niveau scolaire
     */
    public function scopeByLevel($query, string $levelName)
    {
        return $query->whereHas('classroom.level', function ($q) use ($levelName) {
            $q->where('name', $levelName);
        });
    }

    /**
     * Scope pour filtrer par classe complète
     */
    public function scopeByClassroom($query, int $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    /**
     * Scope pour filtrer par nom de classe
     */
    public function scopeByClassName($query, string $className)
    {
        return $query->whereHas('classroom', function ($q) use ($className) {
            $q->where('name', $className);
        });
    }
}
