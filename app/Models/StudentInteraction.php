<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentInteraction extends Model
{
    protected $fillable = [
        'student_id',
        'text_id',
        'word_id',
        'action_type',
        'read_count',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_count' => 'integer'
    ];

    /**
     * Relation avec l'élève
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec le texte
     */
    public function text(): BelongsTo
    {
        return $this->belongsTo(Text::class);
    }

    /**
     * Relation avec le mot
     */
    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    /**
     * Vérifier si l'action est une aide
     */
    public function isHelpAction(): bool
    {
        return in_array($this->action_type, [
            'help_syllables',
            'help_letters',
            'help_image',
            'toggle_vocalization'
        ]);
    }

    /**
     * Vérifier si le mot est devenu usuel pour cet élève
     */
    public function isWordFamiliar(int $threshold = 3): bool
    {
        return $this->read_count >= $threshold;
    }
}
