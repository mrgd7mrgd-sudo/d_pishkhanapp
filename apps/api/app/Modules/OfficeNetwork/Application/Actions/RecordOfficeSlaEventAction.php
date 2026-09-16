<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Application\Actions;

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Jobs\SendSmsJob;
use App\Modules\OfficeNetwork\Domain\Events\OfficeSlaBreached;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSlaEvent;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use App\Shared\Metrics\DomainMetrics;
use App\Shared\Scopes\OfficeScope;
use Carbon\CarbonImmutable;

/**
 * RecordOfficeSlaEventAction (Architecture §5.9, §9.6, TASK-102).
 *
 * Records SLA events/breaches, updates domain metrics, broadcasts events,
 * and notifies office managers of quality violations.
 */
final class RecordOfficeSlaEventAction
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function execute(
        string $officeId,
        string $eventType,
        ?string $caseId = null,
        int $penaltyPoints = 0,
        ?int $durationMinutes = null,
        bool $isBreach = true,
        ?CarbonImmutable $occurredAt = null,
        ?array $metadata = null,
    ): OfficeSlaEvent {
        $timestamp = $occurredAt ?? CarbonImmutable::now();

        $event = OfficeSlaEvent::create([
            'office_id' => $officeId,
            'case_id' => $caseId,
            'event_type' => $eventType,
            'duration_minutes' => $durationMinutes,
            'penalty_points' => $penaltyPoints,
            'is_breach' => $isBreach,
            'occurred_at' => $timestamp,
            'metadata' => $metadata,
        ]);

        if ($isBreach) {
            $this->handleBreachSideEffects($event, $officeId, $eventType, $caseId, $penaltyPoints, $timestamp);
        }

        return $event;
    }

    private function handleBreachSideEffects(
        OfficeSlaEvent $event,
        string $officeId,
        string $eventType,
        ?string $caseId,
        int $penaltyPoints,
        CarbonImmutable $timestamp
    ): void {
        DomainMetrics::incrementSlaBreaches($officeId);

        OfficeSlaBreached::dispatch($officeId, $eventType, $caseId, $penaltyPoints, null, $timestamp);

        $this->notifyOfficeManagers($officeId, $eventType, $caseId, $penaltyPoints);

        AuditLogger::record(
            action: AuditableAction::SLA_BREACH_RECORDED,
            subject: $event,
            changes: [
                'office_id' => $officeId,
                'event_type' => $eventType,
                'case_id' => $caseId,
                'penalty_points' => $penaltyPoints,
            ],
            context: ['office_id' => $officeId],
            actorType: 'system',
            actorId: null,
        );
    }

    private function notifyOfficeManagers(
        string $officeId,
        string $eventType,
        ?string $caseId,
        int $penaltyPoints
    ): void {
        $managers = Operator::query()
            ->withoutGlobalScope(OfficeScope::class)
            ->where('office_id', $officeId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Operator $op): bool => $op->role === OperatorRole::MANAGER || $op->hasRole('office_manager'));

        foreach ($managers as $manager) {
            if (! empty($manager->mobile)) {
                SendSmsJob::dispatch(
                    $manager->mobile,
                    'sla_breach_alert',
                    [
                        'office_id' => $officeId,
                        'event_type' => $eventType,
                        'case_id' => $caseId ?? '',
                        'penalty' => (string) $penaltyPoints,
                    ]
                );
            }
        }
    }
}
