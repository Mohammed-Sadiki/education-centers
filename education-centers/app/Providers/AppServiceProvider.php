<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(\App\Models\ClassRoom::class, \App\Policies\ClassPolicy::class);

        \Illuminate\Support\Facades\Route::bind('notification', function (string $value) {
            return \App\Models\AppNotification::findOrFail($value);
        });
    }

}
