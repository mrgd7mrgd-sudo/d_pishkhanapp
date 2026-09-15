<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Payments\Application\Queries\OfficeSettlementReport;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Models\LedgerAccount;
use App\Modules\Payments\Domain\Models\Payout;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * DeskFinanceController (Architecture §4.4, §7.3, TASK-093).
 * Provides finance, payouts and ledger discrepancy reporting exclusively to office managers.
 */
final class DeskFinanceController
{
    /**
     * GET /desk/finance
     */
    public function index(Request $request, OfficeSettlementReport $settlementReport): JsonResponse
    {
        $operator = $this->resolveManager($request);

        $periodStart = $request->has('period_start')
            ? Carbon::parse((string) $request->query('period_start'))
            : Carbon::now()->startOfMonth();
        $periodEnd = $request->has('period_end')
            ? Carbon::parse((string) $request->query('period_end'))
            : Carbon::now();

        $reportData = $settlementReport->generate((string) $operator->office_id, $periodStart, $periodEnd);
        $payouts = $this->fetchRecentPayouts((string) $operator->office_id);
        $chartPoints = $this->fetchRevenueChart((string) $operator->office_id);

        return new JsonResponse([
            'data' => [
                'office' => [
                    'id' => $reportData->officeId,
                    'code' => $reportData->officeCode,
                    'name' => $reportData->officeName,
                ],
                'payable_balance_rials' => $reportData->currentBalanceRials,
                'period_summary' => [
                    'period_start' => $reportData->periodStart->toIso8601String(),
                    'period_end' => $reportData->periodEnd->toIso8601String(),
                    'total_cases_count' => $reportData->totalCasesCount,
                    'total_fee_rials' => $reportData->totalFeeRials,
                    'office_share_rials' => $reportData->officeShareRials,
                    'platform_share_rials' => $reportData->platformShareRials,
                    'period_credits_rials' => $reportData->periodCreditsRials,
                    'period_debits_rials' => $reportData->periodDebitsRials,
                    'period_net_payable_rials' => $reportData->periodNetPayableRials,
                ],
                'ledger_integrity' => [
                    'is_office_ledger_consistent' => $reportData->isOfficeLedgerConsistent,
                    'is_global_ledger_balanced' => $reportData->isGlobalLedgerBalanced,
                    'discrepancy_rials' => $reportData->discrepancyRials,
                ],
                'recent_payouts' => $payouts,
                'revenue_chart' => $chartPoints,
            ],
        ]);
    }

    private function resolveManager(Request $request): Operator
    {
        $operator = $request->user();
        if (! $operator instanceof Operator || $operator->office_id === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دسترسی غیرمجاز.',
            ], 404));
        }

        $isManager = $operator->hasRole('office_manager') || $operator->role === OperatorRole::MANAGER;
        if (! $isManager) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'code' => 'FORBIDDEN_NOT_OFFICE_MANAGER',
                'detail' => 'دسترسی غیرمجاز — این بخش فقط مختص مدیر دفتر است.',
            ], 403));
        }

        return $operator;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRecentPayouts(string $officeId): array
    {
        return Payout::query()
            ->where('office_id', $officeId)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(fn (Payout $p): array => [
                'id' => $p->id,
                'amount_rials' => $p->amount_rials,
                'status' => $p->status->value,
                'period_start' => $p->period_start->toIso8601String(),
                'period_end' => $p->period_end->toIso8601String(),
                'total_cases_count' => $p->total_cases_count,
                'reference_number' => $p->reference_number,
                'generated_at' => $p->generated_at->toIso8601String(),
                'processed_at' => $p->processed_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{date: string, office_share_rials: int, cases_count: int}>
     */
    private function fetchRevenueChart(string $officeId): array
    {
        /** @var LedgerAccount|null $officeAccount */
        $officeAccount = LedgerAccount::query()
            ->where('owner_type', LedgerOwnerType::OFFICE->value)
            ->where('owner_id', $officeId)
            ->where('kind', LedgerAccountKind::PAYABLE->value)
            ->first();

        if ($officeAccount === null) {
            return [];
        }

        /** @var Collection<int, \stdClass> $rows */
        $rows = DB::table('ledger_entries')
            ->join('ledger_transactions', 'ledger_entries.transaction_id', '=', 'ledger_transactions.id')
            ->where('ledger_entries.account_id', $officeAccount->id)
            ->where('ledger_entries.direction', LedgerDirection::CREDIT->value)
            ->where('ledger_transactions.created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(ledger_transactions.created_at) as date, SUM(ledger_entries.amount_rials) as office_share_rials, COUNT(DISTINCT ledger_transactions.case_id) as cases_count')
            ->groupBy(DB::raw('DATE(ledger_transactions.created_at)'))
            ->orderBy('date')
            ->get();

        $points = [];
        foreach ($rows as $row) {
            $points[] = [
                'date' => (string) $row->date,
                'office_share_rials' => (int) $row->office_share_rials,
                'cases_count' => (int) $row->cases_count,
            ];
        }

        return $points;
    }
}
