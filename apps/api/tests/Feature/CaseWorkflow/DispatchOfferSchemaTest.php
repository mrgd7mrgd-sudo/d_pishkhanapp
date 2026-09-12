<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createDispatchTestContext(): array
{
    $citizen = Citizen::query()->create([
        'national_id_encrypted' => 'enc-nid',
        'national_id_hash' => hash('sha256', Str::random(10)),
        'mobile_encrypted' => 'enc-mob',
        'mobile_hash' => hash('sha256', Str::random(11)),
        'full_name' => 'فریدون مشیری',
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-dispatch-test-'.Str::random(5),
        'title' => 'خدمات آزمایشی',
    ]);

    $service = Service::query()->create([
        'slug' => 'test-svc-'.Str::random(5),
        'title' => 'خدمت تست دیسپچ',
        'description' => 'توضیحات تست',
        'tags' => ['in-person'],
        'category_id' => $category->id,
        'fee_rials' => 200_000,
    ]);

    $case = CaseRequest::query()->create([
        'tracking_code' => 'CR-'.Str::random(8),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'current_step' => 1,
        'total_steps' => 6,
        'fee_paid_rials' => 200_000,
        'office_share_rials' => 100_000,
        'platform_share_rials' => 100_000,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
    ]);

    $office = Office::query()->create([
        'code' => 'OFF-'.Str::random(4),
        'name' => 'دفتر پیشخوان تست ولیعصر',
        'manager_name' => 'آقای مدیر',
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE,
        'is_online' => true,
        'rating' => 4.8,
        'review_count' => 120,
        'address' => 'تهران، میدان ولیعصر',
        'province_code' => 'THR',
        'active_counters' => 3,
        'current_waiting_queue' => 2,
        'sla_score' => 95.0,
    ]);

    $operator = Operator::query()->create([
        'office_id' => $office->id,
        'username' => 'op_'.Str::random(6),
        'password_hash' => bcrypt('Secret123!'),
        'full_name' => 'سعید کارشناس',
        'national_id_hash' => hash('sha256', Str::random(10)),
        'mobile_hash' => hash('sha256', Str::random(11)),
        'role' => OperatorRole::OPERATOR,
        'counter_number' => 1,
        'is_active' => true,
    ]);

    return [$case, $office, $operator];
}

it('ensures dispatch_offers table exists with expected schema and constraints (TASK-066)', function (): void {
    expect(Schema::hasTable('dispatch_offers'))->toBeTrue()
        ->and(Schema::hasColumns('dispatch_offers', [
            'id',
            'case_id',
            'office_id',
            'round',
            'status',
            'expires_at',
            'responded_at',
            'responded_by',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('prevents duplicate pending offers to the same office for the same case (TASK-066-T, DoD)', function (): void {
    [$case, $office] = createDispatchTestContext();

    DispatchOffer::query()->create([
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 1,
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => Carbon::now()->addSeconds(90),
    ]);

    expect(fn () => DispatchOffer::query()->create([
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 2, // Second pending offer for same case and office
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => Carbon::now()->addSeconds(90),
    ]))->toThrow(QueryException::class);
});

it('allows subsequent offers to the same office once previous offer is no longer pending (TASK-066)', function (): void {
    [$case, $office] = createDispatchTestContext();

    $offer1 = DispatchOffer::query()->create([
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 1,
        'status' => DispatchOfferStatus::DECLINED,
        'expires_at' => Carbon::now()->subMinute(),
        'responded_at' => Carbon::now()->subSeconds(30),
    ]);

    $offer2 = DispatchOffer::query()->create([
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 2,
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => Carbon::now()->addSeconds(90),
    ]);

    expect($offer1->exists)->toBeTrue()
        ->and($offer2->exists)->toBeTrue()
        ->and($offer1->status)->toBe(DispatchOfferStatus::DECLINED)
        ->and($offer2->status)->toBe(DispatchOfferStatus::PENDING);
});

it('verifies partial index idx_offers_office_pending usage via EXPLAIN in PostgreSQL (TASK-066-T, DoD)', function (): void {
    [$case, $office] = createDispatchTestContext();

    DispatchOffer::query()->create([
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 1,
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => Carbon::now()->addSeconds(90),
    ]);

    $driver = config('database.connections.'.config('database.default').'.driver');

    if ($driver === 'pgsql') {
        $explain = DB::select("
            EXPLAIN SELECT * FROM dispatch_offers
            WHERE office_id = '{$office->id}' AND status = 'pending' AND expires_at > NOW();
        ");
        $plan = implode("\n", array_map(fn ($r) => (string) ($r->{'QUERY PLAN'} ?? ''), $explain));
        expect($plan)->toContain('idx_offers_office_pending');
    } else {
        $activeOffers = DispatchOffer::activeForOffice($office->id)->get();
        expect($activeOffers)->toHaveCount(1)
            ->and($activeOffers->first()?->round)->toBe(1);
    }
});

it('verifies relationships on DispatchOffer and CaseRequest (TASK-066)', function (): void {
    [$case, $office, $operator] = createDispatchTestContext();

    $offer = DispatchOffer::query()->create([
        'case_id' => $case->id,
        'office_id' => $office->id,
        'round' => 1,
        'status' => DispatchOfferStatus::ACCEPTED,
        'expires_at' => Carbon::now()->addSeconds(90),
        'responded_at' => Carbon::now(),
        'responded_by' => $operator->id,
    ]);

    expect($offer->case->id)->toBe($case->id)
        ->and($offer->office->id)->toBe($office->id)
        ->and($offer->responder?->id)->toBe($operator->id)
        ->and($case->dispatchOffers)->toHaveCount(1)
        ->and($case->dispatchOffers->first()?->id)->toBe($offer->id);
});
