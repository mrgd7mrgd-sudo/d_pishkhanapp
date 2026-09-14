<?php

declare(strict_types=1);

namespace Tests\Feature\Messaging;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Domain\Enums\MessageSenderType;
use App\Modules\Messaging\Domain\Enums\NotificationType;
use App\Modules\Messaging\Domain\Events\NewCaseMessage;
use App\Modules\Messaging\Domain\Models\CaseMessage;
use App\Modules\Messaging\Domain\Models\Notification;
use App\Modules\Messaging\Domain\Models\NotificationPreference;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->citizenA = Citizen::create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09121111111'),
        'mobile_encrypted' => 'enc:09121111111',
        'national_id_hash' => hash('sha256', '0010350801'),
        'national_id_encrypted' => 'enc:0010350801',
        'full_name' => 'علی علوی',
        'tier' => 'bronze',
        'profile_completed' => true,
    ]);

    $this->citizenB = Citizen::create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09122222222'),
        'mobile_encrypted' => 'enc:09122222222',
        'national_id_hash' => hash('sha256', '0010350802'),
        'national_id_encrypted' => 'enc:0010350802',
        'full_name' => 'رضا رضایی',
        'tier' => 'bronze',
        'profile_completed' => true,
    ]);

    $this->officeA = Office::create([
        'id' => (string) Str::uuid(),
        'code' => 'OFF-MSG-A',
        'name' => 'دفتر پیام رسان الف',
        'province_code' => 'THR',
        'city' => 'تهران',
        'address' => 'خیابان بهشتی، پلاک ۱',
        'postal_code' => '1511111111',
        'phone' => '02188880001',
        'latitude' => 35.7300,
        'longitude' => 51.4200,
        'status' => 'active',
        'is_online' => true,
        'license_number' => 'LIC-MSG-001',
        'rating' => 4.80,
    ]);

    $this->officeB = Office::create([
        'id' => (string) Str::uuid(),
        'code' => 'OFF-MSG-B',
        'name' => 'دفتر پیام رسان ب',
        'province_code' => 'THR',
        'city' => 'تهران',
        'address' => 'خیابان بهشتی، پلاک ۲',
        'postal_code' => '1511111112',
        'phone' => '02188880002',
        'latitude' => 35.7310,
        'longitude' => 51.4210,
        'status' => 'active',
        'is_online' => true,
        'license_number' => 'LIC-MSG-002',
        'rating' => 4.70,
    ]);

    $this->operatorA = Operator::create([
        'id' => (string) Str::uuid(),
        'office_id' => $this->officeA->id,
        'username' => 'op_msg_a',
        'password_hash' => bcrypt('Secret123!'),
        'national_id_hash' => hash('sha256', '0010350803'),
        'national_id_encrypted' => 'enc:0010350803',
        'full_name' => 'کارشناس دفتر الف',
        'mobile_hash' => hash('sha256', '09123333333'),
        'mobile_encrypted' => 'enc:09123333333',
        'role' => 'operator',
        'is_active' => true,
    ]);

    $this->operatorB = Operator::create([
        'id' => (string) Str::uuid(),
        'office_id' => $this->officeB->id,
        'username' => 'op_msg_b',
        'password_hash' => bcrypt('Secret123!'),
        'national_id_hash' => hash('sha256', '0010350804'),
        'national_id_encrypted' => 'enc:0010350804',
        'full_name' => 'کارشناس دفتر ب',
        'mobile_hash' => hash('sha256', '09124444444'),
        'mobile_encrypted' => 'enc:09124444444',
        'role' => 'operator',
        'is_active' => true,
    ]);

    $this->category = ServiceCategory::create([
        'id' => 'cat-msg-'.Str::random(5),
        'title' => 'دسته پیام رسان',
    ]);

    $this->service = Service::create([
        'id' => (string) Str::uuid(),
        'category_id' => $this->category->id,
        'title' => 'خدمت تست پیام رسان',
        'slug' => 'msg-service-'.Str::random(6),
        'description' => 'توضیحات تست پیام رسان',
        'tags' => ['online'],
        'fee_rials' => 400000,
        'office_share_percent' => 70,
        'is_active' => true,
    ]);

    $this->caseA = CaseRequest::create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-MSG-'.strtoupper(Str::random(6)),
        'citizen_id' => $this->citizenA->id,
        'office_id' => $this->officeA->id,
        'service_id' => $this->service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::EXPERT_REVIEW,
        'turn_owner' => TurnOwner::OFFICE,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'current_step' => 2,
        'total_steps' => 5,
        'fee_paid_rials' => 400000,
        'office_share_rials' => 280000,
        'platform_share_rials' => 120000,
    ]);
});

