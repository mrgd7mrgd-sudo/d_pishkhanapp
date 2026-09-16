<?php

declare(strict_types=1);

namespace Tests\Contract;

use App\Modules\AiAssistance\Domain\Enums\AiChannel;
use App\Modules\AiAssistance\Domain\Enums\AiMessageRole;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentAttendance;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentCompletion;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentReminderType;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentStatus;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Enums\PaymentGateway;
use App\Modules\Payments\Domain\Enums\PaymentIntentStatus;
use App\Modules\Payments\Domain\Enums\PayoutStatus;
use Illuminate\Support\Facades\File;

function extractTsStringArray(string $filePath, string $arrayName): array
{
    expect(File::exists($filePath))->toBeTrue("TypeScript file [{$filePath}] not found.");

    $content = File::get($filePath);
    $pattern = '/export\s+const\s+'.preg_quote($arrayName, '/').'\s*=\s*\[(.*?)\]\s*as\s*const;/s';
    if (! preg_match($pattern, $content, $matches)) {
        return [];
    }

    $raw = $matches[1] ?? '';
    preg_match_all("/['\"]([^'\"]+)['\"]/", $raw, $valMatches);

    return $valMatches[1] ?? [];
}

it('verifies exact parity between PHP ReturnReasonCode and TypeScript RETURN_REASON_CODES (§6.3, DoD: EnumParityTest)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../packages/domain/src/return-reasons.ts');
    expect($tsPath)->not->toBeFalse();

    $tsCodes = extractTsStringArray($tsPath, 'RETURN_REASON_CODES');
    expect($tsCodes)->not->toBeEmpty();

    $phpCodes = ReturnReasonCode::values();

    expect($phpCodes)->toBe($tsCodes)
        ->and(count($phpCodes))->toBe(10);
});

it('verifies exact parity between PHP CaseStatus and TypeScript CASE_STATUSES (§3.5, §6.1)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../packages/domain/src/case-status.ts');
    expect($tsPath)->not->toBeFalse();

    $tsStatuses = extractTsStringArray($tsPath, 'CASE_STATUSES');
    expect($tsStatuses)->not->toBeEmpty();

    $phpStatuses = CaseStatus::values();

    expect($phpStatuses)->toBe($tsStatuses)
        ->and(count($phpStatuses))->toBe(11);
});

it('verifies exact parity between PHP TurnOwner and TypeScript TURN_OWNERS (§4.7, §5.4)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../packages/domain/src/turn-owner.ts');
    expect($tsPath)->not->toBeFalse();

    $tsOwners = extractTsStringArray($tsPath, 'TURN_OWNERS');
    expect($tsOwners)->not->toBeEmpty();

    $phpOwners = TurnOwner::values();

    expect($phpOwners)->toBe($tsOwners)
        ->and(count($phpOwners))->toBe(5);
});

it('verifies exact parity between PHP TimelineStepStatus and TypeScript TIMELINE_STEP_STATUSES (§6.1)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../packages/domain/src/timeline-step-status.ts');
    expect($tsPath)->not->toBeFalse();

    $tsStepStatuses = extractTsStringArray($tsPath, 'TIMELINE_STEP_STATUSES');
    expect($tsStepStatuses)->not->toBeEmpty();

    $phpStepStatuses = TimelineStepStatus::values();

    expect($phpStepStatuses)->toBe($tsStepStatuses)
        ->and(count($phpStepStatuses))->toBe(5);
});

it('verifies exact parity between PHP and TypeScript ledger enums (§6.1, §6.3, TASK-054)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../packages/domain/src/ledger.ts');
    expect($tsPath)->not->toBeFalse();

    // Directions
    $tsDirections = extractTsStringArray($tsPath, 'LEDGER_DIRECTIONS');
    expect(LedgerDirection::values())->toBe($tsDirections)
        ->and(count($tsDirections))->toBe(2);

    // Account Kinds
    $tsKinds = extractTsStringArray($tsPath, 'LEDGER_ACCOUNT_KINDS');
    expect(LedgerAccountKind::values())->toBe($tsKinds)
        ->and(count($tsKinds))->toBe(5);

    // Owner Types
    $tsOwnerTypes = extractTsStringArray($tsPath, 'LEDGER_OWNER_TYPES');
    expect(LedgerOwnerType::values())->toBe($tsOwnerTypes)
        ->and(count($tsOwnerTypes))->toBe(5);

    // Transaction Types
    $tsTxTypes = extractTsStringArray($tsPath, 'LEDGER_TRANSACTION_TYPES');
    expect(LedgerTransactionType::values())->toBe($tsTxTypes)
        ->and(count($tsTxTypes))->toBe(7);
});

