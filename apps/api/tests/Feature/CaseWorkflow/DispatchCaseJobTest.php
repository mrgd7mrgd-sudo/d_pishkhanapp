<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Events\DispatchOfferCreated;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\CaseWorkflow\Jobs\DispatchCaseJob;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Application\Queries\OfficeFinder;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->category = ServiceCategory::query()->create([
        'id' => 'cat_identity',
        'title' => 'خدمات هویتی و سجلی',
        'slug' => 'identity',
        'icon' => 'card',
        'display_order' => 1,
    ]);

    $this->service = Service::query()->create([
        'category_id' => 'cat_identity',
        'slug' => 'svc_identity_card_dispatch',
        'title' => 'تعویض کارت ملی هوشمند',
        'description' => 'تست دیسپچ اسنپی',
        'tags' => ['in-person'],
        'estimated_days_min' => 7,
        'estimated_days_max' => 14,
        'fee_rials' => 3400000,
        'office_share_percent' => 50.0,
        'is_active' => true,
    ]);

    $this->citizen = Citizen::query()->create([
        'national_id_encrypted' => 'enc_nid_dispatch',
        'national_id_hash' => hash('sha256', Str::random(10)),
        'mobile_encrypted' => 'enc_mob_dispatch',
        'mobile_hash' => hash('sha256', Str::random(11)),
        'full_name' => 'شهروند تستی دیسپچ',
        'tier' => CitizenTier::BRONZE,
        'province_code' => 'THR',
    ]);

    $this->ledgerService = app(LedgerService::class);
});

function createTestOffice(
    string $code,
    string $name,
    float $lat,
    float $lng,
    bool $isOnline = true,
    float $rating = 4.8,
    string $categoryId = 'cat_identity'
): Office {
    $office = Office::query()->create([
        'code' => $code,
        'name' => $name,
        'manager_name' => 'مدیر '.$name,
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE,
        'is_online' => $isOnline,
        'rating' => $rating,
        'review_count' => 50,
        'address' => 'تهران، '.$name,
        'province_code' => 'THR',
        'active_counters' => 2,
        'current_waiting_queue' => 1,
        'sla_score' => 95.0,
        'location' => "{$lat},{$lng}",
    ]);

    OfficeServiceCoverage::query()->create([
        'office_id' => $office->id,
        'category_id' => $categoryId,
        'is_active' => true,
    ]);

    return $office;
}

function createTestCase(Citizen $citizen, Service $service, float $lat = 35.7592, float $lng = 51.4083): CaseRequest
{
    return CaseRequest::query()->create([
        'tracking_code' => 'CR-1405-'.mt_rand(10000, 99999),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'current_step' => 1,
        'total_steps' => 6,
        'fee_paid_rials' => 3400000,
        'office_share_rials' => 1700000,
        'platform_share_rials' => 1700000,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'citizen_location' => ['lat' => $lat, 'lng' => $lng],
    ]);
}

test('offers sent to exactly 3 nearest online offices supporting category (§5.8, TASK-067-T)', function (): void {
    Event::fake([DispatchOfferCreated::class]);
    Queue::fake();

    $case = createTestCase($this->citizen, $this->service, 35.7592, 51.4083);

    $office1 = createTestOffice('OFF-01', 'دفتر ونک', 35.7595, 51.4085, true, 4.9);
    $office2 = createTestOffice('OFF-02', 'دفتر میرداماد', 35.7610, 51.4110, true, 4.8);
    $office3 = createTestOffice('OFF-03', 'دفتر گاندی', 35.7550, 51.4150, true, 4.7);
    $office4 = createTestOffice('OFF-04', 'دفتر جردن', 35.7680, 51.4190, true, 4.5);
    $offlineOffice = createTestOffice('OFF-05', 'دفتر ملاصدرا آفلاین', 35.7580, 51.4060, false, 5.0);

    $job = new DispatchCaseJob($case->id, 1);
    $job->handle(app(OfficeFinder::class), app(CaseStateMachine::class));

    $offers = DispatchOffer::query()->where('case_id', $case->id)->get();
    expect($offers)->toHaveCount(3);

    $offeredOfficeIds = $offers->pluck('office_id')->all();
    expect($offeredOfficeIds)->toContain($office1->id)
        ->and($offeredOfficeIds)->toContain($office2->id)
        ->and($offeredOfficeIds)->toContain($office3->id)
        ->and($offeredOfficeIds)->not->toContain($office4->id)
        ->and($offeredOfficeIds)->not->toContain($offlineOffice->id);

    foreach ($offers as $offer) {
        expect($offer->round)->toBe(1)
            ->and($offer->status)->toBe(DispatchOfferStatus::PENDING)
            ->and($offer->expires_at)->not->toBeNull();
    }

    Event::assertDispatched(DispatchOfferCreated::class, 3);
});