test('third-party citizen cannot read or post case messages and receives 404 (Horizontal Isolation §7.3, TASK-072-T)', function (): void {
    // Citizen B is not owner of Case A -> 404
    $response = $this->actingAs($this->citizenB, 'sanctum')
        ->getJson("/api/v1/cases/{$this->caseA->id}/messages");

    $response->assertStatus(404)
        ->assertJson([
            'status' => 404,
            'detail' => 'Case not found.',
        ]);

    $postResponse = $this->actingAs($this->citizenB, 'sanctum')
        ->postJson("/api/v1/cases/{$this->caseA->id}/messages", [
            'body' => 'پیام نامعتبر از شخص ثالث',
        ]);

    $postResponse->assertStatus(404);
});

test('unassigned office operator cannot read or post case messages and receives 404 (Horizontal Isolation §7.3, TASK-072-T)', function (): void {
    // Operator B belongs to Office B, but Case A is assigned to Office A -> 404
    $response = $this->actingAs($this->operatorB, 'sanctum')
        ->getJson("/api/v1/cases/{$this->caseA->id}/messages");

    $response->assertStatus(404)
        ->assertJson([
            'status' => 404,
            'detail' => 'Case not found.',
        ]);

    $postResponse = $this->actingAs($this->operatorB, 'sanctum')
        ->postJson("/api/v1/cases/{$this->caseA->id}/messages", [
            'body' => 'پیام نامعتبر از اپراتور دفتر دیگر',
        ]);

    $postResponse->assertStatus(404);
});

test('owner citizen and assigned operator can send and view messages with correct metadata (§5.7, TASK-072-T)', function (): void {
    // 1. Citizen A sends message
    $resCitizen = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson("/api/v1/cases/{$this->caseA->id}/messages", [
            'body' => 'سلام، مدرک بارگذاری شد.',
            'attachment_key' => 'docs/passport.enc',
        ]);

    $resCitizen->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'case_id',
            'sender_type',
            'sender_id',
            'sender_name',
            'body',
            'attachment_key',
            'read_at',
            'created_at',
        ])
        ->assertJson([
            'case_id' => $this->caseA->id,
            'sender_type' => 'citizen',
            'sender_id' => $this->citizenA->id,
            'sender_name' => 'علی علوی',
            'body' => 'سلام، مدرک بارگذاری شد.',
            'attachment_key' => 'docs/passport.enc',
            'read_at' => null,
        ]);

    // 2. Operator A views messages
    $resOperator = $this->actingAs($this->operatorA, 'sanctum')
        ->getJson("/api/v1/cases/{$this->caseA->id}/messages");

    $resOperator->assertStatus(200)
        ->assertJsonStructure([
            'case_id',
            'unread_count',
            'items' => [
                '*' => [
                    'id',
                    'case_id',
                    'sender_type',
                    'sender_id',
                    'sender_name',
                    'body',
                    'attachment_key',
                    'read_at',
                    'created_at',
                ],
            ],
        ]);

    // Unread count was 1 for operator, now marked as read
    expect($resOperator->json('unread_count'))->toBe(1)
        ->and($resOperator->json('items'))->toHaveCount(1);

    // Operator A replies
    $resOperatorReply = $this->actingAs($this->operatorA, 'sanctum')
        ->postJson("/api/v1/cases/{$this->caseA->id}/messages", [
            'body' => 'با تشکر، در حال بررسی است.',
        ]);

    $resOperatorReply->assertStatus(201)
        ->assertJson([
            'sender_type' => 'operator',
            'sender_id' => $this->operatorA->id,
            'sender_name' => 'کارشناس دفتر الف',
            'body' => 'با تشکر، در حال بررسی است.',
        ]);

    // Citizen A views messages again
    $resCitizenView = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/cases/{$this->caseA->id}/messages");

    $resCitizenView->assertStatus(200);
    // Unread count was 1 (the operator reply), now marked as read
    expect($resCitizenView->json('unread_count'))->toBe(1)
        ->and($resCitizenView->json('items'))->toHaveCount(2);

    // After viewing, next check should show 0 unread
    $resCitizenView2 = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/cases/{$this->caseA->id}/messages");

    expect($resCitizenView2->json('unread_count'))->toBe(0);
});

