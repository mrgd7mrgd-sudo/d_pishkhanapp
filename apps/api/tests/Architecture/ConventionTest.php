<?php

declare(strict_types=1);

arch('strict types are declared everywhere in app')
    ->expect('App')
    ->toUseStrictTypes();

arch('classes in modules and shared are final by default')
    ->expect(['App\\Modules', 'App\\Shared'])
    ->classes()
    ->toBeFinal()
    ->ignoring([
        'App\Modules\*\Database\Migrations',
        'App\Modules\*\Domain\Models',
        'App\Shared\Events\DomainEvent',
        'App\Modules\Identity\Infrastructure\Policies\BasePolicy',
    ]);

arch('enums are string backed')
    ->expect('App')
    ->enums()
    ->toBeEnums();
