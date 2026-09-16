<?php

declare(strict_types=1);

namespace App\Modules\Identity\Listeners;

use App\Modules\CaseWorkflow\Domain\Events\CaseCreated;
use App\Modules\Identity\Domain\Events\DelegationUsed;
use App\Modules\Identity\Domain\Models\Delegation;
use App\Modules\Messaging\Domain\Enums\NotificationType;
use App\Modules\Messaging\Domain\Models\Notification;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * NotifyPrincipalOnDelegationUseListener (Architecture §7.1 T4, §7.3, TASK-122).
 *
 * Invariant: Every use of legal delegation sends an immediate notification to the principal,
 * and records an immutable security audit entry.
 */
final class NotifyPrincipalOnDelegationUseListener
{
    public function handle(object $event): void
    {
        if ($event instanceof DelegationUsed) {
            $this->notifyOnDelegationUsed($event);
        } elseif ($event instanceof CaseCreated) {
            $this->notifyOnCaseCreated($event);
        }
    }

    private function notifyOnDelegationUsed(DelegationUsed $event): void
    {
        $delegation = $event->delegation;
        $case = $event->case;

        Notification::create([
            'id' => (string) Str::uuid(),
            'citizen_id' => $delegation->principal_citizen_id,
            'type' => NotificationType::SYSTEM->value,
            'title' => 'اعلان استفاده از حق نمایندگی',
            'body' => $case !== null
                ? "نماینده شما با شناسه وکالت {$delegation->document_number} پرونده‌ای با کد پیگیری {$case->tracking_code} به وکالت از شما ثبت نمود."
                : "نماینده شما از اختیارات وکالت‌نامه شماره {$delegation->document_number} استفاده نمود.",
            'payload' => [
                'delegation_id' => $delegation->id,
                'case_id' => $case?->id,
                'tracking_code' => $case?->tracking_code,
                'action' => $event->action,
            ],
        ]);

        AuditLogger::record(
            action: 'delegation.used',
            subject: $delegation,
            changes: [
                'action' => $event->action,
                'case_id' => $case?->id,
                'tracking_code' => $case?->tracking_code,
            ],
            context: [
                'ip' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent() ?? 'system',
            ],
            actorType: 'citizen',
            actorId: (string) $event->delegate->getAuthIdentifier(),
        );
    }

    private function notifyOnCaseCreated(CaseCreated $event): void
    {
        $case = $event->case;
        if ($case->delegation_id === null) {
            return;
        }

        // Avoid duplicate notification if already processed by DelegationUsed
        $alreadyNotified = Notification::query()
            ->where('payload->case_id', $case->id)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        /** @var Delegation|null $delegation */
        $delegation = Delegation::query()->find($case->delegation_id);
        if ($delegation === null) {
            return;
        }

        Notification::create([
            'id' => (string) Str::uuid(),
            'citizen_id' => $delegation->principal_citizen_id,
            'type' => NotificationType::SYSTEM->value,
            'title' => 'اعلان استفاده از حق نمایندگی',
            'body' => "پرونده جدیدی با کد پیگیری {$case->tracking_code} توسط نماینده شما به وکالت ثبت گردید.",
            'payload' => [
                'delegation_id' => $delegation->id,
                'case_id' => $case->id,
                'tracking_code' => $case->tracking_code,
                'action' => 'case_created',
            ],
        ]);
    }
}
