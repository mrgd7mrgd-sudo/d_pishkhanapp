<?php

declare(strict_types=1);

namespace App\Integration\Sms;

use App\Integration\Sms\Drivers\FakeDriver;
use App\Integration\Sms\Drivers\KavenegarDriver;
use App\Integration\Sms\Drivers\SmsIrDriver;
use Illuminate\Support\ServiceProvider;

final class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsGateway::class, function ($app): SmsGateway {
            $driver = config('pishkhan.sms.driver', 'fake');

            if ($driver === 'fake') {
                return new FakeDriver;
            }

            $kavenegarKey = (string) config('pishkhan.sms.kavenegar.api_key', '');
            $kavenegarSender = (string) config('pishkhan.sms.kavenegar.sender', '10004346');
            $kavenegar = new KavenegarDriver($kavenegarKey, $kavenegarSender);

            $smsirKey = (string) config('pishkhan.sms.smsir.api_key', '');
            $smsirLine = (string) config('pishkhan.sms.smsir.line_number', '30007732');
            $smsir = new SmsIrDriver($smsirKey, $smsirLine);

            if ($driver === 'circuit_breaker') {
                return new CircuitBreakerSmsGateway($kavenegar, $smsir);
            }

            if ($driver === 'kavenegar') {
                return $kavenegar;
            }

            if ($driver === 'smsir') {
                return $smsir;
            }

            return new FakeDriver;
        });
    }
}
