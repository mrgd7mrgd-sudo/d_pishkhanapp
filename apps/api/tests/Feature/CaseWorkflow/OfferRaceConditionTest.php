<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\CaseWorkflow\Jobs\DispatchCaseJob;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->category = ServiceCategory::query()->firstOrCreate(
        ['id' => 'cat_identity'],
        [
            'title' => 'خدمات هویتی و سجلی',
            'slug' => 'identity-test',
            'icon' => 'card',
            'display_order' => 1,
        ]
    );

    $this->service = Service::query()->create([
        'category_id' => 'cat_identity',
        'slug' => 'svc_identity_offer_test_'.Str::random(5),
        'title' => 'تعویض شناسنامه تستی',
        'description' => 'تست پیشنهاد رقابتی',
        'tags' => ['in-person'],
        'fee_rials' => 2000000,
        'office_share_percent' => 60.0,
        'is_active' => true,
    ]);

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند تست پیشنهاد';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();
});

/**
 * Helper to create a test office and operator.
 */
function createOfficeAndOperator(string $code, string $name, float $slaScore = 100.00): array
{
    $office = Office::query()->create([
        'code' => $code,
        'name' => $name,
        'is_online' => true,
        'sla_score' => $slaScore,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $operator = new Operator;
    $operator->office_id = $office->id;
    $operator->username = 'op_'.$code;
    $operator->password_hash = Hash::make('Secret123!');
    $operator->full_name = 'اپراتور '.$name;
    $operator->national_id = '008'.str_pad($code, 7, '0', STR_PAD_LEFT);
    $operator->mobile = '0912'.str_pad($code, 7, '1', STR_PAD_LEFT);
    $operator->role = OperatorRole::OPERATOR;
    $operator->is_active = true;
    $operator->save();

    return [$office, $operator];
}

/**
 * Helper to create a case in searching_office status.
 */
function createSearchingCase(string $citizenId, string $serviceId): CaseRequest
{
    return CaseRequest::create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-'.strtoupper(Str::random(8)),
        'citizen_id' => $citizenId,
        'service_id' => $serviceId,
        'province_code' => 'THR',
        'city' => 'تهران',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'current_step' => 1,
        'total_steps' => 5,
        'fee_paid_rials' => 2000000,
        'office_share_rials' => 1200000,
        'platform_share_rials' => 800000,
    ]);
}

test('concurrency: 20 parallel accept requests result in exactly 1 HTTP 200 and 19 HTTP 409 (Scenario E3, TASK-069-T)', function (): void {
    $case = createSearchingCase($this->citizen->id, $this->service->id);

    $offices = [];
    $operators = [];
    $offers = [];

    // Create 20 distinct offices, operators and offers for the same case
    for ($i = 1; $i <= 20; $i++) {
        [$office, $operator] = createOfficeAndOperator("069{$i}", "دفتر شماره {$i}");
        $offices[] = $office;
        $operators[] = $operator;

        $offers[] = DispatchOffer::create([
            'id' => (string) Str::uuid(),
            'case_id' => $case->id,
            'office_id' => $office->id,
            'round' => 1,
            'status' => DispatchOfferStatus::PENDING,
            'expires_at' => CarbonImmutable::now()->addSeconds(90),
        ]);
    }

    $successCount = 0;
    $conflictCount = 0;
    $winningOperator = null;

    // Simulate 20 competing acceptance attempts
    for ($i = 0; $i < 20; $i++) {
        $response = $this->actingAs($operators[$i], 'sanctum')
            ->postJson("/api/v1/offers/{$offers[$i]->id}/accept");

        if ($response->status() === 200) {
            $successCount++;
            $winningOperator = $operators[$i];
            $response->assertJsonPath('data.status', CaseStatus::ASSIGNED_TO_OFFICE->value)
                ->assertJsonPath('data.turn_owner', TurnOwner::OFFICE->value)
                ->assertJsonPath('data.assigned_office_id', $winningOperator->office_id);
        } elseif ($response->status() === 409) {
            $conflictCount++;
            $response->assertJsonPath('code', 'OFFER_ALREADY_TAKEN');
        }
    }

    // Exactly 1 winner, 19 rejected with 409 OFFER_ALREADY_TAKEN
    expect($successCount)->toBe(1)
        ->and($conflictCount)->toBe(19)
        ->and($winningOperator)->not->toBeNull();

    // Verify case in database is assigned to the winning office
    $case->refresh();
    expect($case->status)->toBe(CaseStatus::ASSIGNED_TO_OFFICE)
        ->and($case->office_id)->toBe($winningOperator->office_id)
        ->and($case->turn_owner)->toBe(TurnOwner::OFFICE);

    // Verify all other 19 offers are now expired in the database
    $expiredOffersCount = DispatchOffer::query()
        ->where('case_id', $case->id)
        ->where('status', DispatchOfferStatus::EXPIRED->value)
        ->count();

    expect($expiredOffersCount)->toBe(19);

    // Verify the winning offer is marked accepted
    $acceptedOffersCount = DispatchOffer::query()
        ->where('case_id', $case->id)
        ->where('status', DispatchOfferStatus::ACCEPTED->value)
        ->count();

    expect($acceptedOffersCount)->toBe(1);
});

