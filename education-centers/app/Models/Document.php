<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id', 'student_id', 'type', 'title', 'description',
        'file_path', 'original_name', 'mime_type', 'size', 'uploaded_by', 'status',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'identity' => __('Identité'), 'diploma' => __('Diplôme'), 'certificate' => __('Certificat'),
            'attestation' => __('Attestation'), 'administrative' => __('Administratif'), 'other' => __('Autre'),
            default => ucfirst($this->type),
        };
    }

    public function getFormattedSizeAttribute(): string
    {
        if ($this->size < 1024) {
            return $this->size.' B';
        }
        if ($this->size < 1048576) {
            return round($this->size / 1024, 1).' KB';
        }

        return round($this->size / 1048576, 1).' MB';
    }

    /**
     * Le document peut-il être affiché/imprimé directement dans le navigateur ?
     */
    public function isPrintable(): bool
    {
        return in_array($this->mime_type, ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'], true);
    }
}
