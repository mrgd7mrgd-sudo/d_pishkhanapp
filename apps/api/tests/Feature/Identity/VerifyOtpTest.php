<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Shared\Audit\AuditableAction;
use App\Shared\Crypto\EnvelopeEncryptor;
use App\Shared\Errors\ErrorCode;
use App\Shared\Http\Middleware\RequestId;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('verifying valid OTP issues Sanctum PAT with 7-day TTL, masked citizen details, and records audit', function () {
    $this->seed(ProvinceSeeder::class);

    $mobile = '09123456781';
    $code = '48291';
    $encryptor = app(EnvelopeEncryptor::class);

    // Pre-create existing citizen with full data
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = $mobile;
    $citizen->full_name = 'سید علی حسینی';
    $citizen->tier = CitizenTier::SILVER;
    $citizen->province_code = 'THR';
    $citizen->save();

    // Create active challenge
    $challenge = OtpChallenge::createChallenge(
        mobile: $mobile,
        rawCode: $code,
        purpose: OtpPurpose::LOGIN,
        ipAddress: '127.0.0.1',
        ttlSeconds: 120
    );

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'challenge_id' => $challenge->id,
        'code' => $code,
        'device_name' => 'Android · Chrome 120',
    ]);

    $response->assertStatus(200)
        ->assertHeader(RequestId::HEADER_NAME)
        ->assertJsonStructure([
            'data' => [
                'token',
                'token_type',
                'expires_at',
                'citizen' => [
                    'id',
                    'full_name',
                    'national_id_masked',
                    'mobile_masked',
                    'tier',
                    'tier_name',
                    'sana_verified',
                    'digital_signature_active',
                    'credit_score',
                    'wallet_balance_rials',
                    'province_code',
                    'city_code',
                ],
                'abilities',
            ],
        ]);

    $data = $response->json('data');
    expect($data['token_type'])->toBe('Bearer');
    expect($data['token'])->toBeString()->toContain('|');
    expect($data['abilities'])->toBe(['case:create', 'case:read', 'document:upload', 'wallet:topup', 'consultation:book']);

    // PII Masking verification (§5.6 #2, §7.7)
    expect($data['citizen']['national_id_masked'])->toBe('001****802');
    expect($data['citizen']['mobile_masked'])->toBe('0912***6781');
    expect($data['citizen']['full_name'])->toBe('سید علی حسینی');
    expect($data['citizen']['tier'])->toBe('silver');

    // Token security verification: plain token is NEVER stored in database, it must be hashed (SHA-256)
    [$tokenId, $plainSecret] = explode('|', $data['token'], 2);
    $tokenRecord = DB::table('personal_access_tokens')->where('id', $tokenId)->first();
    expect($tokenRecord)->not->toBeNull();
    expect($tokenRecord->token)->not->toBe($plainSecret);
    expect($tokenRecord->token)->toBe(hash('sha256', $plainSecret));

    // Token TTL verification (7 days)
    $expiresAt = CarbonImmutable::parse($data['expires_at']);
    $expectedExpiry = CarbonImmutable::now()->addDays(7);
    expect(abs($expiresAt->diffInSeconds($expectedExpiry)))->toBeLessThan(5);

    // Audit log verification (§7.6)
    $auditLog = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_LOGIN_SUCCESS->value)
        ->first();
    expect($auditLog)->not->toBeNull();
    expect($auditLog->subject_id)->toBe($citizen->id);
});

test('expired challenge returns AUTH_OTP_EXPIRED 410', function () {
    $mobile = '09123456782';
    $code = '12345';

    $challenge = OtpChallenge::createChallenge(
        mobile: $mobile,
        rawCode: $code,
        purpose: OtpPurpose::LOGIN,
        ttlSeconds: 120
    );

    // Force expiration
    $challenge->expires_at = CarbonImmutable::now()->subMinute();
    $challenge->save();

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'challenge_id' => $challenge->id,
        'code' => $code,
    ]);

    $response->assertStatus(410)
        ->assertJson([
            'status' => 410,
            'code' => ErrorCode::AUTH_OTP_EXPIRED->value,
            'title' => ErrorCode::AUTH_OTP_EXPIRED->title(),
        ]);
});

test('invalid code increments attempts and returns AUTH_OTP_INVALID 422', function () {
    $mobile = '09123456783';
    $code = '99999';

    $challenge = OtpChallenge::createChallenge(
        mobile: $mobile,
        rawCode: $code,
        purpose: OtpPurpose::LOGIN,
        ttlSeconds: 120
    );

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'challenge_id' => $challenge->id,
        'code' => '11111', // Incorrect code
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 422,
            'code' => ErrorCode::AUTH_OTP_INVALID->value,
            'title' => ErrorCode::AUTH_OTP_INVALID->title(),
        ]);

    $challenge->refresh();
    expect($challenge->attempts)->toBe(1);
});
