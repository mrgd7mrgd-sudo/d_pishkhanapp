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
use App\Modules\CaseWorkflow\Jobs\ExpireDispatchOfferJob;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Application\Queries\OfficeFinder;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        'slug' => 'svc_identity_card_expiry',
        'title' => 'تعویض کارت هوشمند ملی',
        'description' => 'تست انقضای آفر',
        'tags' => ['in-person'],
        'estimated_days_min' => 7,
        'estimated_days_max' => 14,
        'fee_rials' => 3400000,
        'office_share_percent' => 50.0,
        'is_active' => true,
    ]);

    $this->citizen = Citizen::query()->create([
        'national_id_encrypted' => 'enc_nid_expiry',
        'national_id_hash' => hash('sha256', Str::random(10)),
        'mobile_encrypted' => 'enc_mob_expiry',
        'mobile_hash' => hash('sha256', Str::random(11)),
        'full_name' => 'شهروند تست انقضا',
        'tier' => CitizenTier::BRONZE,
        'province_code' => 'THR',
    ]);
});

function createExpiryTestOffice(string $code, string $name, float $lat = 35.7595, float $lng = 51.4085): Office
{
    $office = Office::query()->create([
        'code' => $code,
        'name' => $name,
        'manager_name' => 'مدیر '.$name,
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE,
        'is_online' => true,
        'rating' => 4.8,
        'review_count' => 30,
        'address' => 'تهران، '.$name,
        'province_code' => 'THR',
        'active_counters' => 2,
        'current_waiting_queue' => 1,
        'sla_score' => 95.0,
        'location' => "{$lat},{$lng}",
    ]);

    OfficeServiceCoverage::query()->create([
        'office_id' => $office->id,
        'category_id' => 'cat_identity',
        'is_active' => true,
    ]);

    return $office;
}

function createExpiryTestCase(Citizen $citizen, Service $service, float $lat = 35.7592, float $lng = 51.4083): CaseRequest
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

test('traveling 91 seconds causes pending offer to expire and begins next round (Scenario E4, TASK-068-T)', function (): void {
    Event::fake([DispatchOfferCreated::class]);
    Queue::fake([DispatchCaseJob::class, ExpireDispatchOfferJob::class]);

    $case = createExpiryTestCase($this->citizen, $this->service);
    $office1 = createExpiryTestOffice('OFF-EXP-1', 'دفتر ونک');

    // Run round 1: creates offer expiring in 90 seconds
    $job = new DispatchCaseJob($case->id, 1);
    $job->handle(app(OfficeFinder::class), app(CaseStateMachine::class));

    $offer = DispatchOffer::query()->where('case_id', $case->id)->first();
    expect($offer)->not->toBeNull()
        ->and($offer->status)->toBe(DispatchOfferStatus::PENDING)
        ->and($offer->round)->toBe(1);

    // Fast-forward time by 91 seconds past the 90s TTL
    $this->travel(91)->seconds();

    // Execute delayed job for offer expiration
    (new ExpireDispatchOfferJob($offer->id))->handle();

    $offer->refresh();
    expect($offer->status)->toBe(DispatchOfferStatus::EXPIRED)
        ->and($offer->responded_at)->not->toBeNull();

    // Next round (round 2) is automatically dispatched because all offers in round 1 expired
    Queue::assertPushed(DispatchCaseJob::class, fn (DispatchCaseJob $j): bool => $j->caseId === $case->id && $j->round === 2);
});

test('backup command pishkhan:expire-dispatch-offers catches orphaned pending offers (TASK-068-T)', function (): void {
    Queue::fake([DispatchCaseJob::class]);

    $case = createExpiryTestCase($this->citizen, $this->service);
    $office = createExpiryTestOffice('OFF-ORPHAN', 'دفتر جامانده');

    // Simulate an offer that missed its queue job and has been overdue for 2 minutes
    $overdueOffer = DispatchOffer::query()->create([
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 1,
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => CarbonImmutable::now()->subMinutes(2),
    ]);

    expect($overdueOffer->status)->toBe(DispatchOfferStatus::PENDING);

    // Run backup command
    $this->artisan('pishkhan:expire-dispatch-offers')
        ->expectsOutputToContain('Expired 1 overdue dispatch offers.')
        ->assertSuccessful();

    $overdueOffer->refresh();
    expect($overdueOffer->status)->toBe(DispatchOfferStatus::EXPIRED)
        ->and($overdueOffer->responded_at)->not->toBeNull();
});

test('accepted offer is never expired even after TTL has passed (TASK-068-T)', function (): void {
    $case = createExpiryTestCase($this->citizen, $this->service);
    $office = createExpiryTestOffice('OFF-ACC', 'دفتر پذیرنده');

    // Create an accepted offer whose original TTL was 5 minutes ago
    $acceptedOffer = DispatchOffer::query()->create([
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 1,
        'status' => DispatchOfferStatus::ACCEPTED,
        'expires_at' => CarbonImmutable::now()->subMinutes(5),
        'responded_at' => CarbonImmutable::now()->subMinutes(4),
    ]);

    // Attempt expiring via Job
    (new ExpireDispatchOfferJob($acceptedOffer->id))->handle();

    $acceptedOffer->refresh();
    expect($acceptedOffer->status)->toBe(DispatchOfferStatus::ACCEPTED);

    // Attempt expiring via scheduled Command
    $this->artisan('pishkhan:expire-dispatch-offers')
        ->expectsOutputToContain('Expired 0 overdue dispatch offers.')
        ->assertSuccessful();

    $acceptedOffer->refresh();
    expect($acceptedOffer->status)->toBe(DispatchOfferStatus::ACCEPTED);
});

test('concurrent command execution is safely guarded by distributed lock (TASK-068-T)', function (): void {
    $lock = Cache::lock('lock:sched:expire_dispatch_offers', 60);
    $lock->get();

    try {
        $this->artisan('pishkhan:expire-dispatch-offers')
            ->expectsOutputToContain('already running on another instance')
            ->assertSuccessful();
    } finally {
        $lock->release();
    }
});
