<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

const EXPECTED_PROVINCES = [
    'THR', 'ESF', 'FRS', 'KHZ', 'EAZ', 'WAZ', 'ARD', 'ILM', 'BSH', 'CHB',
    'SKH', 'RKH', 'NKH', 'ZNJ', 'SMN', 'SBN', 'QZW', 'QOM', 'KRD', 'KMN',
    'KSH', 'KBD', 'GLS', 'GLN', 'LRS', 'MZN', 'MRK', 'HRZ', 'HMD', 'YZD',
    'ABZ',
];

it('ensures case_requests table has all required columns from Architecture ERD §6.1', function (): void {
    expect(Schema::hasTable('case_requests'))->toBeTrue();

    $expectedColumns = [
        'id', 'tracking_code', 'citizen_id', 'service_id', 'office_id',
        'province_code', 'status', 'turn_owner', 'current_step', 'total_steps',
        'fee_paid_rials', 'office_share_rials', 'platform_share_rials',
        'citizen_location', 'delegation_id', 'delivery_preference',
        'sla_deadline_at', 'created_at', 'updated_at', 'closed_at',
    ];

    foreach ($expectedColumns as $col) {
        expect(Schema::hasColumn('case_requests', $col))->toBeTrue("Column [{$col}] must exist in case_requests.");
    }
});

it('verifies CaseStatus enum has exactly 11 lifecycle statuses and correct terminal flags', function (): void {
    $expectedStatuses = [
        'draft', 'searching_office', 'assigned_to_office', 'expert_review',
        'action_required', 'government_inquiry', 'ready_for_issue', 'delivering',
        'completed', 'rejected', 'cancelled',
    ];

    expect(CaseStatus::values())->toHaveCount(11)
        ->and(CaseStatus::values())->toBe($expectedStatuses);

    expect(CaseStatus::COMPLETED->isTerminal())->toBeTrue()
        ->and(CaseStatus::REJECTED->isTerminal())->toBeTrue()
        ->and(CaseStatus::CANCELLED->isTerminal())->toBeTrue()
        ->and(CaseStatus::DRAFT->isTerminal())->toBeFalse()
        ->and(CaseStatus::SEARCHING_OFFICE->isTerminal())->toBeFalse()
        ->and(CaseStatus::EXPERT_REVIEW->isTerminal())->toBeFalse();
});

it('verifies TurnOwner enum has exactly 5 owners matching Architecture §5.4', function (): void {
    $expectedOwners = ['citizen', 'office', 'government', 'postal', 'system'];

    expect(TurnOwner::values())->toHaveCount(5)
        ->and(TurnOwner::values())->toBe($expectedOwners);
});

it('verifies DeliveryPreference enum has 3 delivery modes', function (): void {
    $expected = ['in_person', 'courier', 'post'];

    expect(DeliveryPreference::values())->toHaveCount(3)
        ->and(DeliveryPreference::values())->toBe($expected);
});

it('creates and retrieves a case request with casts, relationships, and scopes', function (): void {
    $citizen = Citizen::query()->create([
        'national_id_encrypted' => 'enc-nid',
        'national_id_hash' => hash('sha256', '0012345678'),
        'mobile_encrypted' => 'enc-mob',
        'mobile_hash' => hash('sha256', '09121111111'),
        'full_name' => 'سهراب سپهری',
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-case-test',
        'title' => 'خدمات هویتی آزمایشی',
    ]);

    $service = Service::query()->create([
        'slug' => 'test-case-service',
        'title' => 'تعویض کارت ملی آزمایشی',
        'description' => 'توضیحات خدمت آزمایشی برای تست پرونده',
        'tags' => ['in-person'],
        'category_id' => $category->id,
        'fee_rials' => 500_000,
    ]);

    $office = Office::query()->create([
        'code' => '9010',
        'name' => 'پیشخوان مرکز تهران',
        'membership_status' => 'registered_online',
    ]);

    $case = CaseRequest::query()->create([
        'tracking_code' => 'CR-1405-00001',
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'office_id' => $office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'current_step' => 1,
        'total_steps' => 6,
        'fee_paid_rials' => 500_000,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
    ]);

    expect($case->status)->toBe(CaseStatus::SEARCHING_OFFICE)
        ->and($case->turn_owner)->toBe(TurnOwner::SYSTEM)
        ->and($case->delivery_preference)->toBe(DeliveryPreference::IN_PERSON)
        ->and($case->fee_paid_rials)->toBe(500_000);

    expect($case->citizen?->id)->toBe($citizen->id)
        ->and($case->service?->id)->toBe($service->id)
        ->and($case->office?->id)->toBe($office->id);

    // Test scopes
    expect(CaseRequest::byProvince('THR')->count())->toBe(1)
        ->and(CaseRequest::byProvince('ESF')->count())->toBe(0)
        ->and(CaseRequest::active()->count())->toBe(1);

    $case->update(['status' => CaseStatus::COMPLETED]);
    expect(CaseRequest::active()->count())->toBe(0);
});

