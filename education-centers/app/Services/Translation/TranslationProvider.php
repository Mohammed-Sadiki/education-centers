<?php

namespace App\Services\Translation;

interface TranslationProvider
{
    /**
     * Whether the provider has the credentials required to run.
     */
    public function isConfigured(): bool;

    /**
     * Translate a single string. Returns null when translation is impossible.
     */
    public function translate(string $text, string $source, string $target): ?string;

    /**
     * Human readable provider name.
     */
    public function name(): string;
}
