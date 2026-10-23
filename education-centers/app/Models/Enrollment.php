<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;
    use \App\Concerns\HasDomainScope;
    use \App\Models\Concerns\HasContextScope;

    /**
     * Le domaine d'une inscription est hérité de sa formation.
     */
    protected function domainPaths(): array
    {
        return ['formation'];
    }

    protected $table = 'enrollments';

    public const STATUS_PENDING = 'pending';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'center_id',
        'student_id',
        'formation_id',
        'class_id',
        'school_year',
        'registration_number',
        'application_date',
        'status',
        'notes',
        'decision_date',
        'decision_notes',
        'accepted_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'application_date' => 'date',
            'decision_date' => 'date',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Relation : Une inscription appartient à un centre.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Relation : Une inscription appartient à un étudiant.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation : Une inscription concerne une formation.
     */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    /**
     * Relation : Une inscription peut être affectée à une classe.
     */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    // --- Helpers de statut ---

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // --- Accessors utiles ---

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => __('En attente'),
            self::STATUS_UNDER_REVIEW => __('En examen'),
            self::STATUS_ACCEPTED => __('Acceptée'),
            self::STATUS_REJECTED => __('Refusée'),
            self::STATUS_CANCELLED => __('Annulée'),
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            self::STATUS_UNDER_REVIEW => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            self::STATUS_ACCEPTED => 'bg-success-subtle text-success border border-success-subtle',
            self::STATUS_REJECTED => 'bg-danger-subtle text-danger border border-danger-subtle',
            self::STATUS_CANCELLED => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            default => 'bg-light text-dark border',
        };
    }

    public function getFormattedApplicationDateAttribute(): string
    {
        return $this->application_date ? $this->application_date->format('d/m/Y') : '';
    }

    /**
     * Génère un numéro d'inscription unique et lisible par centre.
     * Format : {CENTER_CODE}-INS-{000001}
     * Exemples : BER-INS-000001, MUN-INS-000001
     */
    public static function generateRegistrationNumber(Center $center): string
    {
        $code = strtoupper($center->code);
        $prefix = "{$code}-INS-";

        // Trouve le numéro séquentiel le plus élevé pour ce préfixe
        $lastEnrollment = static::where('registration_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastEnrollment && preg_match('/'.preg_quote($prefix, '/').'(\d+)/', $lastEnrollment->registration_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = $prefix.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            $exists = static::where('registration_number', $candidate)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        return $candidate;
    }
}
