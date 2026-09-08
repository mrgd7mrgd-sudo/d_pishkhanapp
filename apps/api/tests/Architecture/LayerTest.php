<?php

declare(strict_types=1);

arch('controllers do not directly use DB or query builder')
    ->expect([
        'App\Modules\Identity\Http\Controllers',
        'App\Modules\ServiceCatalog\Http\Controllers',
        'App\Modules\CaseWorkflow\Http\Controllers',
        'App\Modules\OfficeNetwork\Http\Controllers',
        'App\Modules\Documents\Http\Controllers',
        'App\Modules\Payments\Http\Controllers',
        'App\Modules\Delivery\Http\Controllers',
        'App\Modules\Consultation\Http\Controllers',
        'App\Modules\Messaging\Http\Controllers',
        'App\Modules\AiAssistance\Http\Controllers',
    ])
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\Query\Builder',
    ]);

arch('domain layer does not use Http facade or Guzzle')
    ->expect([
        'App\Modules\Identity\Domain',
        'App\Modules\ServiceCatalog\Domain',
        'App\Modules\CaseWorkflow\Domain',
        'App\Modules\OfficeNetwork\Domain',
        'App\Modules\Documents\Domain',
        'App\Modules\Payments\Domain',
        'App\Modules\Delivery\Domain',
        'App\Modules\Consultation\Domain',
        'App\Modules\Messaging\Domain',
        'App\Modules\AiAssistance\Domain',
    ])
    ->not->toUse([
        'Illuminate\Support\Facades\Http',
        'GuzzleHttp',
    ]);