it('verifies exact parity between PHP DispatchOfferStatus and TypeScript DISPATCH_OFFER_STATUSES (§5.8, §6.1, TASK-066)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../packages/domain/src/dispatch.ts');
    expect($tsPath)->not->toBeFalse();

    $tsStatuses = extractTsStringArray($tsPath, 'DISPATCH_OFFER_STATUSES');
    expect($tsStatuses)->not->toBeEmpty();

    $phpStatuses = DispatchOfferStatus::values();

    expect($phpStatuses)->toBe($tsStatuses)
        ->and(count($phpStatuses))->toBe(4);
});

it('verifies exact parity between PHP and TypeScript payment enums (§6.1, §8.2, TASK-084)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../packages/domain/src/payment.ts');
    expect($tsPath)->not->toBeFalse();

    // Payment Intent Statuses
    $tsIntentStatuses = extractTsStringArray($tsPath, 'PAYMENT_INTENT_STATUSES');
    expect(PaymentIntentStatus::values())->toBe($tsIntentStatuses)
        ->and(count($tsIntentStatuses))->toBe(6);

    // Payment Gateways
    $tsGateways = extractTsStringArray($tsPath, 'PAYMENT_GATEWAYS');
    expect(PaymentGateway::values())->toBe($tsGateways)
        ->and(count($tsGateways))->toBe(3);

    // Payout Statuses
    $tsPayoutStatuses = extractTsStringArray($tsPath, 'PAYOUT_STATUSES');
    expect(PayoutStatus::values())->toBe($tsPayoutStatuses)
        ->and(count($tsPayoutStatuses))->toBe(4);
});

it('verifies exact parity between PHP and TypeScript appointment enums (§6.1, §6.3, TASK-099)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../packages/domain/src/appointment.ts');
    expect($tsPath)->not->toBeFalse();

    // Appointment Statuses
    $tsStatuses = extractTsStringArray($tsPath, 'APPOINTMENT_STATUSES');
    expect(AppointmentStatus::values())->toBe($tsStatuses)
        ->and(count($tsStatuses))->toBe(3);

    // Attendances
    $tsAttendances = extractTsStringArray($tsPath, 'APPOINTMENT_ATTENDANCES');
    expect(AppointmentAttendance::values())->toBe($tsAttendances)
        ->and(count($tsAttendances))->toBe(3);

    // Completions
    $tsCompletions = extractTsStringArray($tsPath, 'APPOINTMENT_COMPLETIONS');
    expect(AppointmentCompletion::values())->toBe($tsCompletions)
        ->and(count($tsCompletions))->toBe(4);

    // Reminder Types
    $tsReminderTypes = extractTsStringArray($tsPath, 'APPOINTMENT_REMINDER_TYPES');
    expect(AppointmentReminderType::values())->toBe($tsReminderTypes)
        ->and(count($tsReminderTypes))->toBe(3);
});

it('verifies exact parity between PHP and TypeScript AI assistance enums (§6.1, §6.3, TASK-107)', function (): void {
    $tsPath = realpath(__DIR__.'/../../../../packages/domain/src/ai-assistance.ts');
    expect($tsPath)->not->toBeFalse();

    // AI Channels
    $tsChannels = extractTsStringArray($tsPath, 'AI_CHANNELS');
    expect(AiChannel::values())->toBe($tsChannels)
        ->and(count($tsChannels))->toBe(2);

    // AI Message Roles
    $tsRoles = extractTsStringArray($tsPath, 'AI_MESSAGE_ROLES');
    expect(AiMessageRole::values())->toBe($tsRoles)
        ->and(count($tsRoles))->toBe(3);
});
