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
];
