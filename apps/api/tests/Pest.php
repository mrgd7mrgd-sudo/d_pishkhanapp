<?php

declare(strict_types=1);

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature', 'Unit/Shared', 'Unit/Identity', 'Contract');

pest()->group('arch')
    ->in('Architecture');
