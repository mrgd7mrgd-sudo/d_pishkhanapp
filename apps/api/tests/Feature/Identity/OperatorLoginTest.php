<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Shared\Audit\AuditableAction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('operator password uses Argon2id hashing algorithm', function () {
    $password = 'SecurePassword123!';
    $hash = Hash::make($password);

    // Verify Argon2id prefix
    expect($hash)->toStartWith('$');
    expect(password_get_info($hash)['algoName'])->toBe('argon2id');
    expect(Hash::check($password, $hash))->toBeTrue();
});

test('operator login dispatches OTP on valid office code, username, and password', function () {
    $office = Office::create([
        'code' => '1001',
        'name' => 'دفتر پیشخوان مرکزی ۱۰۱',
        'is_online' => true,
    ]);

    $operator = new Operator;
    $operator->office_id = $office->id;
    $operator->username = 'reza_operator';
    $operator->password_hash = Hash::make('StrongPassword2026!');
    $operator->full_name = 'رضا کمالی';
    $operator->national_id = '0081234567';
    $operator->mobile = '09121112233';
    $operator->role = OperatorRole::OPERATOR;
    $operator->counter_number = 3;
    $operator->is_active = true;
    $operator->save();

    $response = $this->postJson('/api/v1/operator/auth/login', [
        'office_code' => '1001',
        'username' => 'reza_operator',
        'password' => 'StrongPassword2026!',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'challenge_id',
                'expires_at',
                'resend_available_at',
                'masked_mobile',
            ],
        ]);

    $data = $response->json('data');
    expect($data['masked_mobile'])->toBe('0912***2233');

    // Verify challenge created in database
    $challenge = OtpChallenge::find($data['challenge_id']);
    expect($challenge)->not->toBeNull();
    expect($challenge->attempts)->toBe(0);

    // Verify audit log recorded
    $audit = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_OTP_REQUESTED->value)
        ->orderByDesc('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->subject_id)->toBe($challenge->id);
});

test('operator login rejects incorrect password or wrong office code', function () {
    $office = Office::create([
        'code' => '1002',
        'name' => 'دفتر پیشخوان رسالت',
        'is_online' => true,
    ]);

    $operator = new Operator;
    $operator->office_id = $office->id;
    $operator->username = 'ali_manager';
    $operator->password_hash = Hash::make('AdminPass12345!');
    $operator->full_name = 'علی رضایی';
    $operator->national_id = '0071234567';
    $operator->mobile = '09129998877';
    $operator->role = OperatorRole::MANAGER;
    $operator->save();

    // Wrong password
    $resWrongPass = $this->postJson('/api/v1/operator/auth/login', [
        'office_code' => '1002',
        'username' => 'ali_manager',
        'password' => 'WrongPassword123!',
    ]);
    $resWrongPass->assertStatus(401)
        ->assertJsonPath('code', 'AUTH_INVALID_CREDENTIALS');

    // Wrong office code
    $resWrongOffice = $this->postJson('/api/v1/operator/auth/login', [
        'office_code' => '9999',
        'username' => 'ali_manager',
        'password' => 'AdminPass12345!',
    ]);
    $resWrongOffice->assertStatus(401)
        ->assertJsonPath('code', 'AUTH_INVALID_CREDENTIALS');
});

