<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Queries;

use Carbon\CarbonInterface;

/**
 * Office Settlement Report DTO (Architecture §8.2, §5.9, TASK-090).
 */
final readonly class OfficeSettlementReportData
{
    /**
     * @param  list<array<string, mixed>>  $cases
     */
    public function __construct(
        public string $officeId,
        public string $officeCode,
        public string $officeName,
        public CarbonInterface $periodStart,
        public CarbonInterface $periodEnd,
        public int $totalCasesCount,
        public int $totalFeeRials,
        public int $officeShareRials,
        public int $platformShareRials,
        public int $periodCreditsRials,
        public int $periodDebitsRials,
        public int $periodNetPayableRials,
        public int $currentBalanceRials,
        public bool $isOfficeLedgerConsistent,
        public bool $isGlobalLedgerBalanced,
        public int $discrepancyRials,
        public array $cases = []
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'office_id' => $this->officeId,
            'office_code' => $this->officeCode,
            'office_name' => $this->officeName,
            'period_start' => $this->periodStart->toIso8601String(),
            'period_end' => $this->periodEnd->toIso8601String(),
            'total_cases_count' => $this->totalCasesCount,
            'total_fee_rials' => $this->totalFeeRials,
            'office_share_rials' => $this->officeShareRials,
            'platform_share_rials' => $this->platformShareRials,
            'period_credits_rials' => $this->periodCreditsRials,
            'period_debits_rials' => $this->periodDebitsRials,
            'period_net_payable_rials' => $this->periodNetPayableRials,
            'current_balance_rials' => $this->currentBalanceRials,
            'is_office_ledger_consistent' => $this->isOfficeLedgerConsistent,
            'is_global_ledger_balanced' => $this->isGlobalLedgerBalanced,
            'discrepancy_rials' => $this->discrepancyRials,
            'cases' => $this->cases,
        ];
    }
}
