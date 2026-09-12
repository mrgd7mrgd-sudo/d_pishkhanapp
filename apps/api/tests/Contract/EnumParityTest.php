<?php

declare(strict_types=1);

namespace Tests\Contract;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
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
