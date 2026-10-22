<?php

namespace App\Services\Translation;

class NullTranslationProvider implements TranslationProvider
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function translate(string $text, string $source, string $target): ?string
    {
        return null;
    }

    public function name(): string
    {
        return 'null';
    }
}
