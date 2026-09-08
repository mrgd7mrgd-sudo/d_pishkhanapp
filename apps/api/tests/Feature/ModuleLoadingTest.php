<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('loads all 10 bounded context modules from config', function (): void {
    /** @var list<string> $modules */
    $modules = config('modules.modules');

    expect($modules)->toHaveCount(10)
        ->toContain('Identity')
        ->toContain('ServiceCatalog')
        ->toContain('CaseWorkflow')
        ->toContain('OfficeNetwork')
        ->toContain('Documents')
        ->toContain('Payments')
        ->toContain('Delivery')
        ->toContain('Consultation')
        ->toContain('Messaging')
        ->toContain('AiAssistance');
});

it('registers module routes under api/v1 prefix', function (): void {
    $response = $this->getJson('/api/v1/services/ping');

    $response->assertOk()
        ->assertJson([
            'module' => 'ServiceCatalog',
            'status' => 'active',
        ]);
});

it('loads module migrations and creates tables correctly', function (): void {
    expect(Schema::hasTable('_test_module_registry'))->toBeTrue();
});
