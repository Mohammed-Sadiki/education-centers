<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id', 'student_id', 'class_id', 'schedule_id', 'enrollment_id',
        'date', 'status', 'arrival_time', 'late_minutes', 'justification',
        'comment', 'justification_status', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isPresent(): bool
    {
        return $this->status === 'present';
    }

    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }

    public function isLate(): bool
    {
        return $this->status === 'late';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'present' => __('Présent'), 'absent' => __('Absent'), 'late' => __('Retard'),
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'present' => 'bg-success-subtle text-success border border-success-subtle',
            'absent' => 'bg-danger-subtle text-danger border border-danger-subtle',
            'late' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'bg-secondary-subtle text-secondary border',
        };
    }
}
