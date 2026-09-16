<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Application\Actions;

use App\Modules\Consultation\Domain\Enums\AdvisorApplicationStatus;
use App\Modules\Consultation\Domain\Enums\ConsultationMode;
use App\Modules\Consultation\Domain\Enums\ConsultationSessionStatus;
use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Consultation\Domain\Models\ConsultationSession;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\LedgerService;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class StartSessionAction
{
    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * Start a consultation session (§5.3, TASK-119).
     * Validates advisor availability and minimum wallet balance for estimated initial fee.
     */
    public function execute(
        Citizen $citizen,
        string $advisorId,
        ConsultationMode $mode,
        ?string $linkedServiceId = null
    ): ConsultationSession {
        /** @var Advisor|null $advisor */
        $advisor = Advisor::query()->find($advisorId);
        if ($advisor === null || $advisor->application_status !== AdvisorApplicationStatus::Approved) {
            throw new InvalidArgumentException('مشاور مورد نظر معتبر یا تأیید شده نیست.');
        }

        if ($advisor->citizen_id === $citizen->id) {
            throw new InvalidArgumentException('مشاور نمی‌تواند با خود جلسه مشاوره برگزار کند.');
        }

        // Estimate upfront required balance to avoid starting sessions with 0 balance
        $minimumRequiredRials = match ($mode) {
            ConsultationMode::Call => (int) $advisor->price_phone_per_minute_rials,
            ConsultationMode::CaseReview => (int) $advisor->price_deep_review_rials,
            ConsultationMode::Text => (int) $advisor->price_text_chat_rials,
        };

        $citizenWallet = $this->ledgerService->getOrCreateAccount(
            LedgerOwnerType::CITIZEN,
            $citizen->id,
            LedgerAccountKind::WALLET
        );

        if (! $this->ledgerService->hasSufficientBalance($citizenWallet, $minimumRequiredRials)) {
            throw new InvalidArgumentException('موجودی کیف پول برای شروع جلسه مشاوره کافی نیست.');
        }

        return DB::transaction(function () use ($citizen, $advisor, $mode, $linkedServiceId): ConsultationSession {
            $trackingCode = 'CS-'.now()->format('ymd').'-'.strtoupper(Str::random(4));

            $session = ConsultationSession::query()->create([
                'id' => (string) Str::uuid(),
                'advisor_id' => $advisor->id,
                'citizen_id' => $citizen->id,
                'mode' => $mode,
                'status' => ConsultationSessionStatus::Active,
                'duration_seconds' => 0,
                'total_fee_rials' => 0,
                'tracking_code' => $trackingCode,
                'uploaded_docs_count' => 0,
                'advisor_verdict' => null,
                'linked_service_id' => $linkedServiceId,
                'started_at' => CarbonImmutable::now(),
                'ended_at' => null,
            ]);

            AuditLogger::record(
                action: 'consultation.session_started',
                subject: $session,
                changes: [
                    'session_id' => $session->id,
                    'advisor_id' => $advisor->id,
                    'citizen_id' => $citizen->id,
                    'mode' => $mode->value,
                    'tracking_code' => $trackingCode,
                ],
                actorType: Citizen::class,
                actorId: $citizen->id
            );

            return $session->fresh(['advisor', 'citizen']);
        });
    }
}
