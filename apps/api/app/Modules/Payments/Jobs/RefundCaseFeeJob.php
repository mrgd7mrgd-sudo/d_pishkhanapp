<?php

declare(strict_types=1);

namespace App\Modules\Payments\Jobs;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Payments\Application\Actions\IssueRefundAction;
use App\Modules\Payments\Domain\Enums\RefundReason;
use App\Modules\Payments\Domain\Exceptions\RefundAlreadyProcessedException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RefundCaseFeeJob (Architecture §8.2, §3.5, TASK-088).
 * Executes full or partial refund for a case through Payments module.
 */
final class RefundCaseFeeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15, 45];

    public function __construct(
        public readonly string $caseId,
        public readonly RefundReason|string $reason = RefundReason::DISPATCH_EXHAUSTED
    ) {
        $this->onQueue('ledger');
    }

    public function handle(IssueRefundAction $action): void
    {
        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()->find($this->caseId);
        if ($case === null || $case->fee_paid_rials <= 0) {
            return;
        }

        try {
            $action->execute($case, $this->reason);
        } catch (RefundAlreadyProcessedException $e) {
            Log::info('Refund already processed for case, skipping duplicate execution.', [
                'case_id' => $this->caseId,
            ]);
        }
    }
}