test('each round expands radius sequentially [5, 10, 15, 25, 40] km (§5.8, TASK-067-T)', function (): void {
    Event::fake([DispatchOfferCreated::class]);
    Queue::fake([DispatchCaseJob::class, ExpireDispatchOfferJob::class]);

    // User at Vanak: 35.7592, 51.4083
    $case = createTestCase($this->citizen, $this->service, 35.7592, 51.4083);

    // Create office in Tajrish (lat: 35.8250, lng: 51.4083 -> ~7.3 km from Vanak)
    // Outside round 1 (5km), but inside round 2 (10km)
    $officeFar = createTestOffice('OFF-FAR', 'دفتر تجریش', 35.8250, 51.4083, true, 4.8);

    // Round 1 (radius 5km): office is too far (7.3km > 5km), 0 candidates found
    $job1 = new DispatchCaseJob($case->id, 1);
    $job1->handle(app(OfficeFinder::class), app(CaseStateMachine::class));

    // Zero offers created in round 1
    expect(DispatchOffer::query()->where('case_id', $case->id)->count())->toBe(0);

    // Next round (round 2) dispatched to queue
    Queue::assertPushed(DispatchCaseJob::class, fn (DispatchCaseJob $j): bool => $j->caseId === $case->id && $j->round === 2);

    // Round 2 (radius 10km): officeFar is within radius (7.3km <= 10km) and receives offer
    $job2 = new DispatchCaseJob($case->id, 2);
    $job2->handle(app(OfficeFinder::class), app(CaseStateMachine::class));

    $offers = DispatchOffer::query()->where('case_id', $case->id)->get();
    expect($offers)->toHaveCount(1)
        ->and($offers->first()?->office_id)->toBe($officeFar->id)
        ->and($offers->first()?->round)->toBe(2);
});

test('declined office is excluded from subsequent rounds (§5.8, TASK-067-T)', function (): void {
    Event::fake([DispatchOfferCreated::class]);
    Queue::fake();

    $case = createTestCase($this->citizen, $this->service, 35.7592, 51.4083);

    $office1 = createTestOffice('OFF-DEC', 'دفتر ردکننده', 35.7595, 51.4085, true, 4.9);
    $office2 = createTestOffice('OFF-ALT', 'دفتر جایگزین', 35.7610, 51.4110, true, 4.8);

    DispatchOffer::query()->create([
        'case_id' => $case->id,
        'office_id' => $office1->id,
        'round' => 1,
        'status' => DispatchOfferStatus::DECLINED,
        'expires_at' => now()->subMinute(),
        'responded_at' => now()->subSeconds(30),
    ]);

    $job2 = new DispatchCaseJob($case->id, 2);
    $job2->handle(app(OfficeFinder::class), app(CaseStateMachine::class));

    $round2Offers = DispatchOffer::query()
        ->where('case_id', $case->id)
        ->where('round', 2)
        ->get();

    expect($round2Offers)->toHaveCount(1)
        ->and($round2Offers->first()?->office_id)->toBe($office2->id)
        ->and($round2Offers->pluck('office_id')->all())->not->toContain($office1->id);
});

test('after 5 unsuccessful rounds case is cancelled and 100% fee is refunded to citizen wallet (§5.8, TASK-067-T)', function (): void {
    $wallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    $clearing = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::CLEARING);

    $this->ledgerService->recordTransaction(
        reference: 'TOPUP-DISPATCH-TEST',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($clearing, LedgerDirection::DEBIT, 5000000),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, 5000000),
        ],
        description: 'شارژ اولیه'
    );

    $case = createTestCase($this->citizen, $this->service);
    $this->ledgerService->recordTransaction(
        reference: $case->tracking_code,
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 3400000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 3400000),
        ],
        description: 'کسر کارمزد پرونده'
    );

    expect($this->ledgerService->getBalanceRials($wallet))->toBe(1600000)
        ->and($this->ledgerService->getBalanceRials($escrow))->toBe(3400000);

    $job = new DispatchCaseJob($case->id, 6);
    $job->handle(app(OfficeFinder::class), app(CaseStateMachine::class));

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::CANCELLED)
        ->and($case->turn_owner)->toBe(TurnOwner::SYSTEM);

    expect($this->ledgerService->getBalanceRials($wallet))->toBe(5000000)
        ->and($this->ledgerService->getBalanceRials($escrow))->toBe(0);
});

test('dispatch batch matching SLO is strictly under 5 seconds (§9.5, TASK-067-T)', function (): void {
    Queue::fake();

    $case = createTestCase($this->citizen, $this->service);

    for ($i = 1; $i <= 10; $i++) {
        createTestOffice("OFF-SLO-{$i}", "دفتر کارایی {$i}", 35.7592 + ($i * 0.001), 51.4083 + ($i * 0.001));
    }

    $startTime = microtime(true);

    $job = new DispatchCaseJob($case->id, 1);
    $job->handle(app(OfficeFinder::class), app(CaseStateMachine::class));

    $elapsedSeconds = microtime(true) - $startTime;

    expect($elapsedSeconds)->toBeLessThan(5.0);
    expect(DispatchOffer::query()->where('case_id', $case->id)->count())->toBe(3);
});
