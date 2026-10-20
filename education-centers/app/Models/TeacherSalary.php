<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalary extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'teacher_id',
        'domain',
        'month',
        'year',
        'school_year',
        'expected_amount',
        'paid_amount',
        'currency',
        'status',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'month'           => 'integer',
            'year'            => 'integer',
            'expected_amount' => 'decimal:2',
            'paid_amount'     => 'decimal:2',
            'payment_date'    => 'date',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->expected_amount - (float) $this->paid_amount);
    }

    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return $months[$this->month] ?? (string) $this->month;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid'    => 'Payé',
            'partial' => 'Partiellement payé',
            'unpaid'  => 'Non payé',
            default   => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'paid'    => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
            'partial' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
            'unpaid'  => 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
            default   => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
        };
    }
}
