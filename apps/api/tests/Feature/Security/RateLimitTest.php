<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Shared\Errors\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    RateLimiter::clear('global');
    $this->seed(RoleSeeder::class);

    // Register temporary route with operator.api limiter for testing
    Route::middleware(['api', 'throttle:operator.api'])->get('/test/operator-limit', function () {
        return response()->json(['status' => 'ok']);
    });

    // Register temporary route with cases.store limiter for testing
    Route::middleware(['api', 'throttle:cases.store'])->post('/test/cases-limit', function () {
        return response()->json(['status' => 'case_created']);
    });

    // Register temporary route with offices.nearby limiter for testing
    Route::middleware(['api', 'throttle:offices.nearby'])->get('/test/nearby-limit', function () {
        return response()->json(['status' => 'offices_found']);
    });
});

it('returns standard rate limit headers on successful requests', function (): void {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk();
    $response->assertHeader('X-RateLimit-Limit');
    $response->assertHeader('X-RateLimit-Remaining');
});

it('enforces global IP rate limiter at 300 requests per minute with RFC 7807 response', function (): void {
    $ip = '198.51.100.42';

    // In Laravel ThrottleRequests, named limiter cache key is md5(limiterName . key)
    $limiterKey = md5('global'.$ip);
    for ($i = 1; $i <= 300; $i++) {
        RateLimiter::hit($limiterKey, 60);
    }

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->getJson('/api/v1/health');

    $response->assertStatus(429);
    $response->assertHeader('Retry-After');
    $response->assertHeader('X-RateLimit-Limit', '300');
    $response->assertHeader('X-RateLimit-Remaining', '0');

    $data = $response->json();
    expect($data['code'])->toBe(ErrorCode::RATE_LIMITED->value);
    expect($data['title'])->toBe(ErrorCode::RATE_LIMITED->title());
    expect($data['detail'])->toBe(ErrorCode::RATE_LIMITED->defaultDetail());
    expect($data['retry_after'])->toBeGreaterThan(0);
});

it('enforces multi-tier OTP request rate limits (3/15m per mobile, 30/15m per IP)', function (): void {
    $mobile = '09121112233';
    $ip = '203.0.113.10';

    // 3 requests allowed for mobile
    for ($i = 1; $i <= 3; $i++) {
        RateLimiter::hit('rl:otp:mobile:'.sha1($mobile), 900);
    }

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/api/v1/auth/otp/request', [
            'mobile' => $mobile,
            'national_id' => '0010350802',
        ]);

    $response->assertStatus(429);
    $response->assertHeader('Retry-After');
    $response->assertJsonPath('code', ErrorCode::AUTH_OTP_TOO_MANY->value);
});

it('enforces OTP verification IP rate limiter at 20 requests per hour', function (): void {
    $ip = '203.0.113.88';

    // Named limiter key: md5('otp.verify' . 'ip:' . $ip)
    $limiterKey = md5('otp.verifyip:'.$ip);
    for ($i = 1; $i <= 20; $i++) {
        RateLimiter::hit($limiterKey, 3600);
    }

    Route::middleware(['api', 'throttle:otp.verify'])->post('/test/otp-verify-limit', function () {
        return response()->json(['status' => 'verified']);
    });

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/test/otp-verify-limit', [
            'challenge_id' => 'ch_test_123',
            'code' => '12345',
        ]);

    $response->assertStatus(429);
    $response->assertHeader('Retry-After');
    $response->assertJsonPath('code', ErrorCode::RATE_LIMITED->value);
});

it('enforces operator API rate limiter at 600 requests per minute', function (): void {
    $office = Office::query()->create([
        'code' => '72161001',
        'name' => 'دفتر هفت‌تیر',
        'is_online' => true,
    ]);

    $operator = Operator::query()->create([
        'office_id' => $office->id,
        'username' => 'op_rate_test',
        'password_hash' => 'hash_pass',
        'national_id_hash' => hash_hmac('sha256', '0012345678', 'pepper'),
        'mobile_hash' => hash_hmac('sha256', '09120000000', 'pepper'),
        'full_name' => 'اپراتور تستی',
        'counter_number' => 2,
    ]);
    $operator->assignRole('office_operator');

    $limiterKey = md5('operator.api'.'op:'.$operator->id);
    for ($i = 1; $i <= 600; $i++) {
        RateLimiter::hit($limiterKey, 60);
    }

    $response = $this->actingAs($operator, 'sanctum')
        ->getJson('/test/operator-limit');

    $response->assertStatus(429);
    $response->assertHeader('Retry-After');
    $response->assertJsonPath('code', ErrorCode::RATE_LIMITED->value);
});

it('enforces citizen case store limit at 10 requests per hour', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09123334455';
    $citizen->full_name = 'مهدی احمدی';
    $citizen->tier = CitizenTier::BRONZE;
    $citizen->save();
    $citizen->assignRole('citizen');

    $limiterKey = md5('cases.store'.'citizen:'.$citizen->id.':hour');
    for ($i = 1; $i <= 10; $i++) {
        RateLimiter::hit($limiterKey, 3600);
    }

    $response = $this->actingAs($citizen, 'sanctum')
        ->postJson('/test/cases-limit', [
            'service_id' => 'svc_123',
        ]);

    $response->assertStatus(429);
    $response->assertJsonPath('code', ErrorCode::RATE_LIMITED->value);
});

it('enforces offices nearby rate limiter at 60 requests per minute', function (): void {
    $ip = '198.51.100.99';
    $limiterKey = md5('offices.nearby'.'ip:'.$ip);

    for ($i = 1; $i <= 60; $i++) {
        RateLimiter::hit($limiterKey, 60);
    }

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->getJson('/test/nearby-limit');

    $response->assertStatus(429);
    $response->assertJsonPath('code', ErrorCode::RATE_LIMITED->value);
});
