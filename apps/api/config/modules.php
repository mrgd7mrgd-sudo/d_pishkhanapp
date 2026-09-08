<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Bounded Context Modules (Architecture §5.2)
    |--------------------------------------------------------------------------
    |
    | The 10 core bounded context modules that comprise the Modular Monolith.
    | Each module must adhere to the mandatory DDD structure:
    | Contracts/, Domain/, Application/, Infrastructure/, Http/, Database/, Listeners/, Jobs/
    |
    */
    'modules' => [
        'Identity',
        'ServiceCatalog',
        'CaseWorkflow',
        'OfficeNetwork',
        'Documents',
        'Payments',
        'Delivery',
        'Consultation',
        'Messaging',
        'AiAssistance',
    ],
];
