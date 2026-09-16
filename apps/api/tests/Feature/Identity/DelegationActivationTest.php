<?php

declare(strict_types=1);

use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\DelegationStatus;
use App\Modules\Identity\Domain\Enums\RoleName;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Delegation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function createDelegationCitizen(string $mobile, string $nationalId, string $name): Citizen
{
    return Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', $mobile),
        'mobile_encrypted' => 'enc:'.$mobile,
        'national_id_hash' => hash('sha256', $nationalId),
        'national_id_encrypted' => 'enc:'.$nationalId,
        'full_name' => $name,
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);
}

it('verifies two-party OTP activation: delegation only becomes active after both parties verify (§5.3, §7.1 T4, §10.3 E17, TASK-121, TASK-121-T)', function (): void {
    $principal = createDelegationCitizen('09121113301', '0011113301', 'موکل اصلی');
    $delegate = createDelegationCitizen('09121113302', '0011113302', 'وکیل معتمد');

    expect($delegate->hasRole(RoleName::CITIZEN_DELEGATE->value))->toBeFalse();

    Sanctum::actingAs($principal, ['*']);

    // 1. Principal creates delegation
    $createRes = $this->postJson('/api/v1/profile/delegations', [
        'delegate_national_id_or_mobile' => '0011113302',
        'valid_until' => CarbonImmutable::now()->addMonths(6)->toISOString(),
        'max_amount_rials' => 50000000, // 50,000,000 Rials
        'allowed_service_ids' => ['srv-passport-issue', 'srv-driving-license'],
        'document_number' => 'NOTARY-98745',
    ])->assertStatus(201)
        ->assertJsonPath('data.status', 'pending_otp')
        ->assertJsonPath('data.principal_otp_verified', false)
        ->assertJsonPath('data.delegate_otp_verified', false);

    $delegationId = $createRes->json('data.id');
    $principalOtp = $createRes->json('meta.principal_otp');
    $delegateOtp = $createRes->json('meta.delegate_otp');

    expect($principalOtp)->not->toBeEmpty()
        ->and($delegateOtp)->not->toBeEmpty();

    // 2. Only principal verifies OTP -> delegation remains pending_otp
    $this->postJson("/api/v1/profile/delegations/{$delegationId}/activate", [
        'otp_code' => $principalOtp,
    ])->assertStatus(200)
        ->assertJsonPath('data.status', 'pending_otp')
        ->assertJsonPath('data.principal_otp_verified', true)
        ->assertJsonPath('data.delegate_otp_verified', false);

    $delegation = Delegation::query()->find($delegationId);
    expect($delegation->status)->toBe(DelegationStatus::PendingOtp);

    // Delegate still does not have the citizen_delegate role
    expect($delegate->fresh()->hasRole(RoleName::CITIZEN_DELEGATE->value))->toBeFalse();

    // 3. Delegate logs in and verifies their OTP -> delegation becomes active!
    Sanctum::actingAs($delegate, ['*']);

    $this->postJson("/api/v1/profile/delegations/{$delegationId}/activate", [
        'otp_code' => $delegateOtp,
    ])->assertStatus(200)
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.principal_otp_verified', true)
        ->assertJsonPath('data.delegate_otp_verified', true);

    $delegation->refresh();
    expect($delegation->status)->toBe(DelegationStatus::Active)
        ->and($delegation->activated_at)->not->toBeNull();

    // Invariant §7.3: Delegate has now been granted citizen_delegate role!
    expect($delegate->fresh()->hasRole(RoleName::CITIZEN_DELEGATE->value))->toBeTrue();

    // Verify Audit Log was recorded (§7.6)
    $audit = DB::table('audit_logs')
        ->where('action', 'delegation.activated')
        ->where('subject_id', $delegationId)
        ->first();
    expect($audit)->not->toBeNull();
});

it('verifies past valid_until is rejected (Invariant §5.3, TASK-121, TASK-121-T)', function (): void {
    $principal = createDelegationCitizen('09121113303', '0011113303', 'کاربر تست');
    $delegate = createDelegationCitizen('09121113304', '0011113304', 'وکیل تست');

    Sanctum::actingAs($principal, ['*']);

    $this->postJson('/api/v1/profile/delegations', [
        'delegate_national_id_or_mobile' => '0011113304',
        'valid_until' => CarbonImmutable::now()->subDays(2)->toISOString(), // In the past!
        'max_amount_rials' => 10000000,
    ])->assertStatus(422);
});

it('verifies max_amount_rials = 0 or negative is rejected (Invariant §5.3, TASK-121, TASK-121-T)', function (): void {
    $principal = createDelegationCitizen('09121113305', '0011113305', 'کاربر تست ۲');
    $delegate = createDelegationCitizen('09121113306', '0011113306', 'وکیل تست ۲');

    Sanctum::actingAs($principal, ['*']);

    $this->postJson('/api/v1/profile/delegations', [
        'delegate_national_id_or_mobile' => '0011113306',
        'valid_until' => CarbonImmutable::now()->addMonths(1)->toISOString(),
        'max_amount_rials' => 0, // Invariant §5.3: must be > 0
    ])->assertStatus(422);
});

it('verifies instant revocation by principal (§5.3, TASK-121, TASK-121-T)', function (): void {
    $principal = createDelegationCitizen('09121113307', '0011113307', 'موکل لغوکننده');
    $delegate = createDelegationCitizen('09121113308', '0011113308', 'وکیل لغوشونده');

    $delegation = Delegation::query()->create([
        'principal_citizen_id' => $principal->id,
        'delegate_citizen_id' => $delegate->id,
        'status' => DelegationStatus::Active,
        'max_amount_rials' => 10000000,
        'valid_until' => CarbonImmutable::now()->addMonths(3),
        'principal_otp_verified' => true,
        'delegate_otp_verified' => true,
        'activated_at' => CarbonImmutable::now()->subDays(1),
    ]);

    Sanctum::actingAs($principal, ['*']);

    $this->postJson("/api/v1/profile/delegations/{$delegation->id}/revoke")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'revoked');

    $delegation->refresh();
    expect($delegation->status)->toBe(DelegationStatus::Revoked)
        ->and($delegation->revoked_at)->not->toBeNull();
});
