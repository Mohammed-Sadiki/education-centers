<?php

namespace Tests\Feature;

use App\Concerns\HasTranslations;
use App\Models\Center;
use App\Models\Formation;
use App\Models\Message;
use App\Services\Translation\TranslationProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_locale_returns_the_original_content(): void
    {
        $formation = $this->formation(['name' => 'Informatique appliquée']);

        $this->app->setLocale('fr');

        $this->assertSame('Informatique appliquée', $formation->translated('name'));
    }

    public function test_content_falls_back_to_french_when_no_provider_is_configured(): void
    {
        $formation = $this->formation(['name' => 'Gestion hôtelière']);

        $this->app->setLocale('ar');

        $this->assertSame('Gestion hôtelière', $formation->translated('name'));
        $this->assertDatabaseMissing('content_translations', [
            'translatable_id' => $formation->id,
            'field' => 'name',
            'locale' => 'ar',
        ]);
    }

    public function test_machine_translation_is_generated_stored_and_reused(): void
    {
        $provider = new FakeTranslationProvider;
        $this->app->instance(TranslationProvider::class, $provider);
        $this->app->setLocale('ar');

        $formation = $this->formation(['name' => 'Développement web']);

        $this->assertSame('AR:Développement web', $formation->translated('name'));
        $this->assertSame(1, $provider->calls);

        $this->assertDatabaseHas('content_translations', [
            'translatable_id' => $formation->id,
            'translatable_type' => $formation->getMorphClass(),
            'field' => 'name',
            'locale' => 'ar',
            'value' => 'AR:Développement web',
            'source' => 'machine',
        ]);

        // A second read uses the stored translation, not the provider.
        $this->assertSame('AR:Développement web', $formation->translated('name'));
        $this->assertSame(1, $provider->calls);
    }

    public function test_manual_translation_overrides_machine_translation(): void
    {
        $provider = new FakeTranslationProvider;
        $this->app->instance(TranslationProvider::class, $provider);
        $this->app->setLocale('ar');

        $formation = $this->formation(['name' => 'Comptabilité']);

        $this->assertSame('AR:Comptabilité', $formation->translated('name'));

        $formation->setTranslation('name', 'المحاسبة', 'ar', 'manual');

        $this->assertSame('المحاسبة', $formation->translated('name'));
        $this->assertSame('manual', $formation->translationFor('name', 'ar')?->source);

        $this->app->setLocale('fr');
        $this->assertSame('Comptabilité', $formation->translated('name'));
    }

    public function test_translation_is_invalidated_only_by_cache_and_keeps_original_data(): void
    {
        $provider = new FakeTranslationProvider;
        $this->app->instance(TranslationProvider::class, $provider);
        $this->app->setLocale('ar');

        $formation = $this->formation(['name' => 'Marketing digital']);
        $formation->translated('name');

        $formation->update(['name' => 'Marketing numérique']);

        // Stored translations are intentional overrides: the original column
        // changes, but the translated value remains available.
        $this->assertSame('AR:Marketing digital', $formation->translated('name'));
        $this->assertSame('Marketing numérique', $formation->getRawOriginal('name'));
    }

    public function test_private_messages_do_not_expose_the_translation_trait(): void
    {
        $this->assertFalse(method_exists(new Message, 'translated'));
        $this->assertNotContains(HasTranslations::class, class_uses_recursive(Message::class));
    }

    public function test_untranslatable_fields_fall_back_to_the_original_value(): void
    {
        $formation = $this->formation(['code' => 'WEB-101']);
        $this->app->setLocale('ar');

        $this->assertSame('WEB-101', $formation->translated('code'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function formation(array $attributes = []): Formation
    {
        $center = Center::factory()->create();

        return Formation::create(array_merge([
            'center_id' => $center->id,
            'name' => 'Formation test',
            'code' => 'TEST-'.uniqid(),
            'description' => 'Description de test',
            'duration_hours' => 40,
            'duration_weeks' => 4,
            'price' => 1000,
            'currency' => 'MAD',
            'status' => 'active',
        ], $attributes));
    }
}

class FakeTranslationProvider implements TranslationProvider
{
    public int $calls = 0;

    public function isConfigured(): bool
    {
        return true;
    }

    public function translate(string $text, string $source, string $target): ?string
    {
        $this->calls++;

        return 'AR:'.$text;
    }

    public function name(): string
    {
        return 'fake';
    }
}
