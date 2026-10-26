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
        'scheduled_payment_date',
        'payment_method',
        'reference',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'expected_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_date' => 'date',
            'scheduled_payment_date' => 'date',
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

    /**
     * Statut personnel vu par le professeur, calculé à partir des données réelles.
     *
     * - paid     : solde intégralement versé.
     * - overdue  : reste dû ET date prévue connue ET dépassée.
     * - partial  : un versement partiel a été effectué, sans retard avéré.
     * - pending  : rien n'est versé et aucune date prévue dépassée.
     *
     * On n'affiche JAMAIS « en retard » sans date de versement contractuelle.
     */
    public function getPersonalStatusAttribute(): string
    {
        $remaining = $this->remaining_amount;

        if ($remaining <= 0.01) {
            return 'paid';
        }

        $overdue = $this->scheduled_payment_date !== null
            && $this->scheduled_payment_date->isPast();

        if ($overdue) {
            return 'overdue';
        }

        return (float) $this->paid_amount > 0 ? 'partial' : 'pending';
    }

    public function getPersonalStatusLabelAttribute(): string
    {
        return match ($this->personal_status) {
            'paid' => __('Versé'),
            'partial' => __('Partiellement versé'),
            'overdue' => __('En retard'),
            default => __('En attente'),
        };
    }

    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => __('Janvier'), 2 => __('Février'), 3 => __('Mars'), 4 => __('Avril'),
            5 => __('Mai'), 6 => __('Juin'), 7 => __('Juillet'), 8 => __('Août'),
            9 => __('Septembre'), 10 => __('Octobre'), 11 => __('Novembre'), 12 => __('Décembre'),
        ];

        return $months[$this->month] ?? (string) $this->month;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => __('Payé'),
            'partial' => __('Partiellement payé'),
            'unpaid' => __('Non payé'),
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
            'partial' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
            'unpaid' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
            default => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
        };
    }
}
