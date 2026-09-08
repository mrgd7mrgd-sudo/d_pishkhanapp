<?php

declare(strict_types=1);

namespace App\Providers;

use App\Shared\Crypto\EnvelopeEncryptor;
use App\Shared\Crypto\KeyRing;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(KeyRing::class, function () {
            return KeyRing::fromEnv();
        });

        $this->app->singleton(EnvelopeEncryptor::class, function ($app) {
            return new EnvelopeEncryptor($app->make(KeyRing::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
