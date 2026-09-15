<?php

declare(strict_types=1);

namespace App\Integration\Payment;

use App\Integration\Payment\Drivers\FakeDriver;
use App\Integration\Payment\Drivers\ZarinPalDriver;
use App\Integration\Payment\Drivers\ZibalDriver;
use Illuminate\Support\ServiceProvider;

/**
 * Payment Integration Service Provider (Architecture §8.0, §8.2, TASK-085).
 */
final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FakeDriver::class, fn (): FakeDriver => new FakeDriver);

        $this->app->singleton(ZarinPalDriver::class, function ($app): ZarinPalDriver {
            $config = $app['config']->get('pishkhan.payment.zarinpal', []);

            return new ZarinPalDriver(
                merchantId: (string) ($config['merchant_id'] ?? '00000000-0000-0000-0000-000000000000'),
                sandbox: (bool) ($config['sandbox'] ?? true)
            );
        });

        $this->app->singleton(ZibalDriver::class, function ($app): ZibalDriver {
            $config = $app['config']->get('pishkhan.payment.zibal', []);

            return new ZibalDriver(
                merchant: (string) ($config['merchant'] ?? 'zibal'),
                sandbox: (bool) ($config['sandbox'] ?? true)
            );
        });

        $this->app->singleton(PaymentGateway::class, function ($app): PaymentGateway {
            $driver = $app['config']->get('pishkhan.payment.driver', 'fake');

            return match ($driver) {
                'zarinpal' => $app->make(ZarinPalDriver::class),
                'zibal' => $app->make(ZibalDriver::class),
                'circuit_breaker' => new CircuitBreakerPaymentGateway(
                    primaryDriver: $app->make(ZarinPalDriver::class),
                    fallbackDriver: $app->make(ZibalDriver::class)
                ),
                default => $app->make(FakeDriver::class),
            };
        });
    }
}
