<?php

declare(strict_types=1);

namespace Tests\Feature\OfficeNetwork;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeReview;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSlaEvent;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Audit\AuditableAction;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    Role::firstOrCreate(['name' => 'office_operator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'office_manager', 'guard_name' => 'web']);

    $this->officeA = Office::query()->create([
        'code' => '8101',
        'name' => 'دفتر پیشخوان دولت صادقیه',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
        'rating' => 0.0,
        'review_count' => 0,
    ]);

    $this->officeB = Office::query()->create([
        'code' => '8102',
        'name' => 'دفتر پیشخوان دولت تجریش',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
        'rating' => 0.0,
        'review_count' => 0,
    ]);

    $this->operatorA = new Operator;
    $this->operatorA->office_id = $this->officeA->id;
    $this->operatorA->username = 'op_sadeqiyeh';
    $this->operatorA->password_hash = Hash::make('Secret123!');
    $this->operatorA->full_name = 'کیوان خسروی';
    $this->operatorA->national_id = '0081234581';
    $this->operatorA->mobile = '09121110021';
    $this->operatorA->role = OperatorRole::OPERATOR;
    $this->operatorA->counter_number = 1;
    $this->operatorA->is_active = true;
    $this->operatorA->save();
    $this->operatorA->assignRole('office_operator');

    $this->managerA = new Operator;
    $this->managerA->office_id = $this->officeA->id;
    $this->managerA->username = 'mgr_sadeqiyeh';
    $this->managerA->password_hash = Hash::make('Secret123!');
    $this->managerA->full_name = 'مهندس فرزاد افشار';
    $this->managerA->national_id = '0081234582';
    $this->managerA->mobile = '09121110022';
    $this->managerA->role = OperatorRole::MANAGER;
    $this->managerA->counter_number = 5;
    $this->managerA->is_active = true;
    $this->managerA->save();
    $this->managerA->assignRole('office_manager');

    $this->managerB = new Operator;
    $this->managerB->office_id = $this->officeB->id;
    $this->managerB->username = 'mgr_tajrish';
    $this->managerB->password_hash = Hash::make('Secret123!');
    $this->managerB->full_name = 'سروش علوی';
    $this->managerB->national_id = '0081234583';
    $this->managerB->mobile = '09121110023';
    $this->managerB->role = OperatorRole::MANAGER;
    $this->managerB->counter_number = 1;
    $this->managerB->is_active = true;
    $this->managerB->save();
    $this->managerB->assignRole('office_manager');

    $this->citizen1 = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09121115566'),
        'mobile_encrypted' => 'enc:09121115566',
        'national_id_hash' => hash('sha256', '0010350866'),
        'national_id_encrypted' => 'enc:0010350866',
        'full_name' => 'بهرام رادمنش',
        'tier' => CitizenTier::SILVER->value,
        'profile_completed' => true,
    ]);

    $this->citizen2 = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09121117788'),
        'mobile_encrypted' => 'enc:09121117788',
        'national_id_hash' => hash('sha256', '0010350877'),
        'national_id_encrypted' => 'enc:0010350877',
        'full_name' => 'مونا یزدانی',
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-review-test',
        'title' => 'خدمات ثبتی و سجلی',
    ]);

    $this->service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $category->id,
        'title' => 'صدور شناسنامه نوزاد',
        'slug' => 'birth-certificate-issue',
        'description' => 'صدور شناسنامه جدید برای نوزاد با مدارک بیمارستان',
        'tags' => ['in-person', 'identity'],
        'fee_rials' => 150000,
        'office_share_percent' => 70,
        'is_active' => true,
    ]);
});

function createReviewTestCase(Citizen $citizen, Service $service, Office $office, CaseStatus $status): CaseRequest
{
    return CaseRequest::query()->create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CR-REV-'.Str::upper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'office_id' => $office->id,
        'province_code' => 'THR',
        'status' => $status,
        'turn_owner' => TurnOwner::OFFICE,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'fee_paid_rials' => 150000,
    ]);
}

