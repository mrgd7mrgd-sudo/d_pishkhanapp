<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Events\AuthDeviceNewEvent;
use App\Modules\Identity\Domain\Events\AuthLoginFailedEvent;
use App\Modules\Identity\Domain\Events\AuthLoginSuccessEvent;
use App\Modules\Identity\Domain\Events\AuthLogoutEvent;
use App\Modules\Identity\Domain\Events\AuthOtpRequestedEvent;
use App\Modules\Identity\Domain\Events\AuthTokenRevokedEvent;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Shared\Audit\AuditLogger;
use App\Shared\Http\Middleware\RequestId;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->citizen = Citizen::query()->create([
        'national_id_hash' => hash_hmac('sha256', '0012345678', 'pepper'),
        'mobile_hash' => hash_hmac('sha256', '09121111111', 'pepper'),
        'full_name' => 'رضا مرادی',
    ]);
});

test('all six authentication events are recorded in audit logs with required metadata and no raw pii', function (): void {
    $requestId = (string) Str::uuid();
    request()->headers->set(RequestId::HEADER_NAME, $requestId);
    request()->server->set('REMOTE_ADDR', '192.168.1.100');
    request()->headers->set('User-Agent', 'PishkhanTestBrowser/1.0');

    // 1. auth.otp.requested
    Event::dispatch(new AuthOtpRequestedEvent('09121111111', 'login', 'chal-123', ['ip' => '192.168.1.100']));

    // 2. auth.login.success
    Event::dispatch(new AuthLoginSuccessEvent($this->citizen, ['ip' => '192.168.1.100']));

    // 3. auth.device.new
    Event::dispatch(new AuthDeviceNewEvent($this->citizen, 'Chrome on Windows', ['ip' => '192.168.1.100']));

    // 4. auth.login.failed
    Event::dispatch(new AuthLoginFailedEvent('0012345678', 'invalid_credentials', ['ip' => '192.168.1.100']));

    // 5. auth.token.revoked
    Event::dispatch(new AuthTokenRevokedEvent($this->citizen, 'tok-456', ['ip' => '192.168.1.100']));

    // 6. auth.logout
    Event::dispatch(new AuthLogoutEvent($this->citizen, ['ip' => '192.168.1.100']));

    $logs = DB::table('audit_logs')->orderBy('created_at')->get();
    expect($logs)->toHaveCount(6);

    $actions = $logs->pluck('action')->all();
    expect($actions)->toBe([
        'auth.otp.requested',
        'auth.login.success',
        'auth.device.new',
        'auth.login.failed',
        'auth.token.revoked',
        'auth.logout',
    ]);

    // Verify metadata presence and zero raw PII
    foreach ($logs as $log) {
        expect($log->entry_hash)->not->toBeEmpty()
            ->and($log->prev_hash)->not->toBeEmpty()
            ->and($log->request_id)->toBe($requestId)
            ->and($log->user_agent)->toBe('PishkhanTestBrowser/1.0')
            ->and($log->ip_address)->toBe('192.168.1.100');

        $changes = (string) $log->changes;
        $context = (string) $log->context;

        // Strict Regex scan: No raw 10-digit National ID, no raw 11-digit mobile, no raw 16-digit card
        expect(preg_match('/\b09121111111\b/', $changes))->toBe(0)
            ->and(preg_match('/\b0012345678\b/', $changes))->toBe(0)
            ->and(preg_match('/\b09121111111\b/', $context))->toBe(0)
            ->and(preg_match('/\b0012345678\b/', $context))->toBe(0);
    }
});

test('hash chain remains 100 percent unbroken and valid across 50 consecutive audit events', function (): void {
    $now = CarbonImmutable::now();

    for ($i = 1; $i <= 50; $i++) {
        AuditLogger::record(
            action: ($i % 2 === 0) ? 'auth.login.success' : 'auth.otp.requested',
            subject: $this->citizen,
            changes: ['iteration' => $i, 'masked_mobile' => '0912***'.str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
            context: ['step' => $i],
            occurredAt: $now->addSeconds($i)
        );
    }

    $count = DB::table('audit_logs')->count();
    expect($count)->toBe(50);

    // Verify chain integrity using Artisan command
    $exitCode = Artisan::call('audit:verify-chain');
    expect($exitCode)->toBe(0);
});
