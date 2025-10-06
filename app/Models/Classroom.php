<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = [
        'name',
        'level_id',
        'section',
        'description'
    ];

    protected $casts = [
        'name' => 'string',
        'level_id' => 'integer',
        'section' => 'string',
        'description' => 'string'
    ];

    /**
     * Relation avec le niveau scolaire
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Relations avec les élèves de cette classe
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Relation many-to-many avec les textes
     */
    public function texts(): BelongsToMany
    {
        return $this->belongsToMany(Text::class, 'classroom_text');
    }

    /**
     * Obtenir le nom complet de la classe (ex: CE1A)
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Obtenir le niveau scolaire (ex: CE1)
     */
    public function getLevelNameAttribute(): string
    {
        return $this->level->name;
    }

    /**
     * Scope pour filtrer par niveau
     */
    public function scopeByLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    /**
     * Scope pour ordonner par niveau puis par section
     */
    public function scopeOrdered($query)
    {
        return $query->leftJoin('levels', 'classrooms.level_id', '=', 'levels.id')
                    ->orderBy('levels.name')
                    ->orderBy('classrooms.section')
                    ->select('classrooms.*');
    }

    /**
     * Obtenir le nombre d'élèves dans cette classe
     */
    public function getTotalStudentsAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Obtenir le temps total de lecture de tous les élèves de la classe
     */
    public function getTotalReadingTimeAttribute(): int
    {
        return $this->students()->get()->sum('total_reading_time');
    }

    /**
     * Obtenir le nombre total de mots lus par tous les élèves de la classe
     */
    public function getTotalWordsReadAttribute(): int
    {
        return $this->students()->get()->sum('total_words_read');
    }
}
