<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Shared\Errors\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('five consecutive failed attempts locks challenge and account for 15 minutes', function () {
    $mobile = '09121113355';
    $code = '88888';

    $challenge = OtpChallenge::createChallenge(
        mobile: $mobile,
        rawCode: $code,
        purpose: OtpPurpose::LOGIN,
        ttlSeconds: 120
    );

    // Attempts 1 to 4: Return 422 AUTH_OTP_INVALID
    for ($i = 1; $i <= 4; $i++) {
        $res = $this->postJson('/api/v1/auth/otp/verify', [
            'challenge_id' => $challenge->id,
            'code' => '00000',
        ]);

        $res->assertStatus(422)
            ->assertJson([
                'code' => ErrorCode::AUTH_OTP_INVALID->value,
            ]);
    }

    // Attempt 5: Exceeds threshold -> Locks challenge for 15 minutes (429 with retry_after)
    $res5 = $this->postJson('/api/v1/auth/otp/verify', [
        'challenge_id' => $challenge->id,
        'code' => '00000',
    ]);

    $res5->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJson([
            'status' => 429,
            'code' => ErrorCode::AUTH_OTP_TOO_MANY->value,
            'retry_after' => 900,
        ]);

    // Attempt 6 (even with correct code!): Must be locked out
    $res6 = $this->postJson('/api/v1/auth/otp/verify', [
        'challenge_id' => $challenge->id,
        'code' => $code, // Correct code!
    ]);

    $res6->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJson([
            'status' => 429,
            'code' => ErrorCode::AUTH_OTP_TOO_MANY->value,
        ]);
});
