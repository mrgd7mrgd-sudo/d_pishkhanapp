<?php

declare(strict_types=1);

use App\Modules\Consultation\Domain\Enums\AdvisorApplicationStatus;
use App\Modules\Consultation\Domain\Enums\ConsultationCategory;
use App\Modules\Consultation\Domain\Enums\ConsultationMode;
use App\Modules\Consultation\Domain\Enums\ConsultationSessionStatus;
use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Consultation\Domain\Models\ConsultationSession;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->ledgerService = app(LedgerService::class);
});

function createBillingCitizen(string $mobile, string $nationalId, string $name): Citizen
{
    return Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', $mobile),
        'mobile_encrypted' => 'enc:'.$mobile,
        'national_id_hash' => hash('sha256', $nationalId),
        'national_id_encrypted' => 'enc:'.$nationalId,
        'full_name' => $name,
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);
}

function depositWallet(LedgerService $ledger, Citizen $citizen, int $amountRials): void
{
    $wallet = $ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $citizen->id, LedgerAccountKind::WALLET);
    $clearing = $ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING);

    $ledger->recordTransaction(
        reference: 'DEP-'.Str::random(8),
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($clearing, LedgerDirection::DEBIT, $amountRials),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, $amountRials),
        ],
        description: 'واریز اولیه تست'
    );
}

it('verifies fake client duration_seconds has zero effect on fee (Invariant §5.3, TASK-119, TASK-119-T)', function (): void {
    $advisorCitizen = createBillingCitizen('09120000001', '0010000001', 'مشاور مالیاتی');
    $clientCitizen = createBillingCitizen('09120000002', '0010000002', 'شهروند متقاضی');

    depositWallet($this->ledgerService, $clientCitizen, 10000000); // 10,000,000 Rials

    $advisor = Advisor::query()->create([
        'citizen_id' => $advisorCitizen->id,
        'display_name' => 'دکتر صابری',
        'title' => 'مشاور مالیاتی',
        'category' => ConsultationCategory::Tax,
        'license_number' => 'LIC-TAX-100',
        'price_text_chat_rials' => 2000000,
        'price_phone_per_minute_rials' => 500000,
        'price_deep_review_rials' => 6000000,
        'application_status' => AdvisorApplicationStatus::Approved,
        'is_verified' => true,
    ]);

    Sanctum::actingAs($clientCitizen, ['*']);

    // Start session
    $startRes = $this->postJson('/api/v1/consultations/sessions/start', [
        'advisor_id' => $advisor->id,
        'mode' => 'call',
    ])->assertStatus(201);

    $sessionId = $startRes->json('data.id');

    // Simulate 3 minutes passing on the server
    $session = ConsultationSession::query()->find($sessionId);
    $session->update([
        'started_at' => CarbonImmutable::now()->subMinutes(3)->subSeconds(15), // 195 seconds
    ]);

    // Client attempts to send fake duration_seconds: 1 second or 99999 seconds!
    // The endpoint doesn't even accept client duration, or ignores it completely!
    $endRes = $this->postJson("/api/v1/consultations/sessions/{$sessionId}/end", [
        'duration_seconds' => 1, // MALICIOUS / FAKE CLIENT VALUE!
    ])->assertStatus(200);

    $session->refresh();

    // 195 seconds = 4 minutes (ceil(195/60) = 4) * 500,000 = 2,000,000 Rials
    expect($session->total_fee_rials)->toBe(2000000)
        ->and($session->duration_seconds)->toBeGreaterThanOrEqual(195);
});

