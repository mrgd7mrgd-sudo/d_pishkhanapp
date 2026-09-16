<?php

declare(strict_types=1);

namespace App\Providers;

use App\Integration\Ai\AiProvider;
use App\Integration\Ai\Drivers\FakeDriver as AiFakeDriver;
use App\Integration\Ai\Drivers\OpenRouterDriver;
use App\Integration\Government\CivilRegistryClient;
use App\Integration\Government\Drivers\HttpDriver;
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
        $this->app->singleton(PostalClient::class, function () {
            $driver = env('POST_DRIVER', config('services.postal.driver', 'simulator'));

            return match (strtolower((string) $driver)) {
                'http' => new HttpDriver(
                    baseUrl: (string) config('services.postal.base_url', 'https://api.post.ir'),
                    apiKey: (string) config('services.postal.api_key', 'test_key')
                ),
                default => new SimulatorPostalClient,
            };
        });

        $this->app->singleton(AiProvider::class, function () {
            $driver = env('AI_DRIVER', config('pishkhan.ai.driver', 'fake'));

            if (strtolower((string) $driver) === 'openrouter') {
                return new OpenRouterDriver(
                    proxyUrl: (string) config('pishkhan.ai.proxy_url', 'https://127.0.0.1:8443'),
                    modelMap: (array) config('pishkhan.ai.models', []),
                    clientCertPath: config('pishkhan.ai.client_cert'),
                    clientKeyPath: config('pishkhan.ai.client_key'),
                    caCertPath: config('pishkhan.ai.ca_cert'),
                    timeoutSeconds: (int) config('pishkhan.ai.timeout', 30)
                );
            }

            return new AiFakeDriver;
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
