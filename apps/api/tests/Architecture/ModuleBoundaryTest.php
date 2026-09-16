<?php

declare(strict_types=1);

arch('modules communicate only via contracts')
    ->expect('App\\Modules')
    ->toOnlyUse([
        'App\\Modules',
        'App\\Shared',
        'App\\Integration',
        'Illuminate',
        'Laravel\\Sanctum',
        'Carbon',
        'Symfony',
        'Symfony\\Component\\HttpFoundation\\Response',
        'Symfony\\Component\\HttpKernel\\Exception\\HttpExceptionInterface',
        'Spatie\\Permission',
        'config',
        'view',
        'now',
        'response',
        'app',
        'event',
        'base_path',
        'resource_path',
        'TCPDF',
        'TCPDF_FONTS',
    ]);
