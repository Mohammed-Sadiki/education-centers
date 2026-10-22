<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CenterBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'school_year',
        'annual_allocated_budget',
    ];

    protected $casts = [
        'annual_allocated_budget' => 'decimal:2',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