test('operator from another office attempting to accept or decline returns HTTP 404 (Horizontal Isolation §7.3, TASK-069-T)', function (): void {
    $case = createSearchingCase($this->citizen->id, $this->service->id);

    [$officeA, $operatorA] = createOfficeAndOperator('1111', 'دفتر الف');
    [$officeB, $operatorB] = createOfficeAndOperator('2222', 'دفتر ب');

    $offerA = DispatchOffer::create([
        'id' => (string) Str::uuid(),
        'case_id' => $case->id,
        'office_id' => $officeA->id,
        'round' => 1,
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => CarbonImmutable::now()->addSeconds(90),
    ]);

    // Operator B attempts to accept Offer A belonging to Office A -> 404 (never 403)
    $acceptResponse = $this->actingAs($operatorB, 'sanctum')
        ->postJson("/api/v1/offers/{$offerA->id}/accept");

    $acceptResponse->assertStatus(404);

    // Operator B attempts to decline Offer A belonging to Office A -> 404
    $declineResponse = $this->actingAs($operatorB, 'sanctum')
        ->postJson("/api/v1/offers/{$offerA->id}/decline");

    $declineResponse->assertStatus(404);

    // Offer A remains pending
    $offerA->refresh();
    expect($offerA->status)->toBe(DispatchOfferStatus::PENDING);
});

test('attempting to accept expired offer returns HTTP 409 OFFER_EXPIRED (§5.8, TASK-069-T)', function (): void {
    $case = createSearchingCase($this->citizen->id, $this->service->id);
    [$office, $operator] = createOfficeAndOperator('3333', 'دفتر ج');

    $expiredOffer = DispatchOffer::create([
        'id' => (string) Str::uuid(),
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 1,
        'status' => DispatchOfferStatus::EXPIRED,
        'expires_at' => CarbonImmutable::now()->subMinute(),
    ]);

    $response = $this->actingAs($operator, 'sanctum')
        ->postJson("/api/v1/offers/{$expiredOffer->id}/accept");

    $response->assertStatus(409)
        ->assertJsonPath('code', 'OFFER_EXPIRED');
});

test('GET /desk/offers returns only active pending offers for authenticated operator office (§11, TASK-069-T)', function (): void {
    $case1 = createSearchingCase($this->citizen->id, $this->service->id);
    $case2 = createSearchingCase($this->citizen->id, $this->service->id);

    [$officeA, $operatorA] = createOfficeAndOperator('4441', 'دفتر د ۱');
    [$officeB, $operatorB] = createOfficeAndOperator('4442', 'دفتر د ۲');

    // Active pending offer for Office A
    $activeOfferA = DispatchOffer::create([
        'id' => (string) Str::uuid(),
        'case_id' => $case1->id,
        'office_id' => $officeA->id,
        'round' => 1,
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => CarbonImmutable::now()->addSeconds(60),
    ]);

    // Expired offer for Office A (should not appear)
    DispatchOffer::create([
        'id' => (string) Str::uuid(),
        'case_id' => $case2->id,
        'office_id' => $officeA->id,
        'round' => 1,
        'status' => DispatchOfferStatus::EXPIRED,
        'expires_at' => CarbonImmutable::now()->subSecond(),
    ]);

    // Active offer for Office B (should not appear for Operator A)
    DispatchOffer::create([
        'id' => (string) Str::uuid(),
        'case_id' => $case1->id,
        'office_id' => $officeB->id,
        'round' => 1,
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => CarbonImmutable::now()->addSeconds(60),
    ]);

    $response = $this->actingAs($operatorA, 'sanctum')
        ->getJson('/api/v1/desk/offers');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeOfferA->id)
        ->assertJsonPath('data.0.case_id', $case1->id)
        ->assertJsonPath('data.0.status', DispatchOfferStatus::PENDING->value);

    expect($response->json('data.0.remaining_seconds'))->toBeGreaterThan(0);
});

test('declining offer marks it declined, penalizes SLA score, and advances to next round if last pending (§5.8, TASK-069-T)', function (): void {
    Queue::fake([DispatchCaseJob::class]);

    $case = createSearchingCase($this->citizen->id, $this->service->id);
    [$office, $operator] = createOfficeAndOperator('5555', 'دفتر ه', 100.00);

    $offer = DispatchOffer::create([
        'id' => (string) Str::uuid(),
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 1,
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => CarbonImmutable::now()->addSeconds(90),
    ]);

    $response = $this->actingAs($operator, 'sanctum')
        ->postJson("/api/v1/offers/{$offer->id}/decline");

    $response->assertStatus(200)
        ->assertJsonPath('data.status', DispatchOfferStatus::DECLINED->value);

    // Verify offer status in DB
    $offer->refresh();
    expect($offer->status)->toBe(DispatchOfferStatus::DECLINED)
        ->and($offer->responded_by)->toBe($operator->id);

    // Verify SLA score penalty (decreased from 100.00 to 99.00)
    $office->refresh();
    expect((float) $office->sla_score)->toBe(99.00);

    // Since this was the only pending offer of round 1, round 2 must be dispatched
    Queue::assertPushed(DispatchCaseJob::class, function (DispatchCaseJob $job) use ($case): bool {
        return $job->caseId === $case->id && $job->round === 2;
    });
});
