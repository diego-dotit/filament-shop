<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Facades\Translatable;

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
        Translatable::fallback(
            fallbackLocale: config('translatable.fallback_locale'),
        );

        // Resolve factory names for domain models in App\Domains\*\Models\*
        Factory::guessFactoryNamesUsing(function (string $modelClass): string {
            $baseName = class_basename($modelClass);

            return "Database\\Factories\\{$baseName}Factory";
        });
    }
}
