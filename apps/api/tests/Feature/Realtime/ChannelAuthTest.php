<?php

declare(strict_types=1);

namespace Tests\Feature\Realtime;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    // Ensure roles exist
    Role::firstOrCreate(['name' => 'system_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'office_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'office_operator', 'guard_name' => 'web']);

    // Create Citizen A
    $this->citizenA = new Citizen;
    $this->citizenA->national_id = '0010350802';
    $this->citizenA->mobile = '09121112233';
    $this->citizenA->full_name = 'شهروند الف';
    $this->citizenA->tier = CitizenTier::BRONZE;
    $this->citizenA->province_code = 'THR';
    $this->citizenA->save();

    // Create Citizen B
    $this->citizenB = new Citizen;
    $this->citizenB->national_id = '0020350803';
    $this->citizenB->mobile = '09124445566';
    $this->citizenB->full_name = 'شهروند ب';
    $this->citizenB->tier = CitizenTier::SILVER;
    $this->citizenB->province_code = 'THR';
    $this->citizenB->save();

    // Create Office A
    $this->officeA = Office::query()->create([
        'code' => '7001',
        'name' => 'دفتر پیشخوان شریعتی',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    // Create Operator A (Office A)
    $this->operatorA = new Operator;
    $this->operatorA->office_id = $this->officeA->id;
    $this->operatorA->username = 'op_shariati';
    $this->operatorA->password_hash = Hash::make('Secret123!');
    $this->operatorA->full_name = 'رضا اکبری';
    $this->operatorA->national_id = '0081234561';
    $this->operatorA->mobile = '09121110001';
    $this->operatorA->role = OperatorRole::OPERATOR;
    $this->operatorA->counter_number = 2;
    $this->operatorA->is_active = true;
    $this->operatorA->save();
    $this->operatorA->assignRole('office_operator');

    // Create Office B
    $this->officeB = Office::query()->create([
        'code' => '7002',
        'name' => 'دفتر پیشخوان آزادی',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    // Create Operator B (Office B)
    $this->operatorB = new Operator;
    $this->operatorB->office_id = $this->officeB->id;
    $this->operatorB->username = 'op_azadi';
    $this->operatorB->password_hash = Hash::make('Secret123!');
    $this->operatorB->full_name = 'سارا راد';
    $this->operatorB->national_id = '0081234562';
    $this->operatorB->mobile = '09121110002';
    $this->operatorB->role = OperatorRole::OPERATOR;
    $this->operatorB->counter_number = 4;
    $this->operatorB->is_active = true;
    $this->operatorB->save();
    $this->operatorB->assignRole('office_operator');

    // Create System Admin Operator
    $this->adminOperator = new Operator;
    $this->adminOperator->office_id = null;
    $this->adminOperator->username = 'sys_admin';
    $this->adminOperator->password_hash = Hash::make('Secret123!');
    $this->adminOperator->full_name = 'مدیر ارشد سامانه';
    $this->adminOperator->national_id = '0089999999';
    $this->adminOperator->mobile = '09129990000';
    $this->adminOperator->role = OperatorRole::MANAGER;
    $this->adminOperator->is_active = true;
    $this->adminOperator->save();
    $this->adminOperator->assignRole('system_admin');

    // Create Category and Service
    $category = ServiceCategory::query()->firstOrCreate(
        ['id' => 'cat_realtime'],
        ['title' => 'خدمات بلادرنگ', 'slug' => 'realtime-test', 'icon' => 'bolt', 'display_order' => 1]
    );

    $this->service = Service::query()->create([
        'category_id' => $category->id,
        'slug' => 'svc_realtime_'.Str::random(5),
        'title' => 'خدمت تست کانال‌ها',
        'description' => 'تست وب‌سوکت و مجوزها',
        'tags' => ['online'],
        'fee_rials' => 1000000,
        'office_share_percent' => 70.0,
        'is_active' => true,
    ]);

    // Create Case assigned to Office A, owned by Citizen A
    $this->caseA = CaseRequest::create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-RT-'.strtoupper(Str::random(6)),
        'citizen_id' => $this->citizenA->id,
        'service_id' => $this->service->id,
        'office_id' => $this->officeA->id,
        'province_code' => 'THR',
        'city' => 'تهران',
        'status' => CaseStatus::ASSIGNED_TO_OFFICE,
        'turn_owner' => TurnOwner::OFFICE,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'current_step' => 2,
        'total_steps' => 5,
        'fee_paid_rials' => 1000000,
        'office_share_rials' => 700000,
        'platform_share_rials' => 300000,
    ]);
});

