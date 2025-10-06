<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $fillable = [
        'name'
    ];

    protected $casts = [
        'name' => 'string'
    ];

    /**
     * Relations avec les classes de ce niveau
     */
    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    /**
     * Relations avec les élèves de ce niveau (via les classes)
     */
    public function students()
    {
        return $this->hasManyThrough(Student::class, Classroom::class);
    }

    /**
     * Relations avec les textes adaptés à ce niveau
     */
    public function texts(): HasMany
    {
        return $this->hasMany(Text::class, 'difficulty_level', 'name');
    }



    /**
     * Obtenir le nombre total d'élèves dans ce niveau
     */
    public function getTotalStudentsAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Obtenir le nombre de classes dans ce niveau
     */
    public function getTotalClassroomsAttribute(): int
    {
        return $this->classrooms()->count();
    }

    /**
     * Obtenir le nombre de textes associés à ce niveau
     */
    public function getTextsCountAttribute(): int
    {
        // Compter les textes associés aux classes de ce niveau via la relation many-to-many
        return Text::whereHas('classrooms', function ($query) {
            $query->where('level_id', $this->id);
        })->count();
    }
}
