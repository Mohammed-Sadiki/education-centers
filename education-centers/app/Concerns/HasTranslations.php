<?php

namespace App\Concerns;

use App\Models\ContentTranslation;
use App\Services\ContentTranslationService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTranslations
{
    /**
     * Register cache invalidation hooks.
     */
    public static function bootHasTranslations(): void
    {
        static::saved(function (self $model): void {
            app(ContentTranslationService::class)->flush($model);
        });

        static::deleted(function (self $model): void {
            app(ContentTranslationService::class)->flush($model);
            $model->contentTranslations()->delete();
        });
    }

    /**
     * Stored translations of this model.
     */
    public function contentTranslations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    /**
     * Fields that may be translated.
     *
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }

    /**
     * Return the localized value of a field, falling back to the original.
     */
    public function translated(string $field, ?string $locale = null): ?string
    {
        if (! in_array($field, $this->translatableFields(), true)) {
            $value = $this->getAttribute($field);

            return $value === null ? null : (string) $value;
        }

        return app(ContentTranslationService::class)->resolve($this, $field, $locale);
    }

    /**
     * Store a translation for a field.
     */
    public function setTranslation(string $field, string $value, string $locale, string $source = 'manual'): ContentTranslation
    {
        return app(ContentTranslationService::class)->store($this, $field, $locale, $value, $source);
    }

    /**
     * Return the stored translation model for a field, if any.
     */
    public function translationFor(string $field, string $locale): ?ContentTranslation
    {
        if ($this->relationLoaded('contentTranslations')) {
            return $this->contentTranslations
                ->first(fn (ContentTranslation $translation): bool => $translation->field === $field && $translation->locale === $locale);
        }

        return $this->contentTranslations()
            ->where('field', $field)
            ->where('locale', $locale)
            ->first();
    }
}
