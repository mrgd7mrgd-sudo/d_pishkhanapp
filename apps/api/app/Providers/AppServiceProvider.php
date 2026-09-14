<?php

declare(strict_types=1);

namespace App\Providers;

use App\Integration\Government\CivilRegistryClient;
use App\Integration\Government\Drivers\Simulator\SimulatorCivilRegistryClient;
use App\Integration\Government\Drivers\Simulator\SimulatorIdentityVerifier;
use App\Integration\Government\Drivers\Simulator\SimulatorPostalClient;
use App\Integration\Government\IdentityVerifier;
use App\Integration\Government\PostalClient;
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

        $this->app->singleton(IdentityVerifier::class, SimulatorIdentityVerifier::class);
        $this->app->singleton(CivilRegistryClient::class, SimulatorCivilRegistryClient::class);
        $this->app->singleton(PostalClient::class, SimulatorPostalClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
