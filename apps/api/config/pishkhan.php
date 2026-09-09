<?php

declare(strict_types=1);

return [
    'crypto' => [
        'current_kek_version' => env('KEK_CURRENT_VERSION', 'kek_v1'),
        'pepper' => env('PEPPER', ''),
        'kek_v1' => env('KEK_V1', ''),
    ],
    'sms' => [
        'driver' => env('SMS_DRIVER', 'fake'), // 'fake', 'kavenegar', 'circuit_breaker'
        'kavenegar' => [
            'api_key' => env('KAVENEGAR_API_KEY', ''),
            'sender' => env('KAVENEGAR_SENDER', '10004346'),
        ],
        'smsir' => [
            'api_key' => env('SMSIR_API_KEY', ''),
            'line_number' => env('SMSIR_LINE_NUMBER', '30007732'),
        ],
    ],
    'geo' => [
        'driver' => env('GEO_DRIVER', 'fake'), // 'fake', 'neshan'
        'neshan' => [
            'api_key' => env('NESHAN_API_KEY', ''),
            'base_url' => env('NESHAN_BASE_URL', 'https://api.neshan.org'),
        ],
        'tile_proxy' => [
            'cache_days' => 30,
            'url_prefix' => env('TILE_PROXY_URL_PREFIX', '/tiles'),
        ],
    ],
];
