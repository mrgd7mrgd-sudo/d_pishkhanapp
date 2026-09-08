<?php

declare(strict_types=1);

arch('modules communicate only via contracts')
    ->expect('App\\Modules')
    ->toOnlyUse([
        'App\\Modules',
        'App\\Shared',
        'App\\Integration',
        'Illuminate',
        'Carbon',
        'Symfony',
    ]);