test('Citizen review cannot be submitted for uncompleted case or another citizen case (TASK-101, §5.3)', function (): void {
    Sanctum::actingAs($this->citizen1, ['*']);

    // 1. Case in EXPERT_REVIEW (not completed)
    $incompleteCase = createReviewTestCase($this->citizen1, $this->service, $this->officeA, CaseStatus::EXPERT_REVIEW);

    $resIncomplete = $this->postJson('/api/v1/reviews', [
        'case_id' => $incompleteCase->id,
        'rating' => 5,
        'comment' => 'برخورد پرسنل عالی بود ولی هنوز پرونده باز است.',
    ]);
    $resIncomplete->assertStatus(422)
        ->assertJsonValidationErrors(['case_id']);

    // 2. Case completed, but belongs to citizen2
    $otherCitizenCase = createReviewTestCase($this->citizen2, $this->service, $this->officeA, CaseStatus::COMPLETED);

    $resOther = $this->postJson('/api/v1/reviews', [
        'case_id' => $otherCitizenCase->id,
        'rating' => 5,
        'comment' => 'تلاش برای ثبت نظر روی پرونده فرد دیگر.',
    ]);
    $resOther->assertStatus(422)
        ->assertJsonValidationErrors(['case_id']);
});

test('Citizen can submit review for completed case and office rating is recalculated correctly (TASK-101, §5.3, TASK-101-T)', function (): void {
    // 1. Citizen 1 completes a case in Office A and submits 5-star review
    $case1 = createReviewTestCase($this->citizen1, $this->service, $this->officeA, CaseStatus::COMPLETED);

    Sanctum::actingAs($this->citizen1, ['*']);
    $res1 = $this->postJson('/api/v1/reviews', [
        'case_id' => $case1->id,
        'rating' => 5,
        'comment' => 'بررسی فوق‌العاده سریع و محترمانه بود. بسیار ممنونم از پرسنل باجه ۱.',
        'tags' => ['سرعت عالی', 'برخورد عالی'],
    ]);

    $res1->assertStatus(201)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.comment', 'بررسی فوق‌العاده سریع و محترمانه بود. بسیار ممنونم از پرسنل باجه ۱.')
        ->assertJsonPath('data.is_verified', true)
        ->assertJsonPath('data.office_id', $this->officeA->id);

    // Office A rating should now be 5.0 with count 1
    $this->officeA->refresh();
    expect($this->officeA->rating)->toBe(5.0)
        ->and($this->officeA->review_count)->toBe(1);

    // 2. Citizen 2 completes another case in Office A and submits 4-star review
    $case2 = createReviewTestCase($this->citizen2, $this->service, $this->officeA, CaseStatus::COMPLETED);

    Sanctum::actingAs($this->citizen2, ['*']);
    $res2 = $this->postJson('/api/v1/reviews', [
        'case_id' => $case2->id,
        'rating' => 4,
        'comment' => 'خوب و منظم انجام شد. کمی شلوغ بود.',
        'tags' => ['دقت بالا'],
    ]);

    $res2->assertStatus(201)
        ->assertJsonPath('data.rating', 4);

    // Office A rating recalculated: (5 + 4) / 2 = 4.5
    $this->officeA->refresh();
    expect($this->officeA->rating)->toBe(4.5)
        ->and($this->officeA->review_count)->toBe(2);

    // Verify audit log recorded for review submission
    $audit = DB::table('audit_logs')
        ->where('action', AuditableAction::REVIEW_SUBMITTED->value)
        ->where('actor_id', $this->citizen2->id)
        ->latest('created_at')
        ->first();

    expect($audit)->not->toBeNull()
        ->and(json_decode((string) $audit->changes, true))->toMatchArray([
            'rating' => 4,
            'office_id' => $this->officeA->id,
            'case_id' => $case2->id,
            'new_office_rating' => 4.5,
            'new_office_review_count' => 2,
        ]);
});

