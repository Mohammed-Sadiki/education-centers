<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'category_id',
        'domain',
        'school_year',
        'title',
        'amount',
        'currency',
        'expense_date',
        'payment_method',
        'supplier',
        'reference',
        'receipt_path',
        'description',
        'status',
        'recorded_by',
    ];

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
