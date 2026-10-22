<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTranslationProvider implements TranslationProvider
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $endpoint,
        private readonly int $timeout = 8,
    ) {}

    public function isConfigured(): bool
    {
        return is_string($this->apiKey) && $this->apiKey !== '';
    }

    public function translate(string $text, string $source, string $target): ?string
    {
        if (! $this->isConfigured() || trim($text) === '') {
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)->get($this->endpoint, [
                'key' => $this->apiKey,
                'q' => $text,
                'source' => $source,
                'target' => $target,
                'format' => 'text',
            ]);

            if (! $response->successful()) {
                Log::warning('Google translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            /** @var array<int, array<string, string>> $translations */
            $translations = $response->json('data.translations', []);
            $value = $translations[0]['translatedText'] ?? null;

            return is_string($value) && $value !== '' ? html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
        } catch (\Throwable $exception) {
            Log::warning('Google translation exception', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    public function name(): string
    {
        return 'google';
    }
}
