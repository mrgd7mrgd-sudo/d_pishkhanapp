<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Actions\VerifyOtpAction;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Shared\Audit\AuditableAction;
use App\Shared\Http\Middleware\RequestId;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('logout revokes current PAT and creates audit record', function () {
    $this->seed(ProvinceSeeder::class);

    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09121112233';
    $citizen->full_name = 'تست خروج کاربر';
    $citizen->tier = CitizenTier::BRONZE;
    $citizen->province_code = 'THR';
    $citizen->save();

    $token = $citizen->createToken('Desktop Browser', VerifyOtpAction::CITIZEN_ABILITIES);
    $plainToken = $token->plainTextToken;
    $tokenId = $token->accessToken->id;

    // Verify token exists in database
    expect(DB::table('personal_access_tokens')->where('id', $tokenId)->exists())->toBeTrue();

    // Call POST /api/v1/auth/logout with Bearer token
    $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertHeader(RequestId::HEADER_NAME)
        ->assertJson(['message' => 'خروج با موفقیت انجام شد.']);

    // Verify token was deleted from database
    expect(DB::table('personal_access_tokens')->where('id', $tokenId)->exists())->toBeFalse();

    // Verify audit log recorded auth.logout
    $auditLog = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_LOGOUT->value)
        ->where('subject_id', $citizen->id)
        ->first();

    expect($auditLog)->not->toBeNull();
    $changes = json_decode((string) $auditLog->changes, true);
    expect($changes['token_id'])->toBe($tokenId);
});

test('revoking a specific device deletes only that token and leaves others active', function () {
    $citizen = new Citizen;
    $citizen->mobile = '09123334455';
    $citizen->full_name = 'کاربر چند دستگاهه';
    $citizen->tier = CitizenTier::BRONZE;
    $citizen->save();

    // Create 3 tokens
    $device1 = $citizen->createToken('Mobile · Android');
    $device2 = $citizen->createToken('Desktop · Chrome');
    $device3 = $citizen->createToken('Tablet · iPad');

    // Authenticated on device 1
    $response = $this->withHeader('Authorization', 'Bearer '.$device1->plainTextToken)
        ->getJson('/api/v1/auth/devices');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');

    $devices = $response->json('data');
    expect(collect($devices)->firstWhere('name', 'Mobile · Android')['is_current'])->toBeTrue();
    expect(collect($devices)->firstWhere('name', 'Desktop · Chrome')['is_current'])->toBeFalse();

    // Delete device 2
    $delResponse = $this->withHeader('Authorization', 'Bearer '.$device1->plainTextToken)
        ->deleteJson('/api/v1/auth/devices/'.$device2->accessToken->id);

    $delResponse->assertStatus(200)
        ->assertJson(['message' => 'نشست دستگاه با موفقیت خاتمه یافت.']);

    // Check database
    expect(DB::table('personal_access_tokens')->where('id', $device2->accessToken->id)->exists())->toBeFalse();
    expect(DB::table('personal_access_tokens')->where('id', $device1->accessToken->id)->exists())->toBeTrue();
    expect(DB::table('personal_access_tokens')->where('id', $device3->accessToken->id)->exists())->toBeTrue();

    // Verify audit log for token revocation
    $audit = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_TOKEN_REVOKED->value)
        ->first();
    expect($audit)->not->toBeNull();
});

test('token with less than 24 hours remaining is automatically extended to 7 days', function () {
    $citizen = new Citizen;
    $citizen->mobile = '09125556677';
    $citizen->full_name = 'کاربر تمدید خودکار';
    $citizen->tier = CitizenTier::SILVER;
    $citizen->save();

    // Create token expiring in 12 hours (< 24 hours)
    $expiringSoon = CarbonImmutable::now()->addHours(12);
    $token = $citizen->createToken('PWA Mobile', VerifyOtpAction::CITIZEN_ABILITIES, $expiringSoon);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertHeader('X-Token-Refreshed', 'true')
        ->assertHeader('X-Token-Expires-At');

    /** @var PersonalAccessToken $refreshedToken */
    $refreshedToken = DB::table('personal_access_tokens')->where('id', $token->accessToken->id)->first();
    $newExpiresAt = CarbonImmutable::parse($refreshedToken->expires_at);

    // New expiry should be ~7 days from now
    $expectedExpiry = CarbonImmutable::now()->addDays(7);
    expect(abs($newExpiresAt->diffInSeconds($expectedExpiry)))->toBeLessThan(5);
});

test('logging in from a new device name records auth.device.new audit notification', function () {
    $mobile = '09127778899';
    $code = '55443';

    $citizen = new Citizen;
    $citizen->mobile = $mobile;
    $citizen->full_name = 'کاربر اعلان دستگاه جدید';
    $citizen->tier = CitizenTier::BRONZE;
    $citizen->save();

    // 1. First login with Device A
    $ch1 = OtpChallenge::createChallenge($mobile, $code, OtpPurpose::LOGIN);
    $this->postJson('/api/v1/auth/otp/verify', [
        'challenge_id' => $ch1->id,
        'code' => $code,
        'device_name' => 'Device A · iPhone 15',
    ])->assertStatus(200);

    // Verify first login records auth.device.new
    $audit1 = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_DEVICE_NEW->value)
        ->where('subject_id', $citizen->id)
        ->first();
    expect($audit1)->not->toBeNull();

    // 2. Second login with SAME Device A -> should NOT trigger another auth.device.new
    DB::table('audit_logs')->truncate();
    $ch2 = OtpChallenge::createChallenge($mobile, $code, OtpPurpose::LOGIN);
    $this->postJson('/api/v1/auth/otp/verify', [
        'challenge_id' => $ch2->id,
        'code' => $code,
        'device_name' => 'Device A · iPhone 15',
    ])->assertStatus(200);

    $auditSameDevice = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_DEVICE_NEW->value)
        ->first();
    expect($auditSameDevice)->toBeNull();

    // 3. Third login with NEW Device B -> triggers auth.device.new
    $ch3 = OtpChallenge::createChallenge($mobile, $code, OtpPurpose::LOGIN);
    $this->postJson('/api/v1/auth/otp/verify', [
        'challenge_id' => $ch3->id,
        'code' => $code,
        'device_name' => 'Device B · MacBook Air',
    ])->assertStatus(200);

    $auditNewDevice = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_DEVICE_NEW->value)
        ->first();
    expect($auditNewDevice)->not->toBeNull();
    $changes = json_decode((string) $auditNewDevice->changes, true);
    expect($changes['device_name'])->toBe('Device B · MacBook Air');
});

test('POST /auth/refresh converts session to 15-minute short-lived token for Service Worker', function () {
    $citizen = new Citizen;
    $citizen->mobile = '09128889900';
    $citizen->full_name = 'کاربر سرویس ورکر';
    $citizen->tier = CitizenTier::GOLD;
    $citizen->save();

    $mainToken = $citizen->createToken('Main App Session');

    $response = $this->withHeader('Authorization', 'Bearer '.$mainToken->plainTextToken)
        ->postJson('/api/v1/auth/refresh');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'token',
                'token_type',
                'expires_at',
                'ttl_seconds',
            ],
        ]);

    $data = $response->json('data');
    expect($data['ttl_seconds'])->toBe(900);
    expect($data['token_type'])->toBe('Bearer');

    $expiresAt = CarbonImmutable::parse($data['expires_at']);
    $expected = CarbonImmutable::now()->addMinutes(15);
    expect(abs($expiresAt->diffInSeconds($expected)))->toBeLessThan(5);
});
