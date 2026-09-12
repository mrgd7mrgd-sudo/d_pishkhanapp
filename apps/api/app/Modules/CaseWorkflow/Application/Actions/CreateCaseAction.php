<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Application\Actions;

use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Events\CaseCreated;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Exceptions\InsufficientBalanceException;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerAccount;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final class CreateCaseAction
{
    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * @param  array{
     *     service_id: string,
     *     dispatch_mode: string,
     *     office_id?: string|null,
     *     payment_method: string,
     *     delivery_preference: string,
     *     delivery_address_id?: string|null,
     *     on_behalf_of_delegation_id?: string|null,
     *     documents?: list<array{document_type_code: string, upload_id: string}>,
     *     commitment_signed: bool,
     *     citizen_location?: array{lat: float|int, lng: float|int}|null
     * }  $data
     */
    public function execute(Citizen $citizen, array $data): CaseRequest
    {
        return DB::transaction(function () use ($citizen, $data): CaseRequest {
            $service = $this->resolveService($data['service_id']);
            $requiredFeeRials = $service->fee_rials;

            $walletAccount = $this->ledgerService->getOrCreateAccount(
                ownerType: LedgerOwnerType::CITIZEN,
                ownerId: $citizen->id,
                kind: LedgerAccountKind::WALLET
            );

            LedgerAccount::query()
                ->where('id', $walletAccount->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentBalance = $this->ledgerService->getBalanceRials($walletAccount);

            if ($currentBalance < $requiredFeeRials) {
                throw new InsufficientBalanceException(
                    requiredRials: $requiredFeeRials,
                    availableRials: $currentBalance
                );
            }

            $trackingCode = $this->generateTrackingCode();
            $caseId = (string) Str::uuid();

            $this->recordLedgerDeduction(
                walletAccount: $walletAccount,
                requiredFeeRials: $requiredFeeRials,
                trackingCode: $trackingCode,
                serviceTitle: $service->title,
                caseId: $caseId
            );

            $case = $this->persistCaseRecord(
                caseId: $caseId,
                trackingCode: $trackingCode,
                citizen: $citizen,
                service: $service,
                data: $data
            );

            $this->createInitialTimelineStep($case, $citizen);
            $this->attachUploadedDocuments($case, $data['documents'] ?? []);

            DB::afterCommit(static function () use ($case): void {
                Event::dispatch(new CaseCreated($case));
            });

            return $case->load(['service', 'timelineSteps', 'documents']);
        });
    }

    private function resolveService(string $serviceId): Service
    {
        /** @var Service|null $service */
        $service = Service::query()
            ->where('id', $serviceId)
            ->orWhere('slug', $serviceId)
            ->first();

        if ($service === null) {
            throw (new ModelNotFoundException)->setModel(Service::class, [$serviceId]);
        }

        return $service;
    }

    private function recordLedgerDeduction(
        LedgerAccount $walletAccount,
        int $requiredFeeRials,
        string $trackingCode,
        string $serviceTitle,
        string $caseId
    ): void {
        $escrowAccount = $this->ledgerService->getOrCreateAccount(
            ownerType: LedgerOwnerType::PLATFORM,
            ownerId: null,
            kind: LedgerAccountKind::ESCROW
        );

        $this->ledgerService->recordTransaction(
            reference: $trackingCode,
            type: LedgerTransactionType::SERVICE_FEE,
            entries: [
                new LedgerEntryData(
                    account: $walletAccount,
                    direction: LedgerDirection::DEBIT,
                    amountRials: $requiredFeeRials
                ),
                new LedgerEntryData(
                    account: $escrowAccount,
                    direction: LedgerDirection::CREDIT,
                    amountRials: $requiredFeeRials
                ),
            ],
            description: "کارمزد ثبت پرونده {$trackingCode} خدمت {$serviceTitle}",
            caseId: $caseId
        );
    }

    /**
     * @param  array{
     *     dispatch_mode: string,
     *     office_id?: string|null,
     *     delivery_preference: string,
     *     on_behalf_of_delegation_id?: string|null,
     *     citizen_location?: array{lat: float|int, lng: float|int}|null
     * }  $data
     */
    private function persistCaseRecord(
        string $caseId,
        string $trackingCode,
        Citizen $citizen,
        Service $service,
        array $data
    ): CaseRequest {
        $officeShareRials = (int) round($service->fee_rials * ($service->office_share_percent / 100));
        $platformShareRials = $service->fee_rials - $officeShareRials;
        $provinceCode = strtoupper($citizen->province_code ?? 'THR');

        $case = new CaseRequest([
            'id' => $caseId,
            'tracking_code' => $trackingCode,
            'citizen_id' => $citizen->id,
            'service_id' => $service->id,
            'office_id' => $data['office_id'] ?? null,
            'province_code' => $provinceCode,
            'status' => CaseStatus::SEARCHING_OFFICE,
            'turn_owner' => TurnOwner::SYSTEM,
            'current_step' => 1,
            'total_steps' => 6,
            'fee_paid_rials' => $service->fee_rials,
            'office_share_rials' => $officeShareRials,
            'platform_share_rials' => $platformShareRials,
            'citizen_location' => $this->formatCitizenLocation($data['citizen_location'] ?? null),
            'delegation_id' => $data['on_behalf_of_delegation_id'] ?? null,
            'delivery_preference' => DeliveryPreference::from($data['delivery_preference']),
            'sla_deadline_at' => CarbonImmutable::now()->addSeconds(90),
        ]);
        $case->save();

        return $case;
    }

    private function createInitialTimelineStep(CaseRequest $case, Citizen $citizen): void
    {
        CaseTimelineStep::query()->create([
            'id' => (string) Str::uuid(),
            'case_id' => $case->id,
            'sequence' => 1,
            'title' => 'ثبت درخواست',
            'description' => 'درخواست شما با موفقیت ثبت و هزینه از کیف پول کسر شد.',
            'status' => TimelineStepStatus::DONE,
            'turn_owner' => TurnOwner::CITIZEN,
            'turn_owner_label' => 'شهروند',
            'actor_type' => TimelineActorType::CITIZEN,
            'actor_id' => $citizen->id,
            'occurred_at' => CarbonImmutable::now(),
            'duration_actual_minutes' => 0,
            'duration_typical_minutes' => null,
        ]);
    }

    /**
     * @param  list<array{document_type_code: string, upload_id: string}>  $documents
     */
    private function attachUploadedDocuments(CaseRequest $case, array $documents): void
    {
        foreach ($documents as $doc) {
            CaseDocument::query()->create([
                'id' => (string) Str::uuid(),
                'case_id' => $case->id,
                'document_type_code' => $doc['document_type_code'],
                'version' => 1,
                'status' => CaseDocumentStatus::PENDING,
                'storage_key' => 'uploads/'.trim($doc['upload_id']),
                'encrypted_data_key' => 'pending-envelope-key',
                'content_sha256' => hash('sha256', $doc['upload_id']),
                'size_bytes' => 0,
                'mime_type' => 'application/octet-stream',
                'quality_warnings' => [],
                'uploaded_at' => CarbonImmutable::now(),
            ]);
        }
    }

    /**
     * @param  array{lat: float|int, lng: float|int}|null  $location
     */
    private function formatCitizenLocation(?array $location): mixed
    {
        if ($location === null) {
            return null;
        }

        $lat = (float) $location['lat'];
        $lng = (float) $location['lng'];

        if (DB::connection()->getDriverName() === 'pgsql') {
            return DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography");
        }

        return json_encode(['lat' => $lat, 'lng' => $lng], JSON_THROW_ON_ERROR);
    }

    private function generateTrackingCode(): string
    {
        do {
            $code = sprintf('CR-1405-%05d', random_int(10000, 99999));
        } while (CaseRequest::query()->where('tracking_code', $code)->exists());

        return $code;
    }
}