test('operator login rejects request from outside configured IP ranges', function () {
    $office = Office::create([
        'code' => '1003',
        'name' => 'دفتر پیشخوان فاطمی',
        'is_online' => true,
    ]);

    $operator = new Operator;
    $operator->office_id = $office->id;
    $operator->username = 'sara_operator';
    $operator->password_hash = Hash::make('PasswordSara123!');
    $operator->full_name = 'سارا محمدی';
    $operator->national_id = '0061234567';
    $operator->mobile = '09124445566';
    $operator->allowed_ip_ranges = '192.168.1.100, 10.0.0.0/24';
    $operator->save();

    // Request from non-allowed IP
    $responseBlocked = $this->withServerVariables(['REMOTE_ADDR' => '172.16.0.5'])
        ->postJson('/api/v1/operator/auth/login', [
            'office_code' => '1003',
            'username' => 'sara_operator',
            'password' => 'PasswordSara123!',
        ]);

    $responseBlocked->assertStatus(403)
        ->assertJsonPath('code', 'AUTH_IP_RESTRICTED');

    // Request from allowed single IP
    $responseAllowedSingle = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
        ->postJson('/api/v1/operator/auth/login', [
            'office_code' => '1003',
            'username' => 'sara_operator',
            'password' => 'PasswordSara123!',
        ]);

    $responseAllowedSingle->assertStatus(200);

    // Request from allowed CIDR range
    $responseAllowedCidr = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.42'])
        ->postJson('/api/v1/operator/auth/login', [
            'office_code' => '1003',
            'username' => 'sara_operator',
            'password' => 'PasswordSara123!',
        ]);

    $responseAllowedCidr->assertStatus(200);
});

test('operator verify OTP completes login, sets session, and returns profile', function () {
    $office = Office::create([
        'code' => '1004',
        'name' => 'دفتر پیشخوان سعادت‌آباد',
        'is_online' => true,
    ]);

    $mobile = '09127778899';
    $operator = new Operator;
    $operator->office_id = $office->id;
    $operator->username = 'mohammad_op';
    $operator->password_hash = Hash::make('MohammadPass123!');
    $operator->full_name = 'محمد احمدی';
    $operator->national_id = '0051234567';
    $operator->mobile = $mobile;
    $operator->role = OperatorRole::OPERATOR;
    $operator->counter_number = 2;
    $operator->save();

    $code = '54321';
    $challenge = OtpChallenge::createChallenge(
        mobile: $mobile,
        rawCode: $code,
        purpose: OtpPurpose::LOGIN,
        ipAddress: '127.0.0.1',
        ttlSeconds: 120
    );

    $response = $this->postJson('/api/v1/operator/auth/verify-otp', [
        'challenge_id' => $challenge->id,
        'code' => $code,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'operator' => [
                    'id',
                    'office_id',
                    'username',
                    'full_name',
                    'national_id_masked',
                    'mobile_masked',
                    'role',
                    'counter_number',
                    'is_active',
                    'last_login_at',
                ],
                'session_id',
            ],
        ]);

    $data = $response->json('data.operator');
    expect($data['username'])->toBe('mohammad_op');
    expect($data['national_id_masked'])->toBe('005****567');
    expect($data['mobile_masked'])->toBe('0912***8899');
    expect($data['counter_number'])->toBe(2);

    // Operator last_login_at must be updated
    $operator->refresh();
    expect($operator->last_login_at)->not->toBeNull();

    // Verify audit log recorded
    $audit = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_LOGIN_SUCCESS->value)
        ->where('subject_id', $operator->id)
        ->first();

    expect($audit)->not->toBeNull();
});

test('operator logout terminates session and invalidates access', function () {
    $office = Office::create([
        'code' => '1005',
        'name' => 'دفتر پیشخوان ونک',
        'is_online' => true,
    ]);

    $operator = new Operator;
    $operator->office_id = $office->id;
    $operator->username = 'hossein_op';
    $operator->password_hash = Hash::make('HosseinPassword123!');
    $operator->full_name = 'حسین کاظمی';
    $operator->national_id = '0041234567';
    $operator->mobile = '09123334455';
    $operator->save();

    // Login operator via actingAs with operator guard
    $response = $this->actingAs($operator, 'operator')
        ->withSession(['last_activity' => CarbonImmutable::now()->timestamp])
        ->getJson('/api/v1/operator/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.username', 'hossein_op');

    // Logout
    $logoutResponse = $this->actingAs($operator, 'operator')
        ->postJson('/api/v1/operator/auth/logout');

    $logoutResponse->assertStatus(200);

    // Verify audit log
    $audit = DB::table('audit_logs')
        ->where('action', AuditableAction::AUTH_LOGOUT->value)
        ->where('actor_id', $operator->id)
        ->first();

    expect($audit)->not->toBeNull();
});
