<?php

declare(strict_types=1);

return [
    'crypto' => [
        'current_kek_version' => env('KEK_CURRENT_VERSION', 'kek_v1'),
        'pepper' => env('PEPPER', ''),
        'kek_v1' => env('KEK_V1', ''),
    ],
];
