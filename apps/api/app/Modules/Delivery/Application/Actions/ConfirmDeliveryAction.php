<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Application\Actions;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Delivery\Domain\DeliveryOtp;
use App\Modules\Delivery\Domain\Enums\DeliveryStatus;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Modules\Payments\Jobs\SettleCaseFeeJob;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Validates OTP and confirms delivery completion (Architecture §3.5, §5.3, §7.1, §7.3, TASK-096).
 */
final class ConfirmDeliveryAction
{
    public const MAX_ATTEMPTS = 5;

    public const ATTEMPTS_BEFORE_NEW_OTP = 3;

    public function __construct(
        private readonly CaseStateMachine $stateMachine
    ) {}

    public function execute(
        string $deliveryId,
        string $rawOtp,
        ?Operator $operator = null
    ): DeliveryRequest {
        $delivery = $this->findAuthorizedDelivery($deliveryId, $operator);

        if ($delivery->delivery_status === DeliveryStatus::DELIVERED) {
            return $delivery;
        }

        $this->ensureCanConfirm($delivery);
        $this->verifyAttemptsAndOtp($delivery, $rawOtp);

        return DB::transaction(function () use ($delivery, $operator): DeliveryRequest {
            $this->markAsDelivered($delivery);
            $this->completeAssociatedCase($delivery, $operator);
            $this->dispatchSuccessNotifications($delivery);
            $this->recordAudit($delivery, $operator);

            return $delivery;
        });
    }

    private function findAuthorizedDelivery(string $deliveryId, ?Operator $operator): DeliveryRequest
    {
        /** @var DeliveryRequest|null $delivery */
        $delivery = DeliveryRequest::query()->with('case')->where('id', $deliveryId)->first();

        if ($delivery === null || ($operator !== null && $delivery->office_id !== $operator->office_id)) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'Delivery request not found.',
            ], 404));
        }

        return $delivery;
    }

    private function ensureCanConfirm(DeliveryRequest $delivery): void
    {
        $allowed = [DeliveryStatus::IN_TRANSIT, DeliveryStatus::COURIER_ASSIGNED, DeliveryStatus::READY_FOR_DISPATCH];

        if (! in_array($delivery->delivery_status, $allowed, true)) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'code' => 'INVALID_DELIVERY_STATUS',
                'detail' => "Cannot confirm delivery from status [{$delivery->delivery_status->value}].",
            ], 422));
        }
    }

    private function verifyAttemptsAndOtp(DeliveryRequest $delivery, string $rawOtp): void
    {
        $cacheKey = "delivery_otp_attempts:{$delivery->id}";
        $currentAttempts = (int) Cache::get($cacheKey, 0);

        if ($currentAttempts >= self::MAX_ATTEMPTS) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 429,
                'code' => 'AUTH_OTP_TOO_MANY',
                'detail' => 'سقف ۵ تلاش برای ثبت کد تحویل این مرسوله به پایان رسیده است.',
            ], 429));
        }

        if (! $delivery->verifyOtp($rawOtp)) {
            $newAttempts = $currentAttempts + 1;
            Cache::put($cacheKey, $newAttempts, Carbon::now()->addHours(24));

            if ($newAttempts >= self::ATTEMPTS_BEFORE_NEW_OTP) {
                $this->regenerateOtpDueToFailedAttempts($delivery);

                throw new HttpResponseException(new JsonResponse([
                    'status' => 422,
                    'code' => 'DELIVERY_OTP_INVALID',
                    'detail' => 'کد وارد شده اشتباه است. به دلیل ۳ تلاش ناموفق، کد جدید برای گیرنده پیامک شد.',
                ], 422));
            }

            throw new HttpResponseException(new JsonResponse([
                'status' => 422,
                'code' => 'DELIVERY_OTP_INVALID',
                'detail' => 'کد تحویل نامعتبر است.',
            ], 422));
        }

        Cache::forget($cacheKey);
    }

    private function regenerateOtpDueToFailedAttempts(DeliveryRequest $delivery): void
    {
        $newOtp = DeliveryOtp::generate();
        $delivery->setOtp($newOtp->code, $newOtp->expiresAt);
        $delivery->save();

        if ($delivery->case !== null) {
            SendCaseNotificationJob::dispatch(
                citizenId: $delivery->case->citizen_id,
                type: 'delivery_otp_regenerated',
                title: 'صدور مجدد کد تحویل مرسوله',
                body: "کد جدید تحویل مرسوله {$delivery->tracking_barcode}: {$newOtp->code} — فقط به مأمور تحویل اعلام کنید.",
                smsTemplate: 'delivery-otp',
                smsParams: [
                    'tracking' => $delivery->tracking_barcode,
                    'code' => $newOtp->code,
                ],
                payload: ['case_id' => $delivery->case_id, 'delivery_id' => $delivery->id]
            );
        }
    }

    private function markAsDelivered(DeliveryRequest $delivery): void
    {
        $delivery->delivery_status = DeliveryStatus::DELIVERED;
        $delivery->delivered_at = Carbon::now();
        $delivery->otp_hash = null;
        $delivery->otp_expires_at = null;
        $delivery->save();

        $delivery->events()->create([
            'event' => DeliveryStatus::DELIVERED->value,
            'location' => 'مقصد / درب منزل',
            'note' => 'مرسوله با تأیید موفق رمز یک‌بارمصرف تحویل داده شد.',
            'occurred_at' => Carbon::now(),
        ]);
    }

    private function completeAssociatedCase(DeliveryRequest $delivery, ?Operator $operator): void
    {
        $case = $delivery->case;
        if ($case !== null && $case->status === CaseStatus::DELIVERING) {
            $context = new TransitionContext(
                title: 'تحویل مرسوله و تکمیل پرونده',
                description: 'مرسوله با تأیید رمز یک‌بارمصرف تحویل متقاضی شد و پرونده تکمیل گردید.',
                stepStatus: TimelineStepStatus::DONE,
                actorType: $operator ? TimelineActorType::OPERATOR : TimelineActorType::SYSTEM,
                actorId: $operator?->id,
                reasonCode: 'DELIVERY_CONFIRMED'
            );

            $this->stateMachine->transition($case, CaseStatus::COMPLETED, $context);

            if ($case->fee_paid_rials > 0) {
                SettleCaseFeeJob::dispatch($case->id);
            }
        }
    }

    private function dispatchSuccessNotifications(DeliveryRequest $delivery): void
    {
        if ($delivery->case !== null) {
            SendCaseNotificationJob::dispatch(
                citizenId: $delivery->case->citizen_id,
                type: 'case_completed',
                title: 'پرونده شما تکمیل شد',
                body: "مرسوله پرونده {$delivery->case->tracking_code} با موفقیت به شما تحویل داده شد.",
                smsTemplate: 'case-ready',
                smsParams: ['tracking' => $delivery->case->tracking_code],
                payload: ['case_id' => $delivery->case_id, 'delivery_id' => $delivery->id]
            );
        }
    }

    private function recordAudit(DeliveryRequest $delivery, ?Operator $operator): void
    {
        AuditLogger::record(
            action: 'delivery.confirmed',
            subject: $delivery,
            changes: [
                'delivery_status' => DeliveryStatus::DELIVERED->value,
                'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            ],
            context: ['office_id' => $delivery->office_id],
            actorType: $operator ? 'operator' : 'courier',
            actorId: $operator?->id
        );
    }
}
