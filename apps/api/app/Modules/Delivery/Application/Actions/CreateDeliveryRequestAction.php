<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Application\Actions;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Exceptions\InvalidCaseTransitionException;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Delivery\Domain\DeliveryOtp;
use App\Modules\Delivery\Domain\Enums\CourierType;
use App\Modules\Delivery\Domain\Enums\DeliveryDocType;
use App\Modules\Delivery\Domain\Enums\DeliveryPaymentMethod;
use App\Modules\Delivery\Domain\Enums\DeliveryStatus;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Action to create a Delivery Request and issue OTP (Architecture §3.5, §6.1, §7.4, TASK-095).
 */
final class CreateDeliveryRequestAction
{
    public function __construct(
        private readonly CaseStateMachine $stateMachine
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Operator $operator, array $data): DeliveryRequest
    {
        $caseId = (string) $data['case_id'];
        $case = $this->findAuthorizedCase($operator, $caseId);

        $this->ensureCaseReadyForDelivery($case);

        return DB::transaction(function () use ($operator, $case, $data): DeliveryRequest {
            $this->transitionCaseToDelivering($operator, $case);

            $otp = DeliveryOtp::generate();
            $barcode = $this->resolveTrackingBarcode($data);

            $deliveryRequest = $this->createDeliveryRecord($operator, $case, $data, $otp, $barcode);

            $this->createInitialEvent($operator, $deliveryRequest);
            $this->dispatchNotifications($case, $deliveryRequest, $otp->code);
            $this->recordAudit($operator, $deliveryRequest);

            return $deliveryRequest;
        });
    }

    private function findAuthorizedCase(Operator $operator, string $caseId): CaseRequest
    {
        /** @var CaseRequest|null $case */
        $case = CaseRequest::query()->where('id', $caseId)->first();

        // Horizontal Isolation §7.3: Always 404, never 403
        if ($case === null || $case->office_id !== $operator->office_id) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'Case not found.',
            ], 404));
        }

        return $case;
    }

    private function ensureCaseReadyForDelivery(CaseRequest $case): void
    {
        if ($case->status !== CaseStatus::READY_FOR_ISSUE) {
            throw new InvalidCaseTransitionException(
                from: $case->status,
                to: CaseStatus::DELIVERING,
                message: "امکان صدور تحویل برای پرونده در وضعیت [{$case->status->value}] وجود ندارد. وضعیت باید ready_for_issue باشد."
            );
        }
    }

    private function transitionCaseToDelivering(Operator $operator, CaseRequest $case): void
    {
        $context = new TransitionContext(
            title: 'ایجاد درخواست تحویل و صدور کد تحویل',
            description: 'درخواست تحویل مرسوله ثبت و کد تحویل یک‌بارمصرف برای متقاضی صادر گردید.',
            stepStatus: TimelineStepStatus::DONE,
            actorType: TimelineActorType::OPERATOR,
            actorId: $operator->id,
            reasonCode: 'DELIVERY_REQUESTED'
        );

        $this->stateMachine->transition($case, CaseStatus::DELIVERING, $context);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveTrackingBarcode(array $data): string
    {
        if (! empty($data['tracking_barcode'])) {
            return (string) $data['tracking_barcode'];
        }

        return 'DEL-'.Carbon::now()->format('Ymd').'-'.strtoupper(Str::random(8));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDeliveryRecord(
        Operator $operator,
        CaseRequest $case,
        array $data,
        DeliveryOtp $otp,
        string $barcode
    ): DeliveryRequest {
        $docType = $data['doc_type'] instanceof DeliveryDocType
            ? $data['doc_type']
            : DeliveryDocType::from((string) $data['doc_type']);

        $courierType = $data['courier_type'] instanceof CourierType
            ? $data['courier_type']
            : CourierType::from((string) $data['courier_type']);

        $paymentMethod = isset($data['payment_method'])
            ? ($data['payment_method'] instanceof DeliveryPaymentMethod
                ? $data['payment_method']
                : DeliveryPaymentMethod::from((string) $data['payment_method']))
            : DeliveryPaymentMethod::PREPAID;

        $deliveryRequest = new DeliveryRequest([
            'case_id' => $case->id,
            'office_id' => $operator->office_id,
            'doc_type' => $docType,
            'doc_type_name' => $data['doc_type_name'] ?? $docType->label(),
            'doc_serial_number' => $data['doc_serial_number'] ?? null,
            'destination_address' => (string) $data['destination_address'],
            'destination_postal_code' => (string) $data['destination_postal_code'],
            'destination_zone' => $data['destination_zone'] ?? null,
            'courier_type' => $courierType,
            'delivery_status' => DeliveryStatus::READY_FOR_DISPATCH,
            'shipping_fee_rials' => (int) ($data['shipping_fee_rials'] ?? 0),
            'payment_method' => $paymentMethod,
            'require_old_doc_return' => (bool) ($data['require_old_doc_return'] ?? false),
            'is_sealed_pack' => (bool) ($data['is_sealed_pack'] ?? true),
            'security_note' => $data['security_note'] ?? null,
            'tracking_barcode' => $barcode,
        ]);

        $deliveryRequest->setOtp($otp->code, $otp->expiresAt);
        $deliveryRequest->save();

        return $deliveryRequest;
    }

    private function createInitialEvent(Operator $operator, DeliveryRequest $deliveryRequest): void
    {
        $deliveryRequest->events()->create([
            'event' => DeliveryStatus::READY_FOR_DISPATCH->value,
            'location' => $operator->office->name ?? 'دفتر مبدأ',
            'note' => 'مرسوله آماده‌سازی و جهت تحویل به پیک ثبت شد.',
            'occurred_at' => Carbon::now(),
        ]);
    }

    private function dispatchNotifications(CaseRequest $case, DeliveryRequest $deliveryRequest, string $rawOtp): void
    {
        SendCaseNotificationJob::dispatch(
            citizenId: $case->citizen_id,
            type: 'delivery_created',
            title: 'صدور کد تحویل مرسوله',
            body: "کد تحویل مرسوله {$deliveryRequest->tracking_barcode}: {$rawOtp} — فقط به مأمور تحویل اعلام کنید.",
            smsTemplate: 'delivery-otp',
            smsParams: [
                'tracking' => $deliveryRequest->tracking_barcode,
                'code' => $rawOtp,
            ],
            payload: [
                'case_id' => $case->id,
                'delivery_id' => $deliveryRequest->id,
                'tracking_barcode' => $deliveryRequest->tracking_barcode,
            ]
        );
    }

    private function recordAudit(Operator $operator, DeliveryRequest $deliveryRequest): void
    {
        AuditLogger::record(
            action: 'delivery.created',
            subject: $deliveryRequest,
            changes: [
                'delivery_status' => $deliveryRequest->delivery_status->value,
                'tracking_barcode' => $deliveryRequest->tracking_barcode,
                'case_id' => $deliveryRequest->case_id,
            ],
            context: ['office_id' => $operator->office_id],
            actorType: 'operator',
            actorId: $operator->id
        );
    }
}
