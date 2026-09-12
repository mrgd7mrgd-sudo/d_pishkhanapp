<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('architecture rule: no file outside CaseStateMachine assigns directly to status property', function (): void {
    $appPath = app_path();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath));
    $violations = [];

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getRealPath();
        $normalizedPath = str_replace('\\', '/', $filePath);

        // Allow CaseStateMachine and models declaring attributes
        if (str_ends_with($normalizedPath, 'CaseWorkflow/Domain/CaseStateMachine.php')) {
            continue;
        }

        $content = (string) file_get_contents($filePath);

        // Pattern checking direct assignment to case status: $case->status = ...
        if (preg_match('/\$case\s*->\s*status\s*=/i', $content)) {
            $violations[] = $normalizedPath;
        }
    }

    expect($violations)->toBeEmpty(
        'Found direct assignments to $case->status outside CaseStateMachine: '.implode(', ', $violations)
    );
});

test('runtime enforcement: direct assignment to status on existing CaseRequest throws LogicException', function (): void {
    $citizen = Citizen::create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09121112233'),
        'mobile_encrypted' => 'enc:09121112233',
        'national_id_hash' => hash('sha256', '0010350802'),
        'national_id_encrypted' => 'enc:0010350802',
        'full_name' => 'کاربر تست گارد وضعیت',
        'tier' => 'bronze',
        'profile_completed' => true,
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-guard-'.Str::random(5),
        'title' => 'دسته تست گارد',
    ]);

    $service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $category->id,
        'title' => 'خدمت تست گارد',
        'slug' => 'guard-test-'.Str::random(6),
        'description' => 'توضیحات تست گارد',
        'tags' => ['online'],
        'fee_rials' => 300000,
        'office_share_percent' => 70,
        'is_active' => true,
    ]);

    $case = CaseRequest::create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-GUARD-'.strtoupper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'current_step' => 1,
        'total_steps' => 5,
        'fee_paid_rials' => 300000,
        'office_share_rials' => 210000,
        'platform_share_rials' => 90000,
    ]);

    // Intentional direct mutation outside CaseStateMachine MUST fail
    expect(function () use ($case): void {
        $case->status = CaseStatus::COMPLETED;
    })->toThrow(
        LogicException::class,
        'Direct mutation of CaseRequest::$status is prohibited. Use CaseStateMachine.'
    );

    // Legitimate mutation through CaseStateMachine MUST succeed
    $stateMachine = new CaseStateMachine;
    $ctx = new TransitionContext(
        title: 'پذیرش پیشنهاد دفتر',
        actorType: TimelineActorType::OPERATOR,
        actorId: (string) Str::uuid(),
    );

    $updatedCase = $stateMachine->transition($case, CaseStatus::ASSIGNED_TO_OFFICE, $ctx);
    expect($updatedCase->status)->toBe(CaseStatus::ASSIGNED_TO_OFFICE)
        ->and($updatedCase->turn_owner)->toBe(TurnOwner::OFFICE);
});
