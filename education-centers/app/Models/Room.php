<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
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
        'capacity',
        'floor',
        'building',
        'equipment',
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
            'capacity' => 'integer',
        ];
    }

    /**
     * Relation : Une salle appartient à un centre.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Relation : Séances programmées dans cette salle.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Vérifier si la salle est active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si la salle est inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Vérifier si la salle peut accueillir un certain nombre de personnes.
     */
    public function hasCapacity(int $requiredCapacity): bool
    {
        return $this->capacity >= $requiredCapacity;
    }

    /**
     * Accessor : libellé complet (ex: "Salle 01 — ROOM-01").
     */
    public function getFullLabelAttribute(): string
    {
        return "{$this->name} — {$this->code}";
    }

    /**
     * Accessor : localisation (bâtiment et étage).
     */
    public function getLocationAttribute(): string
    {
        $parts = [];
        if ($this->building) {
            $parts[] = __('Bâtiment :building', ['building' => $this->building]);
        }
        if ($this->floor !== null && $this->floor !== '') {
            $parts[] = __('Étage :floor', ['floor' => $this->floor]);
        }

        return ! empty($parts) ? implode(', ', $parts) : __('Non précisée');
    }
}