test('sending message emits NewCaseMessage real-time broadcast event on private-case channel (TASK-072-T)', function (): void {
    Event::fake([NewCaseMessage::class]);

    $this->actingAs($this->citizenA, 'sanctum')
        ->postJson("/api/v1/cases/{$this->caseA->id}/messages", [
            'body' => 'بررسی رویداد بلادرنگ',
        ])
        ->assertStatus(201);

    Event::assertDispatched(NewCaseMessage::class, function (NewCaseMessage $event): bool {
        expect($event->broadcastAs())->toBe('message.new');

        $channels = $event->broadcastOn();
        expect($channels)->toHaveCount(1)
            ->and($channels[0]->name)->toBe("private-case.{$this->caseA->id}");

        $payload = $event->broadcastWith();
        expect($payload['case_id'])->toBe($this->caseA->id)
            ->and($payload['sender_type'])->toBe('citizen')
            ->and($payload['body'])->toBe('بررسی رویداد بلادرنگ')
            ->and(strlen((string) json_encode($payload)))->toBeLessThan(4096);

        return true;
    });
});

test('GET /cases/{id}/messages executes in constant number of queries preventing N+1 (TASK-072-T)', function (): void {
    // Seed 15 messages
    for ($i = 0; $i < 15; $i++) {
        CaseMessage::create([
            'id' => (string) Str::uuid(),
            'case_id' => $this->caseA->id,
            'sender_type' => $i % 2 === 0 ? MessageSenderType::CITIZEN : MessageSenderType::OPERATOR,
            'sender_id' => $i % 2 === 0 ? $this->citizenA->id : $this->operatorA->id,
            'sender_name' => $i % 2 === 0 ? 'علی علوی' : 'کارشناس دفتر الف',
            'body' => "پیام تستی شماره {$i}",
            'created_at' => Carbon::now()->addSeconds($i),
        ]);
    }

    DB::enableQueryLog();

    $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/cases/{$this->caseA->id}/messages")
        ->assertStatus(200);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Constant queries: user auth (1-2), case find (1), count unread (1), mark read update (1), select messages (1)
    expect(count($queries))->toBeLessThanOrEqual(6);
});

test('GET /notifications returns list with unread_count and supports unread_only filter (TASK-072-T)', function (): void {
    // Seed 3 notifications for Citizen A (2 unread, 1 read)
    Notification::create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizenA->id,
        'type' => NotificationType::CASE_STATUS->value,
        'title' => 'وضعیت پرونده تغییر کرد',
        'body' => 'پرونده شما در دست بررسی کارشناس است.',
        'payload' => ['case_id' => $this->caseA->id],
        'read_at' => null,
    ]);

    Notification::create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizenA->id,
        'type' => NotificationType::CASE_MESSAGE->value,
        'title' => 'پیام جدید',
        'body' => 'یک پیام جدید از دفتر دریافت شد.',
        'payload' => ['case_id' => $this->caseA->id],
        'read_at' => null,
    ]);

    $readNotif = Notification::create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizenA->id,
        'type' => NotificationType::SYSTEM->value,
        'title' => 'به سامانه پیشخوان خوش آمدید',
        'body' => 'حساب شما فعال شد.',
        'payload' => null,
        'read_at' => Carbon::now()->subHour(),
    ]);

    // Seed 1 notification for Citizen B (must not be visible to Citizen A)
    Notification::create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizenB->id,
        'type' => NotificationType::SYSTEM->value,
        'title' => 'اعلان کاربر دیگر',
        'body' => 'متن اعلان کاربر دیگر',
        'payload' => null,
        'read_at' => null,
    ]);

    // 1. Get all notifications for Citizen A
    $resAll = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson('/api/v1/notifications');

    $resAll->assertStatus(200)
        ->assertJson([
            'unread_count' => 2,
        ]);
    expect($resAll->json('items'))->toHaveCount(3);

    // 2. Filter unread_only
    $resUnread = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson('/api/v1/notifications?unread_only=1');

    $resUnread->assertStatus(200)
        ->assertJson([
            'unread_count' => 2,
        ]);
    expect($resUnread->json('items'))->toHaveCount(2);

    // 3. Mark first unread as read
    $firstUnreadId = $resUnread->json('items.0.id');
    $resMark = $this->actingAs($this->citizenA, 'sanctum')
        ->patchJson("/api/v1/notifications/{$firstUnreadId}/read");

    $resMark->assertStatus(200)
        ->assertJson([
            'id' => $firstUnreadId,
        ]);
    expect($resMark->json('read_at'))->not->toBeNull();

    // 4. Verify unread_count is now 1
    $resAfterMark = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson('/api/v1/notifications');

    $resAfterMark->assertStatus(200)
        ->assertJson([
            'unread_count' => 1,
        ]);

    // 5. Citizen B cannot mark Citizen A notification as read -> 404
    $this->actingAs($this->citizenB, 'sanctum')
        ->patchJson("/api/v1/notifications/{$firstUnreadId}/read")
        ->assertStatus(404);
});

