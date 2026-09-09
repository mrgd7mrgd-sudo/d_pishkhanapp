<?php

declare(strict_types=1);

use App\Integration\Geo\GeoServiceProvider;
use App\Integration\Sms\SmsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\RateLimitServiceProvider;

return [
    AppServiceProvider::class,
    ModuleServiceProvider::class,
    RateLimitServiceProvider::class,
    SmsServiceProvider::class,
    GeoServiceProvider::class,
];
