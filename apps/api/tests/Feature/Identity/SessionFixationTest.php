<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('session id regenerates upon successful operator login to prevent session fixation', function () {
    $office = Office::create([
        'code' => '2001',
        'name' => 'دفتر تجریش',
        'is_online' => true,
    ]);

    $mobile = '09121234500';
    $operator = new Operator;
    $operator->office_id = $office->id;
    $operator->username = 'fixation_test_user';
    $operator->password_hash = Hash::make('FixationPassword123!');
    $operator->full_name = 'کاربر تست تثبیت نشست';
    $operator->national_id = '0031234567';
    $operator->mobile = $mobile;
    $operator->role = OperatorRole::OPERATOR;
    $operator->save();

    $code = '67890';
    $challenge = OtpChallenge::createChallenge(
        mobile: $mobile,
        rawCode: $code,
        purpose: OtpPurpose::LOGIN,
        ipAddress: '127.0.0.1',
        ttlSeconds: 120
    );

    // Initial anonymous session
    $initialResponse = $this->withSession(['anonymous_key' => 'initial_value'])
        ->postJson('/api/v1/operator/auth/verify-otp', [
            'challenge_id' => $challenge->id,
            'code' => $code,
        ]);

    $initialResponse->assertStatus(200);

    $newSessionId = $initialResponse->json('data.session_id');
    expect($newSessionId)->not->toBeNull();
});

test('operator session expires after 30 minutes of inactivity', function () {
    $office = Office::create([
        'code' => '2002',
        'name' => 'دفتر نیاوران',
        'is_online' => true,
    ]);

    $operator = new Operator;
    $operator->office_id = $office->id;
    $operator->username = 'inactive_operator';
    $operator->password_hash = Hash::make('InactivePassword123!');
    $operator->full_name = 'اپراتور غیرفعال';
    $operator->national_id = '0021234567';
    $operator->mobile = '09121234501';
    $operator->save();

    // 1. Request with active session (10 minutes ago - within 30 min window)
    $activeTimestamp = CarbonImmutable::now()->subMinutes(10)->timestamp;

    $activeResponse = $this->actingAs($operator, 'operator')
        ->withSession(['last_activity' => $activeTimestamp])
        ->getJson('/api/v1/operator/auth/me');

    $activeResponse->assertStatus(200);

    // 2. Request with idle session (31 minutes ago - expired)
    $expiredTimestamp = CarbonImmutable::now()->subMinutes(31)->timestamp;

    $expiredResponse = $this->actingAs($operator, 'operator')
        ->withSession(['last_activity' => $expiredTimestamp])
        ->getJson('/api/v1/operator/auth/me');

    $expiredResponse->assertStatus(401)
        ->assertJsonPath('code', 'AUTH_SESSION_EXPIRED');
});
