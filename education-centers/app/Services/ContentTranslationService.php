<?php

namespace App\Services;

use App\Models\ContentTranslation;
use App\Services\Translation\TranslationProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ContentTranslationService
{
    public function __construct(
        private readonly TranslationProvider $provider,
    ) {}

    /**
     * Whether a translation provider is configured.
     */
    public function isAvailable(): bool
    {
        return $this->provider->isConfigured();
    }

    /**
     * Name of the configured provider.
     */
    public function providerName(): string
    {
        return $this->provider->name();
    }

    /**
     * Resolve a translated attribute for a model.
     *
     * Returns the original value when the requested locale is the source
     * locale, when no translation exists, or when translation fails.
     */
    public function resolve(Model $model, string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $sourceLocale = (string) config('translation.source_locale', 'fr');
        $original = $model->getAttribute($field);

        if ($original === null || $original === '') {
            return $original;
        }

        if ($locale === $sourceLocale) {
            return (string) $original;
        }

        $value = $this->cachedValue($model, $field, $locale);

        if ($value !== null) {
            return $value;
        }

        if (! config('translation.auto_translate', true) || ! $this->provider->isConfigured()) {
            return (string) $original;
        }

        $translated = $this->translateText((string) $original, $sourceLocale, $locale);

        if ($translated === null) {
            return (string) $original;
        }

        $this->store($model, $field, $locale, $translated, 'machine');

        return $translated;
    }

    /**
     * Translate a raw string with placeholder and markup protection.
     */
    public function translateText(string $text, string $source, string $target): ?string
    {
        if (trim($text) === '') {
            return null;
        }

        [$masked, $tokens] = $this->mask((string) $text);
        $translated = $this->provider->translate($masked, $source, $target);

        if ($translated === null) {
            return null;
        }

        return $this->unmask($translated, $tokens);
    }

    /**
     * Persist a translation (manual or machine).
     */
    public function store(Model $model, string $field, string $locale, string $value, string $source = 'manual'): ContentTranslation
    {
        /** @var ContentTranslation $translation */
        $translation = $model->contentTranslations()->updateOrCreate(
            ['locale' => $locale, 'field' => $field],
            ['value' => $value, 'source' => $source],
        );

        $this->forget($model, $field, $locale);

        return $translation;
    }

    /**
     * Remove a stored translation.
     */
    public function delete(Model $model, string $field, string $locale): void
    {
        $model->contentTranslations()
            ->where('locale', $locale)
            ->where('field', $field)
            ->delete();

        $this->forget($model, $field, $locale);
    }

    /**
     * Forget the cached value of one field.
     */
    public function forget(Model $model, string $field, string $locale): void
    {
        Cache::forget($this->cacheKey($model, $field, $locale));
    }

    /**
     * Forget every cached field of a model (called when content changes).
     */
    public function flush(Model $model): void
    {
        foreach ($model->translatableFields() as $field) {
            foreach ((array) config('translation.target_locales', ['ar']) as $locale) {
                $this->forget($model, (string) $field, (string) $locale);
            }
        }
    }

    /**
     * Read a stored translation value from cache or database.
     */
    private function cachedValue(Model $model, string $field, string $locale): ?string
    {
        $key = $this->cacheKey($model, $field, $locale);
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached['value'] ?? null;
        }

        $value = $model->contentTranslations()
            ->where('locale', $locale)
            ->where('field', $field)
            ->value('value');

        Cache::put($key, ['value' => $value], (int) config('translation.cache_ttl', 86400));

        return $value;
    }

    private function cacheKey(Model $model, string $field, string $locale): string
    {
        return implode(':', [
            (string) config('translation.cache_prefix', 'content-translation'),
            str_replace('\\', '_', $model->getMorphClass()),
            $model->getKey(),
            $locale,
            $field,
        ]);
    }

    /**
     * Mask placeholders, URLs and HTML tags so providers keep them intact.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function mask(string $text): array
    {
        $tokens = [];
        $index = 0;

        $masked = preg_replace_callback(
            '/<[^>]+>|:[A-Za-z_][A-Za-z0-9_]*|%\\d?\\$?[sd]|https?:\\/\\/\\S+/u',
            function (array $matches) use (&$tokens, &$index): string {
                $token = '__I18N_TOKEN_'.$index.'__';
                $tokens[$token] = $matches[0];
                $index++;

                return $token;
            },
            $text,
        );

        if ($masked === null) {
            Log::warning('Unable to mask translation placeholders');

            return [$text, []];
        }

        return [$masked, $tokens];
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function unmask(string $text, array $tokens): string
    {
        return strtr($text, $tokens);
    }
}