test('Channel 1 (private-citizen.{citizenId}): owner and admin get 200, others get 403 (§5.7, TASK-070-T)', function (): void {
    $channel = "private-citizen.{$this->citizenA->id}";

    // Citizen A authorized
    $resAuthorized = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ]);
    $resAuthorized->assertStatus(200)
        ->assertJsonStructure(['auth']);

    // Citizen B unauthorized -> 403
    $resUnauthorized = $this->actingAs($this->citizenB, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ]);
    $resUnauthorized->assertStatus(403);

    // System Admin authorized -> 200
    $resAdmin = $this->actingAs($this->adminOperator, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ]);
    $resAdmin->assertStatus(200);
});

test('Channel 2 (private-case.{caseId}): owner, assigned office operator and admin get 200, foreign operator gets 403 (§5.7, TASK-070-T)', function (): void {
    $channel = "private-case.{$this->caseA->id}";

    // Citizen A (owner) authorized -> 200
    $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(200);

    // Operator A (assigned office) authorized -> 200
    $this->actingAs($this->operatorA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(200);

    // Operator B (different office) unauthorized -> 403 (§7.3)
    $this->actingAs($this->operatorB, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);

    // Citizen B (unrelated citizen) unauthorized -> 403
    $this->actingAs($this->citizenB, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);

    // System admin authorized -> 200
    $this->actingAs($this->adminOperator, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(200);
});

test('Channel 3 (private-office.{officeId}): operator of Office A cannot subscribe to Office B (Horizontal Isolation §5.7, §7.3, TASK-070-T)', function (): void {
    $channelA = "private-office.{$this->officeA->id}";
    $channelB = "private-office.{$this->officeB->id}";

    // Operator A to Office A -> 200
    $this->actingAs($this->operatorA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channelA,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(200);

    // Operator A to Office B -> strictly 403
    $this->actingAs($this->operatorA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channelB,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);

    // Operator B to Office A -> strictly 403
    $this->actingAs($this->operatorB, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channelA,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);

    // Citizen attempting to subscribe to an office channel -> 403
    $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channelA,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);

    // System Admin to Office A -> 200
    $this->actingAs($this->adminOperator, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channelA,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(200);
});

test('Channel 4 (presence-office-desk.{officeId}): operator receives channel_data with counter and role, cross-office gets 403 (§5.7, TASK-070-T)', function (): void {
    $channel = "presence-office-desk.{$this->officeA->id}";

    // Operator A authorized to Office A desk -> 200 with presence channel_data
    $response = $this->actingAs($this->operatorA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'auth',
            'channel_data',
        ]);

    $channelData = json_decode((string) $response->json('channel_data'), true);
    expect($channelData['user_id'])->toBe($this->operatorA->id)
        ->and($channelData['user_info']['name'])->toBe('رضا اکبری')
        ->and($channelData['user_info']['counter_number'])->toBe(2)
        ->and($channelData['user_info']['role'])->toBe('operator');

    // Operator B cross-office access to Office A desk -> 403
    $this->actingAs($this->operatorB, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);

    // Citizen attempting to join presence desk -> 403
    $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);
});

test('Channel 5 (private-consultation.{sessionId}): admin gets 200, unauthorized gets 403 (§5.7, TASK-070-T)', function (): void {
    $channel = 'private-consultation.session-uuid-12345';

    // Admin authorized -> 200
    $this->actingAs($this->adminOperator, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(200);

    // Unrelated citizen without session record -> 403
    $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);
});

test('Channel 6 (private-admin.system): only system admin gets 200, regular operator and citizen get 403 (§5.7, TASK-070-T)', function (): void {
    $channel = 'private-admin.system';

    // System Admin authorized -> 200
    $this->actingAs($this->adminOperator, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(200);

    // Regular office operator unauthorized -> 403
    $this->actingAs($this->operatorA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);

    // Citizen unauthorized -> 403
    $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '1234.5678',
        ])
        ->assertStatus(403);
});
