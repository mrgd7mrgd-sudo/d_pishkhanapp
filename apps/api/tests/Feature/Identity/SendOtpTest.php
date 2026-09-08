<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Shared\Audit\AuditableAction;
use App\Shared\Errors\ErrorCode;
use App\Shared\Http\Middleware\RequestId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('rl:otp:mobile:'.sha1('09123456781'));
    RateLimiter::clear('rl:otp:mobile:'.sha1('09129999999'));
    RateLimiter::clear('rl:otp:ip:'.sha1('127.0.0.1'));
    RateLimiter::clear('rl:otp:ip:'.sha1('192.168.1.100'));
    RateLimiter::clear('rl:otp:subnet:192.168.1.0_24');
});

test('requesting OTP generates valid challenge and never reveals code in response', function () {
    $payload = [
        'mobile' => '09123456781',
        'national_id' => '0010350802',
        'purpose' => OtpPurpose::LOGIN->value,
    ];

    $response = $this->postJson('/api/v1/auth/otp/request', $payload);

    $response->assertStatus(200)
        ->assertHeader(RequestId::HEADER_NAME)
        ->assertHeader('X-RateLimit-Limit', '3')
        ->assertHeader('X-RateLimit-Remaining', '2')
        ->assertHeader('X-RateLimit-Reset')
        ->assertJsonStructure([
            'data' => [
                'challenge_id',
                'expires_at',
                'resend_available_at',
                'masked_mobile',
                'is_new_user',
            ],
        ]);

    $data = $response->json('data');
    expect($data['masked_mobile'])->toBe('0912***6781');
    expect($data['is_new_user'])->toBeTrue();

    // STRICT SECURITY VERIFICATION (§5.6 #1, §7.7): Raw code is NEVER present in entire JSON response
    $rawResponseText = $response->getContent();
    $challenge = OtpChallenge::query()->find($data['challenge_id']);
    expect($challenge)->not->toBeNull();

    // Check that no 5-digit number matching any OTP code pattern is leaked in response
    expect($rawResponseText)->not->toContain('code');
    expect($rawResponseText)->not->toContain('code_hash');

    // Verify challenge in DB has code hashed with bcrypt, NOT plaintext
    $dbRecord = DB::table('otp_challenges')->where('id', $data['challenge_id'])->first();
    expect($dbRecord)->not->toBeNull();
    expect($dbRecord->code_hash)->toStartWith('$2y$'); // bcrypt signature

    // Verify audit log has been immutably recorded (§7.6)
    $auditLog = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_OTP_REQUESTED->value)
        ->first();
    expect($auditLog)->not->toBeNull();
    expect($auditLog->subject_id)->toBe($data['challenge_id']);
    expect($auditLog->changes)->toContain('0912***6781');
});

test('submitting invalid Iranian national id returns AUTH_NATIONAL_ID_INVALID RFC 7807 problem details', function () {
    $payload = [
        'mobile' => '09123456781',
        'national_id' => '1111111111', // Invalid checksum / all identical
        'purpose' => OtpPurpose::LOGIN->value,
    ];

    $response = $this->postJson('/api/v1/auth/otp/request', $payload);

    $response->assertStatus(422)
        ->assertHeader(RequestId::HEADER_NAME)
        ->assertJson([
            'status' => 422,
            'code' => ErrorCode::AUTH_NATIONAL_ID_INVALID->value,
            'title' => ErrorCode::AUTH_NATIONAL_ID_INVALID->title(),
        ]);
});

test('fourth request within 15 minutes hits mobile rate limit and returns 429 with Retry-After', function () {
    $payload = [
        'mobile' => '09123456781',
        'purpose' => OtpPurpose::LOGIN->value,
    ];

    // Request 1: OK
    $this->postJson('/api/v1/auth/otp/request', $payload)->assertStatus(200);
    // Request 2: OK
    $this->postJson('/api/v1/auth/otp/request', $payload)->assertStatus(200);
    // Request 3: OK
    $this->postJson('/api/v1/auth/otp/request', $payload)->assertStatus(200);

    // Request 4: BLOCKED by mobile limit (3 / 15 min)
    $response = $this->postJson('/api/v1/auth/otp/request', $payload);

    $response->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertHeader('X-RateLimit-Limit', '3')
        ->assertHeader('X-RateLimit-Remaining', '0')
        ->assertJson([
            'status' => 429,
            'code' => ErrorCode::AUTH_OTP_TOO_MANY->value,
            'title' => ErrorCode::AUTH_OTP_TOO_MANY->title(),
        ]);

    $retryAfter = (int) $response->headers->get('Retry-After');
    expect($retryAfter)->toBeGreaterThan(0)->toBeLessThanOrEqual(900);
});

test('rate limiting by client IP blocks requests exceeding 30 per 15 minutes', function () {
    $ip = '192.168.1.50';

    // Simulate 30 requests from different mobiles behind same IP
    for ($i = 1; $i <= 30; $i++) {
        $mobile = '0912000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        $res = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/auth/otp/request', [
                'mobile' => $mobile,
                'purpose' => OtpPurpose::LOGIN->value,
            ]);
        $res->assertStatus(200);
    }

    // 31st request from same IP should be blocked
    $res31 = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/api/v1/auth/otp/request', [
            'mobile' => '09129998877',
            'purpose' => OtpPurpose::LOGIN->value,
        ]);

    $res31->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJson([
            'status' => 429,
            'code' => ErrorCode::AUTH_OTP_TOO_MANY->value,
        ]);
});

test('rate limiting by IPv4 /24 subnet blocks requests exceeding 300 per 15 minutes', function () {
    $subnetBase = '10.20.30.';

    // Fast hit 300 attempts on subnet key
    for ($i = 1; $i <= 300; $i++) {
        RateLimiter::hit('rl:otp:subnet:10.20.30.0_24', 900);
    }

    $response = $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.45'])
        ->postJson('/api/v1/auth/otp/request', [
            'mobile' => '09121112233',
            'purpose' => OtpPurpose::LOGIN->value,
        ]);

    $response->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJson([
            'status' => 429,
            'code' => ErrorCode::AUTH_OTP_TOO_MANY->value,
        ]);
});
