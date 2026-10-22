<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'piece_number',
        'entry_date',
        'entry_type',
        'label',
        'third_party',
        'account_code',
        'debit',
        'credit',
        'cumulative_balance',
        'recorded_by',
    ];

    protected $casts = [
        'debit'              => 'decimal:2',
        'credit'             => 'decimal:2',
        'cumulative_balance' => 'decimal:2',
        'entry_date'         => 'date',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