test('GET and PATCH /notification-preferences manage notification channels correctly (TASK-072-T)', function (): void {
    // 1. Default preferences when none are saved yet
    $resDefault = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson('/api/v1/notification-preferences');

    $resDefault->assertStatus(200)
        ->assertJsonStructure([
            'preferences' => [
                '*' => [
                    'notification_type',
                    'label',
                    'sms_enabled',
                    'push_enabled',
                ],
            ],
        ]);

    // All types enabled by default
    $prefs = $resDefault->json('preferences');
    expect($prefs)->toHaveCount(count(NotificationType::cases()));
    foreach ($prefs as $p) {
        expect($p['sms_enabled'])->toBeTrue()
            ->and($p['push_enabled'])->toBeTrue();
    }

    // 2. Update preferences: disable SMS for case_status, disable push for sla_warning
    $resUpdate = $this->actingAs($this->citizenA, 'sanctum')
        ->patchJson('/api/v1/notification-preferences', [
            'preferences' => [
                [
                    'notification_type' => NotificationType::CASE_STATUS->value,
                    'sms_enabled' => false,
                    'push_enabled' => true,
                ],
                [
                    'notification_type' => NotificationType::SLA_WARNING->value,
                    'sms_enabled' => true,
                    'push_enabled' => false,
                ],
            ],
        ]);

    $resUpdate->assertStatus(200);

    // 3. Verify updated preferences persisted in database
    $prefStatus = NotificationPreference::where('citizen_id', $this->citizenA->id)
        ->where('notification_type', NotificationType::CASE_STATUS->value)
        ->first();

    expect($prefStatus)->not->toBeNull()
        ->and($prefStatus->sms_enabled)->toBeFalse()
        ->and($prefStatus->push_enabled)->toBeTrue();

    // 4. Citizen B has independent preferences untouched
    $resCitizenB = $this->actingAs($this->citizenB, 'sanctum')
        ->getJson('/api/v1/notification-preferences');

    foreach ($resCitizenB->json('preferences') as $p) {
        expect($p['sms_enabled'])->toBeTrue();
    }
});

test('it verifies idx_notifications_unread partial index usage via EXPLAIN (TASK-072-T, DoD)', function (): void {
    Notification::create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizenA->id,
        'type' => NotificationType::CASE_STATUS->value,
        'title' => 'اعلان تست ایندکس',
        'body' => 'متن اعلان',
        'payload' => null,
        'read_at' => null,
    ]);

    $connection = config('database.default');
    $driver = config("database.connections.{$connection}.driver");

    if ($driver === 'pgsql') {
        DB::statement('SET enable_seqscan = OFF;');
        $explain = DB::select(
            'EXPLAIN (FORMAT JSON) SELECT * FROM notifications WHERE citizen_id = ? AND read_at IS NULL ORDER BY created_at DESC',
            [$this->citizenA->id]
        );
        DB::statement('SET enable_seqscan = ON;');
        $explainJson = json_encode($explain);
        expect($explainJson)->toContain('idx_notifications_unread');
    } else {
        $explain = DB::select(
            'EXPLAIN QUERY PLAN SELECT * FROM notifications WHERE citizen_id = ? AND read_at IS NULL ORDER BY created_at DESC',
            [$this->citizenA->id]
        );
        $explainJson = json_encode($explain);
        expect($explainJson)->toContain('idx_notifications_unread');
    }
});
