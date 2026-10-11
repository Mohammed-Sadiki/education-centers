<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
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
        'teacher_number',
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
        'specialization',
        'qualification',
        'hire_date',
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
            'hire_date'     => 'date',
        ];
    }

    /**
     * Get the user account for this teacher.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the educational center for this teacher.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Classes auxquelles ce professeur est affecté.
     */
    public function classes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_teacher', 'teacher_id', 'class_id')
            ->withTimestamps();
    }

    /**
     * Relation : Séances d'emploi du temps du professeur.
     */
    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Check if the teacher is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the teacher is inactive.
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
     * Generate a unique teacher number based on center code.
     * Format: {CENTER_CODE}-T-{000001}
     * Examples: BER-T-000001, MUN-T-000001
     */
    public static function generateTeacherNumber(Center $center): string
    {
        $code = strtoupper($center->code);
        $count = self::where('center_id', $center->id)->count() + 1;
        $teacherNumber = sprintf('%s-T-%06d', $code, $count);

        while (self::where('teacher_number', $teacherNumber)->exists()) {
            $count++;
            $teacherNumber = sprintf('%s-T-%06d', $code, $count);
        }

        return $teacherNumber;
    }
}
