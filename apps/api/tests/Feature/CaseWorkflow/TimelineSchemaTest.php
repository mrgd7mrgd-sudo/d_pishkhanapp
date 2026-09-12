<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createTestContext(): array
{
    $citizen = Citizen::query()->create([
        'national_id_encrypted' => 'enc-nid',
        'national_id_hash' => hash('sha256', Str::random(10)),
        'mobile_encrypted' => 'enc-mob',
        'mobile_hash' => hash('sha256', Str::random(11)),
        'full_name' => 'فریدون مشیری',
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-timeline-test-'.Str::random(5),
        'title' => 'خدمات آزمایشی',
    ]);

    $service = Service::query()->create([
        'slug' => 'test-svc-'.Str::random(5),
        'title' => 'خدمت تست تایم‌لاین',
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
        'delivery_preference' => DeliveryPreference::IN_PERSON,
    ]);

    return [$case, $citizen, $service];
}

it('ensures case_timeline_steps and case_documents tables exist with proper columns', function (): void {
    expect(Schema::hasTable('case_timeline_steps'))->toBeTrue()
        ->and(Schema::hasTable('case_documents'))->toBeTrue();

    $timelineCols = [
        'id', 'case_id', 'sequence', 'title', 'description', 'status',
        'turn_owner', 'turn_owner_label', 'actor_type', 'actor_id',
        'office_note', 'duration_actual_minutes', 'duration_typical_minutes',
        'occurred_at', 'created_at', 'updated_at',
    ];
    foreach ($timelineCols as $col) {
        expect(Schema::hasColumn('case_timeline_steps', $col))->toBeTrue("Timeline column [{$col}] must exist.");
    }

    $docCols = [
        'id', 'case_id', 'document_type_code', 'version', 'status',
        'storage_key', 'encrypted_data_key', 'content_sha256', 'size_bytes',
        'mime_type', 'quality_warnings', 'reason_code', 'reviewed_by',
        'uploaded_at', 'created_at', 'updated_at',
    ];
    foreach ($docCols as $col) {
        expect(Schema::hasColumn('case_documents', $col))->toBeTrue("Document column [{$col}] must exist.");
    }
});

it('verifies rejected document version remains preserved when new version is uploaded (DoD: immutability)', function (): void {
    [$case] = createTestContext();

    $docType = DocumentType::query()->firstOrCreate(
        ['code' => 'national_card_front'],
        ['title' => 'تصویر روی کارت ملی', 'accepted_mimes' => ['image/jpeg', 'image/png'], 'validity_months' => 120]
    );

    // Version 1: Uploaded and rejected due to blur
    $v1 = CaseDocument::query()->create([
        'case_id' => $case->id,
        'document_type_code' => $docType->code,
        'version' => 1,
        'status' => CaseDocumentStatus::REJECTED,
        'storage_key' => 'cases/THR/2026/09/'.$case->id.'/nid_v1.enc',
        'encrypted_data_key' => 'key-payload-v1',
        'content_sha256' => hash('sha256', 'file-v1-bytes'),
        'size_bytes' => 102400,
        'mime_type' => 'image/jpeg',
        'quality_warnings' => ['blur'],
        'reason_code' => 'DOC_BLUR',
        'uploaded_at' => Carbon::now()->subHours(2),
    ]);

    // Version 2: Re-uploaded and verified
    $v2 = CaseDocument::query()->create([
        'case_id' => $case->id,
        'document_type_code' => $docType->code,
        'version' => 2,
        'status' => CaseDocumentStatus::VERIFIED,
        'storage_key' => 'cases/THR/2026/09/'.$case->id.'/nid_v2.enc',
        'encrypted_data_key' => 'key-payload-v2',
        'content_sha256' => hash('sha256', 'file-v2-bytes'),
        'size_bytes' => 150000,
        'mime_type' => 'image/jpeg',
        'quality_warnings' => [],
        'uploaded_at' => Carbon::now(),
    ]);

    // Both versions MUST persist independently in the database (DoD)
    expect($case->documents)->toHaveCount(2);

    $persistedV1 = CaseDocument::query()->findOrFail($v1->id);
    $persistedV2 = CaseDocument::query()->findOrFail($v2->id);

    expect($persistedV1->version)->toBe(1)
        ->and($persistedV1->status)->toBe(CaseDocumentStatus::REJECTED)
        ->and($persistedV1->quality_warnings)->toBe(['blur'])
        ->and($persistedV2->version)->toBe(2)
        ->and($persistedV2->status)->toBe(CaseDocumentStatus::VERIFIED)
        ->and($persistedV2->quality_warnings)->toBe([]);
});

it('enforces unique sequence per case preventing duplicate timeline sequence numbers', function (): void {
    [$case] = createTestContext();

    CaseTimelineStep::query()->create([
        'case_id' => $case->id,
        'sequence' => 1,
        'title' => 'ثبت درخواست',
        'status' => TimelineStepStatus::DONE,
        'turn_owner' => TurnOwner::SYSTEM,
        'turn_owner_label' => 'سیستم',
        'actor_type' => TimelineActorType::CITIZEN,
        'occurred_at' => Carbon::now(),
    ]);

    expect(fn () => CaseTimelineStep::query()->create([
        'case_id' => $case->id,
        'sequence' => 1, // Duplicate sequence for same case
        'title' => 'گام تکراری نامعتبر',
        'status' => TimelineStepStatus::CURRENT,
        'turn_owner' => TurnOwner::SYSTEM,
        'turn_owner_label' => 'سیستم',
        'actor_type' => TimelineActorType::SYSTEM,
        'occurred_at' => Carbon::now(),
    ]))->toThrow(QueryException::class);
});

it('verifies timeline sequence index idx_timeline_case_seq usage via EXPLAIN in PostgreSQL', function (): void {
    [$case] = createTestContext();

    CaseTimelineStep::query()->create([
        'case_id' => $case->id,
        'sequence' => 1,
        'title' => 'گام اول',
        'status' => TimelineStepStatus::DONE,
        'turn_owner' => TurnOwner::SYSTEM,
        'turn_owner_label' => 'سیستم',
        'actor_type' => TimelineActorType::SYSTEM,
        'occurred_at' => Carbon::now(),
    ]);

    $driver = config('database.connections.'.config('database.default').'.driver');

    if ($driver === 'pgsql') {
        $explain = DB::select("
            EXPLAIN SELECT * FROM case_timeline_steps
            WHERE case_id = '{$case->id}' AND sequence = 1;
        ");
        $plan = implode("\n", array_map(fn ($r) => (string) ($r->{'QUERY PLAN'} ?? ''), $explain));
        expect($plan)->toContain('idx_timeline_case_seq');
    } else {
        $steps = $case->timelineSteps;
        expect($steps)->toHaveCount(1)
            ->and($steps->first()?->sequence)->toBe(1);
    }
});
