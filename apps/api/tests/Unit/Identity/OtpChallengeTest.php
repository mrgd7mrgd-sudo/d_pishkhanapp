<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('raw OTP code is never stored in plaintext anywhere in the database', function () {
    $rawCode = '48291';
    $mobile = '09123456789';

    $challenge = OtpChallenge::createChallenge(
        mobile: $mobile,
        rawCode: $rawCode,
        purpose: OtpPurpose::LOGIN,
        ipAddress: '192.168.1.1',
        ttlSeconds: 120
    );

    $record = DB::table('otp_challenges')->where('id', $challenge->id)->first();
    expect($record)->not->toBeNull();

    // Verify raw code is not stored in code_hash column
    expect($record->code_hash)->not->toBe($rawCode);
    expect($record->code_hash)->not->toContain($rawCode);

    // Verify code_hash is valid bcrypt hash matching the raw code
    expect(Hash::check($rawCode, $record->code_hash))->toBeTrue();
});

test('challenge is expired when time exceeds 120 seconds TTL', function () {
    $challenge = OtpChallenge::createChallenge(
        mobile: '09123456789',
        rawCode: '12345',
        purpose: OtpPurpose::LOGIN,
        ttlSeconds: 120
    );

    expect($challenge->isExpired())->toBeFalse();

    // Travel 121 seconds into the future
    $this->travel(121)->seconds();

    // Re-fetch from database
    $fresh = OtpChallenge::query()->findOrFail($challenge->id);
    expect($fresh->isExpired())->toBeTrue();
    expect($fresh->verifyCode('12345'))->toBeFalse();
});

test('verifyCode accurately verifies valid code and rejects invalid code', function () {
    $challenge = OtpChallenge::createChallenge(
        mobile: '09123456789',
        rawCode: '74125',
        purpose: OtpPurpose::LOGIN,
        ttlSeconds: 120
    );

    // Wrong code fails
    expect($challenge->verifyCode('00000'))->toBeFalse();
    expect($challenge->attempts)->toBe(1);

    // Correct code succeeds and records verified_at
    expect($challenge->verifyCode('74125'))->toBeTrue();
    expect($challenge->verified_at)->not->toBeNull();
});

test('challenge fails verification when attempts exceed 5', function () {
    $challenge = OtpChallenge::createChallenge(
        mobile: '09123456789',
        rawCode: '99887',
        purpose: OtpPurpose::LOGIN,
        ttlSeconds: 120
    );

    // 5 wrong attempts
    for ($i = 0; $i < 5; $i++) {
        expect($challenge->verifyCode('11111'))->toBeFalse();
    }

    // Sixth attempt with correct code should be rejected due to rate exhaustion
    expect($challenge->verifyCode('99887'))->toBeFalse();
});