it('verifies 32 partitions, partition pruning, and default partition routing in PostgreSQL', function (): void {
    $driver = config('database.connections.'.config('database.default').'.driver');

    if ($driver !== 'pgsql') {
        // Fallback assertion on SQLite/MySQL environments
        expect(Schema::hasTable('case_requests'))->toBeTrue();

        return;
    }

    // 1. Verify exactly 32 partitions exist (31 provinces + 1 default)
    $partitions = DB::select("
        SELECT inhrelid::regclass::text AS partition_name
        FROM pg_inherits
        WHERE inhparent = 'case_requests'::regclass
        ORDER BY partition_name;
    ");

    expect($partitions)->toHaveCount(32);

    $partitionNames = array_map(fn ($p) => (string) $p->partition_name, $partitions);
    foreach (EXPECTED_PROVINCES as $prov) {
        $expectedName = 'case_requests_'.strtolower($prov);
        expect($partitionNames)->toContain($expectedName);
    }
    expect($partitionNames)->toContain('case_requests_default');

    // 2. Insert records into a known province (THR) and an unknown province (ZZZ)
    $thrId = Str::uuid()->toString();
    $zzzId = Str::uuid()->toString();
    $citId = Str::uuid()->toString();
    $svcId = Str::uuid()->toString();

    DB::statement("
        INSERT INTO case_requests (id, tracking_code, citizen_id, service_id, province_code, status, turn_owner)
        VALUES ('{$thrId}', 'CR-THR-101', '{$citId}', '{$svcId}', 'THR', 'draft', 'system');
    ");

    DB::statement("
        INSERT INTO case_requests (id, tracking_code, citizen_id, service_id, province_code, status, turn_owner)
        VALUES ('{$zzzId}', 'CR-ZZZ-999', '{$citId}', '{$svcId}', 'ZZZ', 'draft', 'system');
    ");

    // 3. Verify ZZZ routed to case_requests_default
    $defaultRows = DB::select("SELECT id, province_code FROM case_requests_default WHERE id = '{$zzzId}';");
    expect($defaultRows)->toHaveCount(1)
        ->and($defaultRows[0]->province_code)->toBe('ZZZ');

    // 4. Verify THR routed to case_requests_thr
    $thrRows = DB::select("SELECT id, province_code FROM case_requests_thr WHERE id = '{$thrId}';");
    expect($thrRows)->toHaveCount(1)
        ->and($thrRows[0]->province_code)->toBe('THR');

    // 5. Verify Partition Pruning via EXPLAIN
    $explain = DB::select("EXPLAIN SELECT * FROM case_requests WHERE province_code = 'THR';");
    $explainText = implode("\n", array_map(fn ($r) => (string) ($r->{'QUERY PLAN'} ?? ''), $explain));

    // The query plan must scan case_requests_thr and NOT scan case_requests_esf or others
    expect($explainText)->toContain('case_requests_thr')
        ->and($explainText)->not->toContain('case_requests_esf')
        ->and($explainText)->not->toContain('case_requests_frs');
});
