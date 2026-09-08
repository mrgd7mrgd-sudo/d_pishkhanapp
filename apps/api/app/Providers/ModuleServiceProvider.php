<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /** @var list<string> $modules */
        $modules = config('modules.modules', []);

        foreach ($modules as $module) {
            $providerClass = "App\\Modules\\{$module}\\{$module}ServiceProvider";
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** @var list<string> $modules */
        $modules = config('modules.modules', []);

        foreach ($modules as $module) {
            $moduleDir = app_path("Modules/{$module}");

            // 1. Load routes if routes.php exists
            $routesPath = "{$moduleDir}/routes.php";
            if (file_exists($routesPath)) {
                Route::middleware('api')
                    ->prefix('api/v1')
                    ->group($routesPath);
            }

            // 2. Load migrations from Database/Migrations
            $migrationsPath = "{$moduleDir}/Database/Migrations";
            if (is_dir($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }

            // 3. Load translations from Resources/lang if present
            $langPath = "{$moduleDir}/Resources/lang";
            if (is_dir($langPath)) {
                $this->loadTranslationsFrom($langPath, strtolower($module));
            }
        }
    }
}
