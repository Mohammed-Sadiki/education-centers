<?php

namespace App\Providers;

use App\Models\AppNotification;
use App\Models\ClassRoom;
use App\Policies\ClassPolicy;
use App\Services\Translation\DeepLTranslationProvider;
use App\Services\Translation\GoogleTranslationProvider;
use App\Services\Translation\NullTranslationProvider;
use App\Services\Translation\TranslationProvider;
use App\Support\UserContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Contexte par défaut : vide (ni centre ni domaine restrictifs).
        // Le middleware ResolveDomainContext le remplace par instance liée
        // à la requête via app()->instance() dès qu'un utilisateur est connecté.
        $this->app->bind(UserContext::class, fn (): UserContext => new UserContext);

        $this->app->singleton(TranslationProvider::class, function (): TranslationProvider {
            $provider = (string) config('translation.provider', 'google');

            $instance = match ($provider) {
                'deepl' => new DeepLTranslationProvider(
                    config('translation.providers.deepl.key'),
                    (string) config('translation.providers.deepl.endpoint'),
                    (int) config('translation.providers.deepl.timeout', 8),
                ),
                'google' => new GoogleTranslationProvider(
                    config('translation.providers.google.key'),
                    (string) config('translation.providers.google.endpoint'),
                    (int) config('translation.providers.google.timeout', 8),
                ),
                default => new NullTranslationProvider,
            };

            return $instance->isConfigured() ? $instance : new NullTranslationProvider;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ClassRoom::class, ClassPolicy::class);

        Route::bind('notification', function (string $value) {
            return AppNotification::findOrFail($value);
        });
    }
}
