<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Queries;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Exceptions\LedgerDiscrepancyException;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerAccount;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Office Settlement Report Query (Architecture §8.2, §5.9, TASK-090).
 * Generates audit-ready daily settlement reports matching double-entry ledger entries.
 */
final class OfficeSettlementReport
{
    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * Generate settlement report for a specific office and period.
     */
    public function generate(
        string $officeId,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd
    ): OfficeSettlementReportData {
        /** @var Office $office */
        $office = Office::query()->findOrFail($officeId);

        /** @var LedgerAccount|null $officeAccount */
        $officeAccount = LedgerAccount::query()
            ->where('owner_type', LedgerOwnerType::OFFICE->value)
            ->where('owner_id', $officeId)
            ->where('kind', LedgerAccountKind::PAYABLE->value)
            ->first();

        $integrity = $this->verifyGlobalLedgerIntegrity();

        if ($officeAccount === null) {
            return $this->buildEmptyReport($office, $periodStart, $periodEnd, $integrity['balanced']);
        }

        $entries = $this->queryPeriodEntries($officeAccount->id, $periodStart, $periodEnd);

        $periodCredits = (int) $entries->where('direction', LedgerDirection::CREDIT->value)->sum('amount_rials');
        $periodDebits = (int) $entries->where('direction', LedgerDirection::DEBIT->value)->sum('amount_rials');
        $netPayable = $periodCredits - $periodDebits;
        $currentBalance = $this->ledgerService->getBalanceRials($officeAccount);

        $casesBreakdown = $this->extractCasesBreakdown($entries);
        $totalCasesCount = count($casesBreakdown);
        $totalFeeRials = (int) array_sum(array_column($casesBreakdown, 'fee_paid_rials'));
        $officeShareRials = (int) array_sum(array_column($casesBreakdown, 'office_share_rials'));
        $platformShareRials = (int) array_sum(array_column($casesBreakdown, 'platform_share_rials'));

        $discrepancy = abs($periodCredits - $officeShareRials);
        $isConsistent = ($discrepancy === 0);

        return new OfficeSettlementReportData(
            officeId: $office->id,
            officeCode: $office->code,
            officeName: $office->name,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            totalCasesCount: $totalCasesCount,
            totalFeeRials: $totalFeeRials,
            officeShareRials: $officeShareRials,
            platformShareRials: $platformShareRials,
            periodCreditsRials: $periodCredits,
            periodDebitsRials: $periodDebits,
            periodNetPayableRials: $netPayable,
            currentBalanceRials: $currentBalance,
            isOfficeLedgerConsistent: $isConsistent,
            isGlobalLedgerBalanced: $integrity['balanced'],
            discrepancyRials: $discrepancy,
            cases: $casesBreakdown
        );
    }

    /**
     * Verify global ledger double-entry invariant: SUM(debit) === SUM(credit).
     *
     * @return array{balanced: bool, total_debit: int, total_credit: int, discrepancy: int}
     */
    public function verifyGlobalLedgerIntegrity(): array
    {
        $totalDebits = (int) DB::table('ledger_entries')
            ->where('direction', LedgerDirection::DEBIT->value)
            ->sum('amount_rials');

        $totalCredits = (int) DB::table('ledger_entries')
            ->where('direction', LedgerDirection::CREDIT->value)
            ->sum('amount_rials');

        $discrepancy = abs($totalDebits - $totalCredits);
        $balanced = ($discrepancy === 0);

        if (! $balanced) {
            Log::critical('CRITICAL_LEDGER_IMBALANCE: Ledger double-entry invariant violated.', [
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'discrepancy' => $discrepancy,
            ]);
        }

        return [
            'balanced' => $balanced,
            'total_debit' => $totalDebits,
            'total_credit' => $totalCredits,
            'discrepancy' => $discrepancy,
        ];
    }

    /**
     * Assert global double-entry integrity, throwing LedgerDiscrepancyException if violated.
     *
     * @throws LedgerDiscrepancyException
     */
    public function assertGlobalLedgerBalanced(): void
    {
        $integrity = $this->verifyGlobalLedgerIntegrity();

        if (! $integrity['balanced']) {
            throw new LedgerDiscrepancyException(
                totalDebits: $integrity['total_debit'],
                totalCredits: $integrity['total_credit'],
                discrepancy: $integrity['discrepancy']
            );
        }
    }

    /**
     * @return Collection<int, \stdClass>
     */
    private function queryPeriodEntries(
        string $accountId,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd
    ): Collection {
        return DB::table('ledger_entries')
            ->join('ledger_transactions', 'ledger_entries.transaction_id', '=', 'ledger_transactions.id')
            ->where('ledger_entries.account_id', $accountId)
            ->whereBetween('ledger_transactions.created_at', [$periodStart, $periodEnd])
            ->select([
                'ledger_entries.direction',
                'ledger_entries.amount_rials',
                'ledger_transactions.id as transaction_id',
                'ledger_transactions.case_id',
                'ledger_transactions.reference',
                'ledger_transactions.created_at',
            ])
            ->get();
    }

    /**
     * @param  Collection<int, \stdClass>  $entries
     * @return list<array<string, mixed>>
     */
    private function extractCasesBreakdown(Collection $entries): array
    {
        $caseIds = $entries->pluck('case_id')->filter()->unique()->values()->all();

        if (empty($caseIds)) {
            return [];
        }

        /** @var Collection<int, CaseRequest> $cases */
        $cases = CaseRequest::query()->with('service')->whereIn('id', $caseIds)->get();

        $result = [];
        foreach ($cases as $case) {
            $result[] = [
                'case_id' => $case->id,
                'tracking_code' => $case->tracking_code,
                'service_title' => $case->service->title ?? 'خدمت',
                'fee_paid_rials' => $case->fee_paid_rials,
                'office_share_rials' => $case->office_share_rials,
                'platform_share_rials' => $case->platform_share_rials,
                'completed_at' => $case->updated_at?->toIso8601String(),
            ];
        }

        return $result;
    }

    private function buildEmptyReport(
        Office $office,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        bool $isGlobalBalanced
    ): OfficeSettlementReportData {
        return new OfficeSettlementReportData(
            officeId: $office->id,
            officeCode: $office->code,
            officeName: $office->name,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            totalCasesCount: 0,
            totalFeeRials: 0,
            officeShareRials: 0,
            platformShareRials: 0,
            periodCreditsRials: 0,
            periodDebitsRials: 0,
            periodNetPayableRials: 0,
            currentBalanceRials: 0,
            isOfficeLedgerConsistent: true,
            isGlobalLedgerBalanced: $isGlobalBalanced,
            discrepancyRials: 0,
            cases: []
        );
    }
}
