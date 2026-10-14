<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StagiaireDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'stagiaire_id',
        'title',
        'type',
        'file_path',
        'file_name',
    ];

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(Stagiaire::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'certificat'  => 'Certificat',
            'attestation' => 'Attestation',
            'convention'  => 'Convention de stage',
            'cv'          => 'Curriculum Vitae',
            default       => 'Document administratif',
        };
    }
}
