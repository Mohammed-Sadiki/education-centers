<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'center_id',
        'student_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'place_of_birth',
        'gender',
        'nationality',
        'address',
        'postal_code',
        'city',
        'country',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'notes',
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
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Get the user account for this student.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the educational center for this student.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Inscriptions de l'étudiant aux formations/classes.
     */
    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Attendance::class); }
    public function grades(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Grade::class); }
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Payment::class); }
    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Invoice::class); }
    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Document::class); }
    public function certificates(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Certificate::class); }

    /**
     * Check if the student is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the student is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Accessor for full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Generate a unique student number based on the center code.
     * Format: {CENTER_CODE}-{000001}
     */
    public static function generateStudentNumber(Center $center): string
    {
        $code = strtoupper($center->code);
        $count = self::where('center_id', $center->id)->count() + 1;
        $studentNumber = sprintf('%s-%06d', $code, $count);

        // Guarantee uniqueness in case of deletions
        while (self::where('student_number', $studentNumber)->exists()) {
            $count++;
            $studentNumber = sprintf('%s-%06d', $code, $count);
        }

        return $studentNumber;
    }
}
