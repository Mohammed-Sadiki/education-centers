<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassRoom extends Model
{
    use HasFactory;

    /**
     * Nom de la table associée dans la base de données.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'center_id',
        'formation_id',
        'name',
        'code',
        'description',
        'capacity',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity'   => 'integer',
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    /**
     * Relation : Une classe appartient à un centre.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Relation : Une classe appartient à une formation.
     */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    /**
     * Relation : Professeurs affectés à cette classe.
     */
    public function teachers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'class_teacher', 'class_id', 'teacher_id')
            ->withTimestamps();
    }

    /**
     * Vérifier si un professeur est affecté à la classe.
     */
    public function hasTeacher(Teacher|int $teacher): bool
    {
        $teacherId = $teacher instanceof Teacher ? $teacher->id : $teacher;

        return $this->teachers()->where('teachers.id', $teacherId)->exists();
    }

    /**
     * Relation : Séances d'emploi du temps de la classe.
     */
    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    /**
     * Relation : Inscriptions affectées à cette classe.
     */
    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Attendance::class, 'class_id'); }
    public function evaluations(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Evaluation::class, 'class_id'); }

    /**
     * Compte des inscriptions acceptées occupant une place.
     */
    public function getAcceptedEnrollmentsCountAttribute(): int
    {
        return $this->enrollments()->where('status', Enrollment::STATUS_ACCEPTED)->count();
    }

    /**
     * Vérifier si la classe a atteint sa capacité maximale.
     */
    public function isFull(): bool
    {
        if (!$this->capacity) {
            return false; // Capacité illimitée
        }

        return $this->accepted_enrollments_count >= $this->capacity;
    }
    /**
     * Vérifier si la classe est active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si la classe est inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Accessor : libellé complet (ex: "A1-01 — Deutsch A1").
     */
    public function getFullLabelAttribute(): string
    {
        $formationName = $this->formation?->name ?? 'Formation';

        return "{$this->code} — {$formationName}";
    }

    /**
     * Accessor : période formatée.
     */
    public function getFormattedPeriodAttribute(): string
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->format('d/m/Y') . ' au ' . $this->end_date->format('d/m/Y');
        } elseif ($this->start_date) {
            return 'Dès le ' . $this->start_date->format('d/m/Y');
        } elseif ($this->end_date) {
            return 'Jusqu’au ' . $this->end_date->format('d/m/Y');
        }

        return 'Non définie';
    }
}
