<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Formation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'center_id',
        'name',
        'code',
        'description',
        'duration_hours',
        'duration_weeks',
        'price',
        'currency',
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
            'duration_hours' => 'integer',
            'duration_weeks' => 'integer',
            'price'          => 'decimal:2',
        ];
    }

    /**
     * Relation : Une formation appartient à un centre.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Relation : Une formation possède plusieurs classes.
     */
    public function classes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    /**
     * Relation : Inscriptions liées à cette formation.
     */
    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Enrollment::class);
    }


    /**
     * Vérifier si la formation est active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si la formation est inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Accessor : formatage du prix avec devise.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price === null) {
            return '—';
        }

        return number_format((float) $this->price, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Accessor : formatage de la durée.
     */
    public function getFormattedDurationAttribute(): string
    {
        $parts = [];
        if ($this->duration_weeks) {
            $parts[] = "{$this->duration_weeks} sem.";
        }
        if ($this->duration_hours) {
            $parts[] = "{$this->duration_hours} h";
        }

        return ! empty($parts) ? implode(' / ', $parts) : '—';
    }
}
