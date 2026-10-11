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
