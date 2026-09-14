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
    'dispatch' => [
        'max_rounds' => (int) env('DISPATCH_MAX_ROUNDS', 5),
        'radius_km' => [
            1 => 5.0,
            2 => 10.0,
            3 => 15.0,
            4 => 25.0,
            5 => 40.0,
        ],
        'batch_size' => (int) env('DISPATCH_BATCH_SIZE', 3),
        'offer_ttl_seconds' => (int) env('DISPATCH_OFFER_TTL_SECONDS', 90),
    ],
    'push' => [
        'vapid_public_key' => env('VAPID_PUBLIC_KEY', 'test_vapid_public_key'),
        'vapid_private_key' => env('VAPID_PRIVATE_KEY', 'test_vapid_private_key'),
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@pishkhan.ir'),
    ],
    'gov' => [
        'driver' => env('GOV_DRIVER', 'simulator'),
    ],
];
