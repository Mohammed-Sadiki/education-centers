<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;
    use \App\Concerns\HasDomainScope;
    use \App\Models\Concerns\HasContextScope;

    /**
     * Le domaine d'une séance est hérité de la classe puis de sa formation.
     */
    protected function domainPaths(): array
    {
        return ['classRoom.formation'];
    }

    protected $table = 'schedules';

    protected $fillable = [
        'center_id',
        'class_id',
        'teacher_id',
        'room_id',
        'date',
        'start_time',
        'end_time',
        'title',
        'type',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Relation vers le centre.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Relation vers la classe (ClassRoom).
     */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    /**
     * Relation vers le professeur.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relation vers la salle.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // --- Helpers de statut ---

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // --- Accessors utiles ---

    public function getFormattedDateAttribute(): string
    {
        return $this->date ? $this->date->format('d/m/Y') : '';
    }

    public function getFormattedTimeAttribute(): string
    {
        $start = substr($this->start_time, 0, 5);
        $end = substr($this->end_time, 0, 5);

        return "{$start} → {$end}";
    }

    public function getDurationMinutesAttribute(): int
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInMinutes($end);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'course' => __('Cours'),
            'exam' => __('Examen'),
            'workshop' => __('Atelier / TP'),
            'other' => __('Autre'),
            default => ucfirst($this->type),
        };
    }
}