test('Duplicate review for the same case is rejected (TASK-101, §5.3, TASK-101-T)', function (): void {
    Sanctum::actingAs($this->citizen1, ['*']);

    $case = createReviewTestCase($this->citizen1, $this->service, $this->officeA, CaseStatus::COMPLETED);

    // 1st review succeeds
    $this->postJson('/api/v1/reviews', [
        'case_id' => $case->id,
        'rating' => 5,
        'comment' => 'نظر اول برای پرونده.',
    ])->assertStatus(201);

    // 2nd review for the same case must be rejected
    $duplicateRes = $this->postJson('/api/v1/reviews', [
        'case_id' => $case->id,
        'rating' => 4,
        'comment' => 'تلاش برای ثبت نظر مجدد.',
    ]);

    $duplicateRes->assertStatus(422)
        ->assertJsonValidationErrors(['case_id']);
});

test('Public GET /offices/{id}/reviews lists verified reviews with filters (TASK-101, §4.4)', function (): void {
    $case1 = createReviewTestCase($this->citizen1, $this->service, $this->officeA, CaseStatus::COMPLETED);
    $case2 = createReviewTestCase($this->citizen2, $this->service, $this->officeA, CaseStatus::COMPLETED);

    OfficeReview::query()->create([
        'id' => (string) Str::uuid(),
        'office_id' => $this->officeA->id,
        'citizen_id' => $this->citizen1->id,
        'case_id' => $case1->id,
        'rating' => 5,
        'comment' => 'عالی بود ۵ ستاره',
        'is_verified' => true,
        'manager_reply' => 'سپاس از نظر شما',
    ]);

    OfficeReview::query()->create([
        'id' => (string) Str::uuid(),
        'office_id' => $this->officeA->id,
        'citizen_id' => $this->citizen2->id,
        'case_id' => $case2->id,
        'rating' => 3,
        'comment' => 'معمولی بود',
        'is_verified' => true,
    ]);

    // 1. All reviews for Office A (2 reviews)
    $resAll = $this->getJson("/api/v1/offices/{$this->officeA->id}/reviews");
    $resAll->assertOk()
        ->assertJsonCount(2, 'data');

    // 2. Filter by rating = 5 (1 review)
    $resRating = $this->getJson("/api/v1/offices/{$this->officeA->id}/reviews?rating=5");
    $resRating->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.rating', 5);

    // 3. Filter by with_reply = 1
    $resReply = $this->getJson("/api/v1/offices/{$this->officeA->id}/reviews?with_reply=1");
    $resReply->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.manager_reply', 'سپاس از نظر شما');
});

test('Only office_manager can reply to reviews and office_operator is forbidden (TASK-101, §7.3, TASK-101-T)', function (): void {
    $case = createReviewTestCase($this->citizen1, $this->service, $this->officeA, CaseStatus::COMPLETED);

    $review = OfficeReview::query()->create([
        'id' => (string) Str::uuid(),
        'office_id' => $this->officeA->id,
        'citizen_id' => $this->citizen1->id,
        'case_id' => $case->id,
        'rating' => 4,
        'comment' => 'رسیدگی دقیق، فقط پارکینگ نداشت.',
        'is_verified' => true,
    ]);

    // 1. office_operator attempts to reply -> 403 Forbidden
    Sanctum::actingAs($this->operatorA, ['*']);
    $resOperator = $this->postJson("/api/v1/desk/reviews/{$review->id}/reply", [
        'reply' => 'پاسخ تستی توسط اپراتور معمولی.',
    ]);
    $resOperator->assertStatus(403)
        ->assertJsonPath('code', 'FORBIDDEN_NOT_OFFICE_MANAGER');

    // 2. office_manager of Office A replies -> 200 OK
    Sanctum::actingAs($this->managerA, ['*']);
    $resManager = $this->postJson("/api/v1/desk/reviews/{$review->id}/reply", [
        'reply' => 'با تشکر از بازخورد ارزشمند شما، هماهنگی با پارکینگ عمومی مجاور انجام شده است.',
    ]);
    $resManager->assertOk()
        ->assertJsonPath('data.manager_reply', 'با تشکر از بازخورد ارزشمند شما، هماهنگی با پارکینگ عمومی مجاور انجام شده است.')
        ->assertJsonPath('data.manager_reply_info.text', 'با تشکر از بازخورد ارزشمند شما، هماهنگی با پارکینگ عمومی مجاور انجام شده است.');

    $review->refresh();
    expect($review->manager_reply)->toBe('با تشکر از بازخورد ارزشمند شما، هماهنگی با پارکینگ عمومی مجاور انجام شده است.')
        ->and($review->manager_operator_id)->toBe($this->managerA->id)
        ->and($review->manager_replied_at)->not->toBeNull();

    // Verify audit log for manager reply
    $auditReply = DB::table('audit_logs')
        ->where('action', AuditableAction::REVIEW_REPLIED->value)
        ->where('actor_id', $this->managerA->id)
        ->latest('created_at')
        ->first();

    expect($auditReply)->not->toBeNull()
        ->and(json_decode((string) $auditReply->changes, true))->toMatchArray([
            'manager_operator_id' => $this->managerA->id,
        ]);
});

