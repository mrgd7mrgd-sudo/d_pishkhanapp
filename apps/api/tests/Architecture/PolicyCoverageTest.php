<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class);

test('every domain model has a registered policy', function (): void {
    $basePath = realpath(__DIR__.'/../../app/Modules');
    expect($basePath)->not->toBeFalse();

    $modelFiles = glob("{$basePath}/*/Domain/Models/*.php") ?: [];
    expect($modelFiles)->not->toBeEmpty();

    foreach ($modelFiles as $file) {
        $realFile = realpath($file);
        if ($realFile === false) {
            continue;
        }

        $relative = str_replace([$basePath, '/', '\\', '.php'], ['', '\\', '\\', ''], $realFile);
        /** @var class-string $className */
        $className = 'App\\Modules'.$relative;

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);
        if ($reflection->isAbstract() || $reflection->isTrait()) {
            continue;
        }

        $policy = Gate::getPolicyFor($className);

        expect($policy)->not->toBeNull("Domain model [{$className}] does not have a registered authorization policy.");
    }
});
