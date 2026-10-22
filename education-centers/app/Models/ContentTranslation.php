<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentTranslation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'field',
        'value',
        'source',
    ];

    /**
     * The translated model (Formation, Announcement, Center...).
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Whether the value was written by an administrator.
     */
    public function isManual(): bool
    {
        return $this->source === 'manual';
    }
}