test('Cross-office manager or operator receives 404 on review inspection and reply (TASK-101, §7.3, TASK-101-T)', function (): void {
    $case = createReviewTestCase($this->citizen1, $this->service, $this->officeA, CaseStatus::COMPLETED);

    $reviewA = OfficeReview::query()->create([
        'id' => (string) Str::uuid(),
        'office_id' => $this->officeA->id,
        'citizen_id' => $this->citizen1->id,
        'case_id' => $case->id,
        'rating' => 5,
        'comment' => 'نظر برای دفتر صادقیه.',
        'is_verified' => true,
    ]);

    Sanctum::actingAs($this->managerB, ['*']);
    $this->getJson("/api/v1/desk/reviews/{$reviewA->id}")->assertNotFound();
    $this->postJson("/api/v1/desk/reviews/{$reviewA->id}/reply", ['reply' => 'پاسخ غیرمجاز'])->assertNotFound();
});

test('Desk GET /desk/reviews lists office reviews with filters (TASK-101, §4.4, §7.3)', function (): void {
    $case1 = createReviewTestCase($this->citizen1, $this->service, $this->officeA, CaseStatus::COMPLETED);
    $case2 = createReviewTestCase($this->citizen2, $this->service, $this->officeA, CaseStatus::COMPLETED);
    $caseB = createReviewTestCase($this->citizen1, $this->service, $this->officeB, CaseStatus::COMPLETED);

    OfficeReview::query()->create([
        'id' => (string) Str::uuid(),
        'office_id' => $this->officeA->id,
        'citizen_id' => $this->citizen1->id,
        'case_id' => $case1->id,
        'rating' => 5,
        'comment' => 'برخورد فوق‌العاده سریع باجه ۲',
        'is_verified' => true,
        'manager_reply' => 'پاسخ داده شد',
    ]);
    OfficeReview::query()->create([
        'id' => (string) Str::uuid(),
        'office_id' => $this->officeA->id,
        'citizen_id' => $this->citizen2->id,
        'case_id' => $case2->id,
        'rating' => 3,
        'comment' => 'نیاز به پیگیری و پاسخگویی بیشتر',
        'is_verified' => true,
    ]);
    OfficeReview::query()->create([
        'id' => (string) Str::uuid(),
        'office_id' => $this->officeB->id,
        'citizen_id' => $this->citizen1->id,
        'case_id' => $caseB->id,
        'rating' => 5,
        'comment' => 'نظر دفتر ب',
        'is_verified' => true,
    ]);

    Sanctum::actingAs($this->operatorA, ['*']);
    $this->getJson('/api/v1/desk/reviews')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/desk/reviews?filter=need_reply')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.rating', 3);
    $this->getJson('/api/v1/desk/reviews?filter=with_reply')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.rating', 5);
});

test('Desk GET /desk/reviews/sla-stats returns SLA trends and breakdown (TASK-104, §4.4, §7.3)', function (): void {
    OfficeSlaEvent::query()->create([
        'office_id' => $this->officeA->id,
        'event_type' => 'late_return',
        'penalty_points' => 5,
        'is_breach' => true,
        'occurred_at' => now(),
    ]);

    Sanctum::actingAs($this->operatorA, ['*']);
    $response = $this->getJson('/api/v1/desk/reviews/sla-stats');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'current_score',
                'window_days',
                'trend',
                'breakdown',
            ],
        ])
        ->assertJsonPath('data.window_days', 30);
});
