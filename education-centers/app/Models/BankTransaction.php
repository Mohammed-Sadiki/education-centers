<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'transaction_date',
        'label',
        'origin',
        'method',
        'type',
        'amount',
        'running_balance',
        'reference',
        'receipt_path',
        'payment_id',
        'teacher_salary_id',
        'expense_id',
        'recorded_by',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'running_balance'  => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function teacherSalary(): BelongsTo
    {
        return $this->belongsTo(TeacherSalary::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
