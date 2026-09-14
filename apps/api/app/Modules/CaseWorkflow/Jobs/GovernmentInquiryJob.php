<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Jobs;

use App\Integration\Government\CivilRegistryClient;
use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\GovInquiryProvider;
use App\Modules\CaseWorkflow\Domain\Enums\GovInquiryStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\GovInquiry;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use App\Shared\Resilience\CircuitBreaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * GovernmentInquiryJob (Architecture §5.8, §8.5, TASK-075).
 * Processes online government inquiry with exponential backoff and Circuit Breaker.
 * Queue: inquiries (priority 3).
 */
final class GovernmentInquiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 8;

    public int $timeout = 120;

    /**
     * Exponential backoff in seconds strictly conforming to §5.8 & §8.5:
     * [60, 300, 900, 1800, 3600, 7200, 14400, 21600]
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600, 7200, 14400, 21600];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new CircuitBreaker('civil-registry', failures: 5, cooldownSeconds: 300),
        ];
    }

    public function __construct(
        public readonly string $caseId,
        public readonly GovInquiryProvider $provider = GovInquiryProvider::CIVIL_REGISTRY,
        string $queueName = 'inquiries'
    ) {
        $this->onQueue($queueName);
    }

    public function handle(
        CivilRegistryClient $civilRegistryClient,
        CaseStateMachine $stateMachine
    ): void {
        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()->with('citizen')->find($this->caseId);

        if (! $case || $case->status !== CaseStatus::GOVERNMENT_INQUIRY) {
            return;
        }

        $citizen = $case->citizen;
        $nationalId = (string) ($citizen?->national_id ?? '');
        $birthDate = '1370-01-01';

        $initialRequestSnapshot = [
            'national_id' => $nationalId,
            'case_id' => $case->id,
            'provider' => $this->provider->value,
        ];

        /** @var GovInquiry $inquiry */
        $inquiry = GovInquiry::query()->firstOrCreate(
            [
                'case_id' => $case->id,
                'provider' => $this->provider,
            ],
            [
                'id' => (string) Str::uuid(),
                'status' => GovInquiryStatus::IN_PROGRESS,
                'attempts' => 0,
                'request_snapshot' => $initialRequestSnapshot,
            ]
        );

        $inquiry->recordAttempt();

        $inquiry->request_snapshot = array_merge($initialRequestSnapshot, [
            'attempt' => $inquiry->attempts,
        ]);
        $inquiry->save();

        try {
            $summary = $civilRegistryClient->getPersonSummary($nationalId, $birthDate);

            $responseSnapshot = [
                'is_alive' => $summary->isAlive,
                'national_id' => $summary->nationalId,
                'first_name' => $summary->firstName,
                'last_name' => $summary->lastName,
                'father_name' => $summary->fatherName,
                'is_eligible' => $summary->isEligible,
                'ineligible_reason' => $summary->ineligibleReason,
            ];

            if ($summary->isEligible) {
                // Scenario 0: Success
                $inquiry->markSucceeded($responseSnapshot);

                $ctx = new TransitionContext(
                    title: 'پاسخ مثبت استعلام دولتی',
                    description: 'استعلام هویتی با موفقیت از ثبت احوال تأیید گردید.',
                    stepStatus: TimelineStepStatus::DONE,
                    actorType: TimelineActorType::GOVERNMENT,
                    metadata: ['inquiry_id' => $inquiry->id]
                );

                $stateMachine->transition($case, CaseStatus::READY_FOR_ISSUE, $ctx);

                SendCaseNotificationJob::dispatch(
                    citizenId: $case->citizen_id,
                    type: 'case-ready',
                    title: 'استعلام تایید شد',
                    body: "استعلام پرونده {$case->tracking_code} تایید شد و آماده صدور است.",
                    smsTemplate: 'case-ready',
                    smsParams: ['tracking' => $case->tracking_code],
                    payload: ['case_id' => $case->id]
                );

                AuditLogger::record(
                    AuditableAction::CASE_TRANSITION,
                    $case,
                    ['status' => CaseStatus::READY_FOR_ISSUE->value],
                    ['inquiry_status' => 'succeeded'],
                    'system',
                    'system'
                );

                return;
            }

            if ($summary->ineligibleReason === 'INQUIRY_MISMATCH') {
                // Scenario 1: Mismatch
                $inquiry->markMismatch('INQUIRY_MISMATCH', $responseSnapshot);

                $ctx = new TransitionContext(
                    title: 'عدم تطابق اطلاعات با سامانه دولتی',
                    description: 'اطلاعات هویتی با پایگاه ثبت احوال همخوانی ندارد. لطفاً مدارک را بررسی و اصلاح نمایید.',
                    stepStatus: TimelineStepStatus::WARNING,
                    actorType: TimelineActorType::GOVERNMENT,
                    reasonCode: 'INQUIRY_MISMATCH',
                    metadata: ['inquiry_id' => $inquiry->id]
                );

                $stateMachine->transition($case, CaseStatus::ACTION_REQUIRED, $ctx);

                SendCaseNotificationJob::dispatch(
                    citizenId: $case->citizen_id,
                    type: 'case-returned',
                    title: 'مغایرت در استعلام دولتی',
                    body: "پرونده {$case->tracking_code} به دلیل مغایرت اطلاعات با ثبت احوال نیازمند بازنگری است.",
                    smsTemplate: 'case-returned',
                    smsParams: ['tracking' => $case->tracking_code, 'hours' => 72],
                    payload: ['case_id' => $case->id]
                );

                AuditLogger::record(
                    AuditableAction::CASE_TRANSITION,
                    $case,
                    ['status' => CaseStatus::ACTION_REQUIRED->value, 'reason' => 'INQUIRY_MISMATCH'],
                    ['inquiry_status' => 'mismatch'],
                    'system',
                    'system'
                );

                return;
            }

            if ($summary->ineligibleReason === 'ELIGIBILITY_FAIL') {
                // Scenario 4: Eligibility Fail
                $inquiry->markFailed('ELIGIBILITY_FAIL', $responseSnapshot);

                $ctx = new TransitionContext(
                    title: 'رد پرونده به دلیل عدم احراز شرایط قانونی',
                    description: 'استعلام دولتی نشان‌دهنده عدم احراز شرایط قانونی برای دریافت این خدمت است.',
                    stepStatus: TimelineStepStatus::FAILED,
                    actorType: TimelineActorType::GOVERNMENT,
                    reasonCode: 'ELIGIBILITY_FAIL',
                    metadata: ['inquiry_id' => $inquiry->id]
                );

                $stateMachine->transition($case, CaseStatus::REJECTED, $ctx);

                SendCaseNotificationJob::dispatch(
                    citizenId: $case->citizen_id,
                    type: 'case_rejected',
                    title: 'رد درخواست',
                    body: "پرونده {$case->tracking_code} به علت عدم احراز شرایط قانونی رد گردید.",
                    payload: ['case_id' => $case->id]
                );

                AuditLogger::record(
                    AuditableAction::CASE_TRANSITION,
                    $case,
                    ['status' => CaseStatus::REJECTED->value, 'reason' => 'ELIGIBILITY_FAIL'],
                    ['inquiry_status' => 'failed'],
                    'system',
                    'system'
                );

                return;
            }

            // Fallback for unexpected outcome
            $inquiry->markFailed('UNKNOWN_OUTCOME', $responseSnapshot);
        } catch (Throwable $e) {
            $inquiry->last_error = $e->getMessage();
            $inquiry->save();

            // Scenario E24: On 500 or CircuitBreaker failure, case remains in government_inquiry
            // Re-throw so worker attempts retry or circuit breaker activates
            throw $e;
        }
    }
}
