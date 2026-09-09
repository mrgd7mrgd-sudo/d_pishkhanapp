<?php

declare(strict_types=1);

use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Shared\Scopes\OfficeScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->officeA = Office::query()->create([
        'code' => '1001',
        'name' => 'دفتر پیشخوان انقلاب',
        'is_online' => true,
    ]);

    $this->officeB = Office::query()->create([
        'code' => '1002',
        'name' => 'دفتر پیشخوان آزادی',
        'is_online' => true,
    ]);

    $this->operatorA = Operator::query()->create([
        'office_id' => $this->officeA->id,
        'username' => 'op_a',
        'password_hash' => 'hash_a',
        'national_id_hash' => hash_hmac('sha256', '0011111111', 'pepper'),
        'mobile_hash' => hash_hmac('sha256', '09121111111', 'pepper'),
        'full_name' => 'اپراتور دفتر الف',
        'counter_number' => 1,
    ]);
    $this->operatorA->assignRole('office_operator');

    $this->operatorB = Operator::query()->create([
        'office_id' => $this->officeB->id,
        'username' => 'op_b',
        'password_hash' => 'hash_b',
        'national_id_hash' => hash_hmac('sha256', '0022222222', 'pepper'),
        'mobile_hash' => hash_hmac('sha256', '09122222222', 'pepper'),
        'full_name' => 'اپراتور دفتر ب',
        'counter_number' => 1,
    ]);
    $this->operatorB->assignRole('office_operator');

    $this->admin = Operator::query()->create([
        'office_id' => null,
        'username' => 'sysadmin',
        'password_hash' => 'hash_admin',
        'national_id_hash' => hash_hmac('sha256', '0033333333', 'pepper'),
        'mobile_hash' => hash_hmac('sha256', '09123333333', 'pepper'),
        'full_name' => 'مدیر سیستم',
        'counter_number' => 1,
    ]);
    $this->admin->assignRole('system_admin');
});

test('operator of office A accessing office B endpoint returns exactly 404 not 403', function (): void {
    $response = $this->actingAs($this->operatorA, 'operator')
        ->getJson("/api/v1/desk/offices/{$this->officeB->id}");

    $response->assertStatus(404);
});

test('operator of office A accessing own office A endpoint returns 200', function (): void {
    $response = $this->actingAs($this->operatorA, 'operator')
        ->getJson("/api/v1/desk/offices/{$this->officeA->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.code', '1001');
});

test('operator of office A accessing office B operators list returns 404', function (): void {
    $response = $this->actingAs($this->operatorA, 'operator')
        ->getJson("/api/v1/desk/offices/{$this->officeB->id}/operators");

    $response->assertStatus(404);
});

test('operator of office A accessing office A operators list returns 200', function (): void {
    $response = $this->actingAs($this->operatorA, 'operator')
        ->getJson("/api/v1/desk/offices/{$this->officeA->id}/operators");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.username', 'op_a');
});

test('operator of office A accessing operator B resource returns 404', function (): void {
    $response = $this->actingAs($this->operatorA, 'operator')
        ->getJson("/api/v1/desk/operators/{$this->operatorB->id}");

    $response->assertStatus(404);
});

test('operator of office A accessing own operator resource returns 200', function (): void {
    $response = $this->actingAs($this->operatorA, 'operator')
        ->getJson("/api/v1/desk/operators/{$this->operatorA->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.username', 'op_a');
});

test('operator accessing endpoint with mismatched X-Office-Id header returns 404', function (): void {
    $response = $this->actingAs($this->operatorA, 'operator')
        ->withHeader('X-Office-Id', $this->officeB->id)
        ->getJson("/api/v1/desk/offices/{$this->officeA->id}");

    $response->assertStatus(404);
});

test('office scope actively hides cross-office operators and bypass proves effectiveness', function (): void {
    Auth::setUser($this->operatorA);

    $visibleWithScope = Operator::query()->get();
    expect($visibleWithScope)->toHaveCount(1)
        ->and($visibleWithScope->first()->id)->toBe($this->operatorA->id)
        ->and(Operator::find($this->operatorB->id))->toBeNull();

    $bypassed = Operator::withoutGlobalScope(OfficeScope::class)->find($this->operatorB->id);
    expect($bypassed)->not->toBeNull()
        ->and($bypassed->id)->toBe($this->operatorB->id);
});

test('system admin can access any office resource', function (): void {
    $response = $this->actingAs($this->admin, 'operator')
        ->getJson("/api/v1/desk/offices/{$this->officeB->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.code', '1002');
});