it('verifies E2E Scenario E21: minute-based session, server timestamps, 80/20 ledger settlement and balance integrity (§5.3, §8.2, §10.3 E21, TASK-119, TASK-119-T)', function (): void {
    $advisorCitizen = createBillingCitizen('09120000003', '0010000003', 'دکتر حسینی');
    $clientCitizen = createBillingCitizen('09120000004', '0010000004', 'علی محمدی');

    // Initial balances: Client = 5,000,000 Rials
    depositWallet($this->ledgerService, $clientCitizen, 5000000);

    $advisor = Advisor::query()->create([
        'citizen_id' => $advisorCitizen->id,
        'display_name' => 'دکتر حسینی',
        'title' => 'مشاور روابط کار و تامین اجتماعی',
        'category' => ConsultationCategory::InsuranceLabor,
        'license_number' => 'LIC-LAB-200',
        'price_text_chat_rials' => 1500000,
        'price_phone_per_minute_rials' => 600000,
        'price_deep_review_rials' => 4500000,
        'application_status' => AdvisorApplicationStatus::Approved,
        'is_verified' => true,
    ]);

    Sanctum::actingAs($clientCitizen, ['*']);

    $startRes = $this->postJson('/api/v1/consultations/sessions/start', [
        'advisor_id' => $advisor->id,
        'mode' => 'call',
    ])->assertStatus(201);

    $sessionId = $startRes->json('data.id');

    // Simulate 2 minutes session (110 seconds -> ceil(110/60) = 2 minutes * 600,000 = 1,200,000 Rials)
    $session = ConsultationSession::query()->find($sessionId);
    $session->update([
        'started_at' => CarbonImmutable::now()->subSeconds(110),
    ]);

    // End session
    $endRes = $this->postJson("/api/v1/consultations/sessions/{$sessionId}/end")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'completed');

    $session->refresh();

    $expectedTotalFee = 1200000;
    $expectedAdvisorFee = 960000; // 80%
    $expectedPlatformFee = 240000; // 20%

    expect($session->total_fee_rials)->toBe($expectedTotalFee);

    // Verify Double-Entry Ledger balances (§8.2)
    $clientWallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $clientCitizen->id, LedgerAccountKind::WALLET);
    $advisorPayable = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::ADVISOR, $advisor->id, LedgerAccountKind::PAYABLE);
    $platformRevenue = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE);

    // Client wallet: 5,000,000 - 1,200,000 = 3,800,000
    expect($this->ledgerService->getBalanceRials($clientWallet))->toBe(3800000)
        // Advisor payable: +960,000
        ->and($this->ledgerService->getBalanceRials($advisorPayable))->toBe($expectedAdvisorFee)
        // Platform revenue: +240,000
        ->and($this->ledgerService->getBalanceRials($platformRevenue))->toBe($expectedPlatformFee);

    // Verify Ledger Transaction equality: SUM(debit) == SUM(credit)
    $tx = DB::table('ledger_transactions')->where('reference', 'CONSULT-'.$session->tracking_code)->first();
    expect($tx)->not->toBeNull();

    $entries = DB::table('ledger_entries')->where('transaction_id', $tx->id)->get();
    $totalDebits = $entries->where('direction', 'debit')->sum('amount_rials');
    $totalCredits = $entries->where('direction', 'credit')->sum('amount_rials');

    expect($totalDebits)->toBe($expectedTotalFee)
        ->and($totalCredits)->toBe($expectedTotalFee)
        ->and($totalDebits)->toBe($totalCredits);
});

it('verifies case_review mode session with documents upload, linked service and advisor verdict (§5.3, TASK-119, TASK-119-T)', function (): void {
    $advisorCitizen = createBillingCitizen('09120000005', '0010000005', 'مشاور پرونده');
    $clientCitizen = createBillingCitizen('09120000006', '0010000006', 'شهروند متقاضی پرونده');

    depositWallet($this->ledgerService, $clientCitizen, 10000000);

    $advisor = Advisor::query()->create([
        'citizen_id' => $advisorCitizen->id,
        'display_name' => 'مهندس کریمی',
        'title' => 'مشاور شرکت‌ها و مناقصات',
        'category' => ConsultationCategory::TendersPermits,
        'license_number' => 'LIC-TND-300',
        'price_text_chat_rials' => 2000000,
        'price_phone_per_minute_rials' => 500000,
        'price_deep_review_rials' => 7000000,
        'application_status' => AdvisorApplicationStatus::Approved,
        'is_verified' => true,
    ]);

    $cat = ServiceCategory::query()->create([
        'id' => (string) Str::uuid(),
        'title' => 'مجوزها و مناقصات',
        'icon_name' => 'file-text',
    ]);

    $service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $cat->id,
        'slug' => 'srv-tender-qualification',
        'title' => 'ارزیابی کیفی مناقصه',
        'description' => 'توضیحات مناقصه',
        'tags' => ['مناقصه', 'مجوز'],
        'fee_rials' => 25000000,
        'estimated_days_min' => 3,
        'estimated_days_max' => 7,
    ]);

    Sanctum::actingAs($clientCitizen, ['*']);

    $startRes = $this->postJson('/api/v1/consultations/sessions/start', [
        'advisor_id' => $advisor->id,
        'mode' => 'case_review',
        'linked_service_id' => $service->id,
    ])->assertStatus(201);

    $sessionId = $startRes->json('data.id');

    // End session with verdict and doc count
    $endRes = $this->postJson("/api/v1/consultations/sessions/{$sessionId}/end", [
        'advisor_verdict' => 'اسناد شرکت در مناقصه بررسی گردید و تایید شد.',
        'uploaded_docs_count' => 3,
    ])->assertStatus(200)
        ->assertJsonPath('data.total_fee_rials', 7000000)
        ->assertJsonPath('data.advisor_verdict', 'اسناد شرکت در مناقصه بررسی گردید و تایید شد.')
        ->assertJsonPath('data.uploaded_docs_count', 3)
        ->assertJsonPath('data.linked_service.id', $service->id);

    $session = ConsultationSession::query()->find($sessionId);
    expect($session->total_fee_rials)->toBe(7000000)
        ->and($session->advisor_verdict)->toBe('اسناد شرکت در مناقصه بررسی گردید و تایید شد.')
        ->and($session->uploaded_docs_count)->toBe(3);
});
