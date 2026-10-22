<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Announcement extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * Fields whose displayed value can be localized.
     *
     * @var list<string>
     */
    protected array $translatable = ['title', 'content'];

    protected $fillable = [
        'center_id', 'author_id', 'title', 'slug', 'content',
        'image_path', 'is_public', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'published_at' => 'datetime'];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    protected static function booted(): void
    {
        static::saving(function (Announcement $a) {
            if (empty($a->slug)) {
                $a->slug = Str::slug($a->title).'-'.uniqid();
            }
        });
    }
}
