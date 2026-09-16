<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Modules\AiAssistance\Domain\Enums\AiChannel;
use App\Modules\AiAssistance\Domain\Enums\AiMessageRole;
use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\AiAssistance\Domain\Models\AiMessage;
use App\Modules\AiAssistance\Domain\Models\AiUsageRecord;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('verifies AI tables schema existence and column constraints (§6.1, TASK-107, TASK-107-T)', function (): void {
    // 1. ai_conversations table
    expect(Schema::hasTable('ai_conversations'))->toBeTrue();
    expect(Schema::hasColumns('ai_conversations', ['id', 'citizen_id', 'channel', 'title', 'created_at', 'updated_at']))->toBeTrue();

    // 2. ai_messages table
    expect(Schema::hasTable('ai_messages'))->toBeTrue();
    expect(Schema::hasColumns('ai_messages', ['id', 'conversation_id', 'role', 'content', 'citations', 'suggested_actions', 'created_at']))->toBeTrue();

    // 3. ai_usage_records table
    expect(Schema::hasTable('ai_usage_records'))->toBeTrue();
    expect(Schema::hasColumns('ai_usage_records', ['id', 'conversation_id', 'model', 'input_tokens', 'output_tokens', 'cost_rials', 'created_at']))->toBeTrue();
});

it('verifies cost_rials is integer and persists usage correctly (§6.1, TASK-107-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0012345678';
    $citizen->mobile = '09121112233';
    $citizen->full_name = 'رضا علوی';
    $citizen->save();

    $conv = AiConversation::create([
        'citizen_id' => $citizen->id,
        'channel' => AiChannel::Chat,
        'title' => 'پرسش درباره شناسنامه',
    ]);

    $usage = AiUsageRecord::create([
        'conversation_id' => $conv->id,
        'model' => 'google/gemini-2.5-flash',
        'input_tokens' => 250,
        'output_tokens' => 120,
        'cost_rials' => 15000,
    ]);

    expect($usage->cost_rials)->toBeInt()
        ->and($usage->cost_rials)->toBe(15000)
        ->and($usage->input_tokens)->toBe(250)
        ->and($usage->output_tokens)->toBe(120);

    // Verify DB row
    $row = DB::table('ai_usage_records')->where('id', $usage->id)->first();
    expect($row)->not->toBeNull();
    expect((int) $row->cost_rials)->toBe(15000);
});

it('verifies relationships between conversation, messages and usage records (§6.1, §8.1, TASK-107-T)', function (): void {
    $citizen = new Citizen;
    $citizen->national_id = '0098765432';
    $citizen->mobile = '09123334455';
    $citizen->full_name = 'سارا احمدی';
    $citizen->save();

    $conv = AiConversation::create([
        'citizen_id' => $citizen->id,
        'channel' => AiChannel::Chat,
        'title' => 'راهنمایی تعویض پلاک',
    ]);

    $userMsg = AiMessage::create([
        'conversation_id' => $conv->id,
        'role' => AiMessageRole::User,
        'content' => 'برای تعویض پلاک چه مراحلی دارد؟',
    ]);

    $aiMsg = AiMessage::create([
        'conversation_id' => $conv->id,
        'role' => AiMessageRole::Assistant,
        'content' => 'ابتدا باید نوبت اینترنتی دریافت نمایید.',
        'citations' => [
            ['service_id' => 'srv-plaque', 'title' => 'تعویض پلاک'],
        ],
        'suggested_actions' => [
            ['type' => 'open_service', 'service_id' => 'srv-plaque', 'label' => 'ثبت درخواست تعویض پلاک'],
        ],
    ]);

    $usage = AiUsageRecord::create([
        'conversation_id' => $conv->id,
        'model' => 'google/gemini-2.5-flash',
        'input_tokens' => 100,
        'output_tokens' => 80,
        'cost_rials' => 9000,
    ]);

    expect($conv->messages)->toHaveCount(2)
        ->and($conv->messages->first()->role)->toBe(AiMessageRole::User)
        ->and($conv->messages->last()->citations)->toBeArray()
        ->and($conv->messages->last()->suggested_actions)->toBeArray()
        ->and($conv->usageRecords)->toHaveCount(1)
        ->and($conv->citizen->id)->toBe($citizen->id);
});

it('verifies postgres monthly range partitioning for ai_usage_records when on pgsql (§6.1, §6.5, TASK-107-T)', function (): void {
    $driver = config('database.default');
    if ($driver !== 'pgsql') {
        expect(true)->toBeTrue();

        return;
    }

    $partitions = DB::select("
        SELECT relname
        FROM pg_class c
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE c.relispartition = true
          AND relname LIKE 'ai_usage_records_%'
    ");

    expect(count($partitions))->toBeGreaterThanOrEqual(12);
});
