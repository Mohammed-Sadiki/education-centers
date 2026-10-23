<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;
    use \App\Concerns\HasDomainScope;
    use \App\Models\Concerns\HasContextScope;

    /**
     * Le domaine d'un certificat dérive de sa formation.
     */
    protected function domainPaths(): array
    {
        return ['formation'];
    }

    protected $fillable = [
        'center_id', 'student_id', 'enrollment_id', 'formation_id',
        'certificate_number', 'type', 'title', 'issued_date', 'valid_until',
        'file_path', 'verify_token', 'status', 'issued_by',
    ];

    protected function casts(): array
    {
        return ['issued_date' => 'date', 'valid_until' => 'date'];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'enrollment' => __('Attestation d’inscription'),
            'completion' => __('Certificat de formation'),
            'attendance' => __('Attestation de présence'),
            'grades_report' => __('Relevé de notes'),
            'receipt' => __('Reçu de paiement'),
            'other' => __('Autre'),
            default => ucfirst($this->type),
        };
    }

    public static function generateCertificateNumber(Center $center, string $type = 'CERT'): string
    {
        $prefix = strtoupper($type).'-'.strtoupper($center->code).'-';
        $last = static::where('certificate_number', 'LIKE', "{$prefix}%")->orderBy('id', 'desc')->first();
        $next = $last ? ((int) substr($last->certificate_number, -6)) + 1 : 1;

        return $prefix.str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    public static function generateVerifyToken(): string
    {
        return Str::random(64);
    }
}
