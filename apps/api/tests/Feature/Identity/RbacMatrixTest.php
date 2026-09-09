<?php

declare(strict_types=1);

use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Enums\RoleName;
use App\Modules\Identity\Domain\Enums\SystemPermission;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

dataset('rbac_matrix', [
    // [permission, citizen, delegate, operator, manager, admin, auditor]
    ['services.view', true, true, true, true, true, true],
    ['cases.create', true, true, false, false, false, false],
    ['cases.view', true, true, true, true, true, true],
    ['documents.download', true, true, true, true, true, false],
    ['dispatch.accept', false, false, true, true, false, false],
    ['cases.return', false, false, true, true, false, false],
    ['cases.reject', false, false, false, true, true, false],
    ['cases.complete', false, false, true, true, false, false],
    ['deliveries.create', false, false, true, true, false, false],
    ['deliveries.confirm_otp', false, false, true, true, false, false],
    ['finances.view', false, false, false, true, true, true],
    ['reviews.reply', false, false, false, true, true, false],
    ['office.profile.update', false, false, false, true, true, false],
    ['office.operators.manage', false, false, false, true, true, false],
    ['office.register.approve', false, false, false, false, true, false],
    ['advisor.register.approve', false, false, false, false, true, false],
    ['service_catalog.manage', false, false, false, false, true, false],
    ['delegation.create', true, false, false, false, false, false],
    ['audit_logs.view', false, false, false, true, true, true],
    ['consultations.conduct', false, false, false, false, false, false],
]);

test('rbac matrix 20 rows x 6 columns matches architecture specification §7.3', function (
    string $permission,
    bool $citizenExpected,
    bool $delegateExpected,
    bool $operatorExpected,
    bool $managerExpected,
    bool $adminExpected,
    bool $auditorExpected,
): void {
    $citizen = Role::findByName(RoleName::CITIZEN->value, 'web');
    $delegate = Role::findByName(RoleName::CITIZEN_DELEGATE->value, 'web');
    $operator = Role::findByName(RoleName::OFFICE_OPERATOR->value, 'web');
    $manager = Role::findByName(RoleName::OFFICE_MANAGER->value, 'web');
    $admin = Role::findByName(RoleName::SYSTEM_ADMIN->value, 'web');
    $auditor = Role::findByName(RoleName::AUDITOR->value, 'web');

    expect($citizen->hasPermissionTo($permission))->toBe($citizenExpected, "Citizen role permission mismatch for {$permission}")
        ->and($delegate->hasPermissionTo($permission))->toBe($delegateExpected, "Delegate role permission mismatch for {$permission}")
        ->and($operator->hasPermissionTo($permission))->toBe($operatorExpected, "Operator role permission mismatch for {$permission}")
        ->and($manager->hasPermissionTo($permission))->toBe($managerExpected, "Manager role permission mismatch for {$permission}")
        ->and($admin->hasPermissionTo($permission))->toBe($adminExpected, "Admin role permission mismatch for {$permission}")
        ->and($auditor->hasPermissionTo($permission))->toBe($auditorExpected, "Auditor role permission mismatch for {$permission}");
})->with('rbac_matrix');

test('advisor role has consultation conduct and service catalog view permissions only', function (): void {
    $advisor = Role::findByName(RoleName::ADVISOR->value, 'web');

    expect($advisor->hasPermissionTo(SystemPermission::SERVICES_VIEW->value))->toBeTrue()
        ->and($advisor->hasPermissionTo(SystemPermission::CONSULTATIONS_CONDUCT->value))->toBeTrue()
        ->and($advisor->hasPermissionTo(SystemPermission::CASES_CREATE->value))->toBeFalse()
        ->and($advisor->hasPermissionTo(SystemPermission::CASES_REJECT->value))->toBeFalse()
        ->and($advisor->hasPermissionTo(SystemPermission::DOCUMENTS_DOWNLOAD->value))->toBeFalse();
});

test('exactly seven distinct roles are defined in the system', function (): void {
    $roles = Role::pluck('name')->all();

    expect($roles)->toHaveCount(7)
        ->and($roles)->toContain(
            'citizen',
            'citizen_delegate',
            'advisor',
            'office_operator',
            'office_manager',
            'system_admin',
            'auditor'
        );
});

test('citizen model can be assigned citizen role and verify permissions', function (): void {
    $citizen = Citizen::query()->create([
        'national_id_hash' => hash_hmac('sha256', '1234567890', 'test_pepper'),
        'mobile_hash' => hash_hmac('sha256', '09121111111', 'test_pepper'),
        'full_name' => 'علی رضایی',
    ]);

    $citizen->assignRole('citizen');

    expect($citizen->hasRole('citizen'))->toBeTrue()
        ->and($citizen->hasPermissionTo('services.view'))->toBeTrue()
        ->and($citizen->hasPermissionTo('cases.create'))->toBeTrue()
        ->and($citizen->hasPermissionTo('dispatch.accept'))->toBeFalse();
});

test('operator model can be assigned office_operator role and verify permissions', function (): void {
    $operator = Operator::query()->create([
        'username' => 'op_tester',
        'password_hash' => 'dummy_hash',
        'national_id_hash' => hash_hmac('sha256', '0012345678', 'test_pepper'),
        'mobile_hash' => hash_hmac('sha256', '09121111112', 'test_pepper'),
        'full_name' => 'اپراتور تستر',
        'counter_number' => 2,
    ]);

    $operator->assignRole('office_operator');

    expect($operator->hasRole('office_operator'))->toBeTrue()
        ->and($operator->hasPermissionTo('dispatch.accept'))->toBeTrue()
        ->and($operator->hasPermissionTo('cases.return'))->toBeTrue()
        ->and($operator->hasPermissionTo('cases.reject'))->toBeFalse()
        ->and($operator->hasPermissionTo('finances.view'))->toBeFalse();
});
