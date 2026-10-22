<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepLTranslationProvider implements TranslationProvider
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

        // DeepL expects uppercase language codes and uses EN-US style tags.
        $targetCode = strtoupper($target);
        if ($targetCode === 'AR') {
            $targetCode = 'AR';
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$this->apiKey])
                ->asForm()
                ->post($this->endpoint, [
                    'text' => $text,
                    'source_lang' => strtoupper($source),
                    'target_lang' => $targetCode,
                ]);

            if (! $response->successful()) {
                Log::warning('DeepL translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $value = $response->json('translations.0.text');

            return is_string($value) && $value !== '' ? $value : null;
        } catch (\Throwable $exception) {
            Log::warning('DeepL translation exception', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    public function name(): string
    {
        return 'deepl';
    }
}
