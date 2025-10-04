<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ReadingSession extends Model
{
    protected $fillable = [
        'student_id',
        'text_id',
        'start_time',
        'end_time',
        'duration',
        'words_read',
        'help_requested'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
        'words_read' => 'integer',
        'help_requested' => 'integer'
    ];

    /**
     * Relation avec l'élève
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec le texte (optionnelle)
     */
    public function text(): BelongsTo
    {
        return $this->belongsTo(Text::class);
    }

    /**
     * Démarrer une nouvelle session de lecture
     */
    public static function startSession(int $studentId, ?int $textId = null): self
    {
        return self::create([
            'student_id' => $studentId,
            'text_id' => $textId,
            'start_time' => Carbon::now(),
            'words_read' => 0,
            'help_requested' => 0
        ]);
    }

    /**
     * Terminer la session de lecture
     */
    public function endSession(): void
    {
        $this->end_time = Carbon::now();
        $this->duration = $this->start_time->diffInSeconds($this->end_time);
        $this->save();
    }

    /**
     * Vérifier si la session est active
     */
    public function isActive(): bool
    {
        return is_null($this->end_time);
    }

    /**
     * Obtenir la durée formatée (MM:SS)
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return '00:00';
        }
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
