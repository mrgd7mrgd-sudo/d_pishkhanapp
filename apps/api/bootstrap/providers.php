<?php

declare(strict_types=1);

use App\Integration\Sms\SmsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;

return [
    AppServiceProvider::class,
    ModuleServiceProvider::class,
    SmsServiceProvider::class,
];
