<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Http\Controllers;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Exceptions\InvalidCaseTransitionException;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\CaseWorkflow\Http\Resources\CaseDetailResource;
use App\Modules\CaseWorkflow\Http\Resources\CaseResource;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CaseReadController
{
    /**
     * List cases for authenticated user with status filtering and cursor pagination (Architecture §5.6, §7.7).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = CaseRequest::query()
            ->with(['service', 'office'])
            ->orderByDesc('created_at');

        if ($user instanceof Citizen) {
            $query->where('citizen_id', $user->id);
        } elseif ($user instanceof Operator) {
            if (! $user->hasRole('admin') && ! $user->hasRole('auditor')) {
                $query->where('office_id', $user->office_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        $perPage = min(50, max(1, $request->integer('per_page', 15)));
        $cases = $query->cursorPaginate($perPage);

        return new JsonResponse([
            'data' => CaseResource::collection($cases->items())->resolve(),
            'meta' => [
                'per_page' => $cases->perPage(),
                'next_cursor' => $cases->nextCursor()?->encode(),
                'prev_cursor' => $cases->previousCursor()?->encode(),
            ],
        ]);
    }

    /**
     * Show case detail strictly matching §5.6 Sample 6.
     */
    public function show(string $trackingCode, Request $request): JsonResponse
    {
        $case = $this->findAuthorizedCase($trackingCode, $request);

        return new JsonResponse([
            'data' => (new CaseDetailResource($case))->resolve(),
        ]);
    }

    /**
     * Cancel an active case with full refund (Architecture §5.6, §6.2, §12.3).
     */
    public function cancel(
        string $id,
        Request $request,
        CaseStateMachine $stateMachine,
        LedgerService $ledgerService
    ): JsonResponse {
        $case = $this->findAuthorizedCase($id, $request);

        if (! $stateMachine->canTransition($case->status, CaseStatus::CANCELLED)) {
            throw new InvalidCaseTransitionException(
                from: $case->status,
                to: CaseStatus::CANCELLED,
                message: 'امکان انصراف از پرونده در وضعیت فعلی وجود ندارد.'
            );
        }

        $user = $request->user();
        $actorId = $user?->getAuthIdentifier();

        DB::transaction(function () use ($case, $stateMachine, $ledgerService, $actorId): void {
            $stateMachine->transition(
                case: $case,
                to: CaseStatus::CANCELLED,
                ctx: new TransitionContext(
                    actorType: TimelineActorType::CITIZEN,
                    actorId: $actorId,
                    title: 'انصراف از درخواست',
                    description: 'درخواست توسط شهروند لغو شد و وجه به کیف پول بازگردانده شد.',
                    stepStatus: TimelineStepStatus::DONE,
                    reasonCode: 'USER_CANCELLED'
                )
            );

            // Reverse ledger entries from Escrow back to Citizen Wallet (§6.2 line 2995)
            if ($case->fee_paid_rials > 0) {
                $this->refundCaseFee($case, $ledgerService);
            }
        });

        $case->load(['service.category', 'office', 'timelineSteps', 'documents', 'returns.returnReason']);

        return new JsonResponse([
            'data' => (new CaseDetailResource($case))->resolve(),
        ]);
    }

    private function findAuthorizedCase(string $identifier, Request $request): CaseRequest
    {
        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()
            ->with(['service.category', 'office', 'timelineSteps', 'documents', 'returns.returnReason'])
            ->where('tracking_code', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        if ($case === null) {
            throw new NotFoundHttpException('پرونده مورد نظر یافت نشد.');
        }

        $user = $request->user();
        if ($user instanceof Citizen && $user->id !== $case->citizen_id) {
            // Conceal existence to prevent ID enumeration (§7.3)
            throw new NotFoundHttpException('پرونده مورد نظر یافت نشد.');
        }

        return $case;
    }

    private function refundCaseFee(CaseRequest $case, LedgerService $ledgerService): void
    {
        $wallet = $ledgerService->getOrCreateAccount(
            ownerType: LedgerOwnerType::CITIZEN,
            ownerId: $case->citizen_id,
            kind: LedgerAccountKind::WALLET
        );

        $escrow = $ledgerService->getOrCreateAccount(
            ownerType: LedgerOwnerType::PLATFORM,
            ownerId: null,
            kind: LedgerAccountKind::ESCROW
        );

        $ledgerService->recordTransaction(
            reference: 'REFUND-'.$case->tracking_code,
            type: LedgerTransactionType::REFUND,
            entries: [
                new LedgerEntryData(
                    account: $escrow,
                    direction: LedgerDirection::DEBIT,
                    amountRials: $case->fee_paid_rials
                ),
                new LedgerEntryData(
                    account: $wallet,
                    direction: LedgerDirection::CREDIT,
                    amountRials: $case->fee_paid_rials
                ),
            ],
            description: "استرداد وجه انصراف پرونده {$case->tracking_code}",
            caseId: $case->id
        );
    }
}
