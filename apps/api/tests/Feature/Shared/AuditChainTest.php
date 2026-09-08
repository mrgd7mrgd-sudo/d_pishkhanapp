<?php

declare(strict_types=1);

use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('verify audit chain command verifies 100 sequential audit log records cleanly', function () {
    $now = CarbonImmutable::now();

    for ($i = 1; $i <= 100; $i++) {
        AuditLogger::record(
            action: AuditableAction::DOCUMENT_VIEWED,
            subject: 'CaseDocument:test-doc-'.$i,
            changes: ['iteration' => $i, 'status' => 'reviewed'],
            context: ['operator_id' => 'op-007', 'reason' => 'verification'],
            occurredAt: $now->addSeconds($i)
        );
    }

    $count = DB::table('audit_logs')->count();
    expect($count)->toBe(100);

    // Run verification command
    $exitCode = Artisan::call('audit:verify-chain');
    expect($exitCode)->toBe(0);

    $output = Artisan::output();
    expect($output)->toContain('SUCCESS: Cryptographic hash chain verified cleanly across 100 records');
});

test('verify audit chain command detects tampering in changes payload', function () {
    $now = CarbonImmutable::now();

    // Create 10 valid chained entries
    for ($i = 1; $i <= 10; $i++) {
        AuditLogger::record(
            action: AuditableAction::CASE_TRANSITION,
            subject: 'Case:case-uuid-999',
            changes: ['step' => $i],
            occurredAt: $now->addSeconds($i)
        );
    }

    // Command passes before tampering
    expect(Artisan::call('audit:verify-chain'))->toBe(0);

    // Tamper directly with entry #5 changes payload
    $fifthRecord = DB::table('audit_logs')
        ->orderBy('occurred_at', 'asc')
        ->skip(4)
        ->first();

    expect($fifthRecord)->not->toBeNull();

    DB::table('audit_logs')
        ->where('id', $fifthRecord->id)
        ->update([
            'changes' => json_encode(['step' => 5, 'tampered' => true]),
        ]);

    // Command must detect tampering and return failure code
    $exitCode = Artisan::call('audit:verify-chain');
    expect($exitCode)->toBe(1);

    $output = Artisan::output();
    expect($output)->toContain('TAMPER DETECTED');
    expect($output)->toContain($fifthRecord->id);
});

test('verify audit chain command detects tampering in prev_hash breaking chain', function () {
    $now = CarbonImmutable::now();

    for ($i = 1; $i <= 5; $i++) {
        AuditLogger::record(
            action: AuditableAction::AUTH_LOGIN_SUCCESS,
            subject: 'User:usr-'.$i,
            occurredAt: $now->addSeconds($i)
        );
    }

    // Tamper with prev_hash of 3rd record
    $thirdRecord = DB::table('audit_logs')
        ->orderBy('occurred_at', 'asc')
        ->skip(2)
        ->first();

    expect($thirdRecord)->not->toBeNull();

    DB::table('audit_logs')
        ->where('id', $thirdRecord->id)
        ->update([
            'prev_hash' => 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff',
        ]);

    $exitCode = Artisan::call('audit:verify-chain');
    expect($exitCode)->toBe(1);

    $output = Artisan::output();
    expect($output)->toContain('CHAIN BROKEN');
    expect($output)->toContain($thirdRecord->id);
});
