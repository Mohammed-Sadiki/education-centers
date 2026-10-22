<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * Fields whose displayed value can be localized.
     *
     * @var list<string>
     */
    protected array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'code',
        'color',
        'description',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
