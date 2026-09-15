<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\Payments\Domain\Enums\PaymentGateway;
use App\Modules\Payments\Domain\Enums\PaymentIntentStatus;
use App\Modules\Payments\Domain\Enums\PayoutStatus;
use App\Modules\Payments\Domain\Models\PaymentIntent;
use App\Modules\Payments\Domain\Models\Payout;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createTestCitizenForPayment(): Citizen
{
    return Citizen::create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', Str::random(12)),
        'mobile_encrypted' => 'enc:'.Str::random(11),
        'national_id_hash' => hash('sha256', Str::random(10)),
        'national_id_encrypted' => 'enc:0010350802',
        'full_name' => 'کاربر تست پرداخت',
        'tier' => 'bronze',
        'profile_completed' => true,
    ]);
}

function createTestOfficeForPayout(): Office
{
    return Office::create([
        'id' => (string) Str::uuid(),
        'code' => 'OFF-'.strtoupper(Str::random(4)),
        'name' => 'دفتر تست مالی',
        'manager_name' => 'مدیر دفتر مالی',
        'membership_status' => 'registered_online',
        'is_online' => true,
        'province_code' => 'THR',
        'address' => 'تهران، خیابان انقلاب',
        'phone' => '02166998877',
        'active_counters' => 3,
        'current_waiting_queue' => 0,
    ]);
}

test('payment_intents table stores valid payment intents with 6 allowed statuses (TASK-084-T)', function (): void {
    $citizen = createTestCitizenForPayment();

    foreach (PaymentIntentStatus::cases() as $status) {
        $intent = PaymentIntent::create([
            'citizen_id' => $citizen->id,
            'amount_rials' => 1000000,
            'gateway' => PaymentGateway::ZARINPAL,
            'authority' => 'AUTH-'.Str::random(16),
            'status' => $status,
            'expires_at' => Carbon::now()->addMinutes(15),
            'metadata' => ['service_id' => 'srv-test'],
        ]);

        expect($intent->status)->toBe($status)
            ->and($intent->amount_rials)->toBe(1000000)
            ->and($intent->gateway)->toBe(PaymentGateway::ZARINPAL)
            ->and($intent->citizen_id)->toBe($citizen->id);
    }
});

test('full card number is never stored in raw form; mutator guarantees masked PAN (TASK-084-T, §7.7)', function (): void {
    $citizen = createTestCitizenForPayment();

    // 1. Assign raw 16-digit PAN
    $intent = PaymentIntent::create([
        'citizen_id' => $citizen->id,
        'amount_rials' => 500000,
        'gateway' => PaymentGateway::ZARINPAL,
        'authority' => 'AUTH-PAN-'.Str::random(12),
        'status' => PaymentIntentStatus::PAID,
        'card_pan_masked' => '6037991234567890', // raw 16 digits
        'expires_at' => Carbon::now()->addMinutes(15),
    ]);

    // Mutator must have masked the middle 6 digits
    expect($intent->card_pan_masked)->toBe('603799******7890');

    // Fresh retrieval from database
    $fresh = PaymentIntent::find($intent->id);
    expect($fresh)->not->toBeNull()
        ->and($fresh->card_pan_masked)->toBe('603799******7890');

    // Direct database query inspection to ensure raw PAN never reached the database
    $rawDbValue = DB::table('payment_intents')->where('id', $intent->id)->value('card_pan_masked');
    expect($rawDbValue)->toBe('603799******7890')
        ->and($rawDbValue)->not->toContain('123456');

    // 2. Pre-masked PAN with asterisks is preserved
    $intent2 = PaymentIntent::create([
        'citizen_id' => $citizen->id,
        'amount_rials' => 300000,
        'gateway' => PaymentGateway::ZIBAL,
        'authority' => 'AUTH-PAN-2-'.Str::random(12),
        'status' => PaymentIntentStatus::PAID,
        'card_pan_masked' => '502229******4321',
        'expires_at' => Carbon::now()->addMinutes(15),
    ]);

    expect($intent2->card_pan_masked)->toBe('502229******4321');
});

test('duplicate authority within the same gateway violates unique index idx_intents_auth (TASK-084-T, §6.4)', function (): void {
    $citizen = createTestCitizenForPayment();
    $authority = 'DUPLICATE-AUTH-KEY-100';

    PaymentIntent::create([
        'citizen_id' => $citizen->id,
        'amount_rials' => 2000000,
        'gateway' => PaymentGateway::ZARINPAL,
        'authority' => $authority,
        'status' => PaymentIntentStatus::CREATED,
        'expires_at' => Carbon::now()->addMinutes(15),
    ]);

    // Second attempt with exact same gateway and authority must throw QueryException
    expect(function () use ($citizen, $authority): void {
        PaymentIntent::create([
            'citizen_id' => $citizen->id,
            'amount_rials' => 1500000,
            'gateway' => PaymentGateway::ZARINPAL,
            'authority' => $authority,
            'status' => PaymentIntentStatus::CREATED,
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);
    })->toThrow(QueryException::class);

    // But same authority with a different gateway is permitted
    $intentOtherGateway = PaymentIntent::create([
        'citizen_id' => $citizen->id,
        'amount_rials' => 1500000,
        'gateway' => PaymentGateway::ZIBAL,
        'authority' => $authority,
        'status' => PaymentIntentStatus::CREATED,
        'expires_at' => Carbon::now()->addMinutes(15),
    ]);

    expect($intentOtherGateway->id)->not->toBeEmpty();
});

test('payouts table records office settlements with proper foreign keys and status lifecycle (TASK-084-T, §8.2)', function (): void {
    $office = createTestOfficeForPayout();

    foreach (PayoutStatus::cases() as $status) {
        $payout = Payout::create([
            'office_id' => $office->id,
            'amount_rials' => 4500000,
            'status' => $status,
            'period_start' => Carbon::yesterday()->startOfDay(),
            'period_end' => Carbon::yesterday()->endOfDay(),
            'total_cases_count' => 12,
            'reference_number' => 'SETTLE-'.Str::random(10),
            'generated_at' => Carbon::now(),
        ]);

        expect($payout->status)->toBe($status)
            ->and($payout->office_id)->toBe($office->id)
            ->and($payout->amount_rials)->toBe(4500000)
            ->and($payout->total_cases_count)->toBe(12)
            ->and($payout->office->id)->toBe($office->id);
    }
});
