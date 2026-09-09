<?php

declare(strict_types=1);

namespace App\Modules\Identity\Listeners;

use App\Modules\Identity\Domain\Events\AuthDeviceNewEvent;
use App\Modules\Identity\Domain\Events\AuthLoginFailedEvent;
use App\Modules\Identity\Domain\Events\AuthLoginSuccessEvent;
use App\Modules\Identity\Domain\Events\AuthLogoutEvent;
use App\Modules\Identity\Domain\Events\AuthOtpRequestedEvent;
use App\Modules\Identity\Domain\Events\AuthTokenRevokedEvent;
use App\Shared\Audit\AuditLogger;
use App\Shared\Security\PiiRedactor;
use Illuminate\Events\Dispatcher;

final class AuditAuthEventsListener
{
    public function onLoginSuccess(AuthLoginSuccessEvent $event): void
    {
        $context = PiiRedactor::redactArray($event->context);

        AuditLogger::record(
            action: $event->eventName(),
            subject: $event->user,
            changes: ['status' => 'success'],
            context: $context,
            actorType: get_class($event->user),
            actorId: (string) $event->user->getAuthIdentifier(),
            occurredAt: $event->occurredAt
        );
    }

    public function onLoginFailed(AuthLoginFailedEvent $event): void
    {
        $context = PiiRedactor::redactArray($event->context);

        AuditLogger::record(
            action: $event->eventName(),
            subject: 'Auth:Failed',
            changes: [
                'identifier_masked' => PiiRedactor::maskNationalId($event->identifierMasked),
                'reason' => $event->reason,
            ],
            context: $context,
            occurredAt: $event->occurredAt
        );
    }

    public function onLogout(AuthLogoutEvent $event): void
    {
        $context = PiiRedactor::redactArray($event->context);

        AuditLogger::record(
            action: $event->eventName(),
            subject: $event->user,
            changes: ['status' => 'logged_out'],
            context: $context,
            actorType: get_class($event->user),
            actorId: (string) $event->user->getAuthIdentifier(),
            occurredAt: $event->occurredAt
        );
    }

    public function onTokenRevoked(AuthTokenRevokedEvent $event): void
    {
        $context = PiiRedactor::redactArray($event->context);

        AuditLogger::record(
            action: $event->eventName(),
            subject: "Token:{$event->tokenId}",
            changes: ['status' => 'revoked', 'token_id' => $event->tokenId],
            context: $context,
            actorType: get_class($event->user),
            actorId: (string) $event->user->getAuthIdentifier(),
            occurredAt: $event->occurredAt
        );
    }

    public function onOtpRequested(AuthOtpRequestedEvent $event): void
    {
        $context = PiiRedactor::redactArray($event->context);

        AuditLogger::record(
            action: $event->eventName(),
            subject: $event->challengeId !== null ? "OtpChallenge:{$event->challengeId}" : 'OtpChallenge',
            changes: [
                'mobile_masked' => PiiRedactor::maskMobile($event->mobileMasked),
                'purpose' => $event->purpose,
            ],
            context: $context,
            occurredAt: $event->occurredAt
        );
    }

    public function onDeviceNew(AuthDeviceNewEvent $event): void
    {
        $context = PiiRedactor::redactArray($event->context);

        AuditLogger::record(
            action: $event->eventName(),
            subject: $event->user,
            changes: ['device_name' => $event->deviceName],
            context: $context,
            actorType: get_class($event->user),
            actorId: (string) $event->user->getAuthIdentifier(),
            occurredAt: $event->occurredAt
        );
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            AuthLoginSuccessEvent::class => 'onLoginSuccess',
            AuthLoginFailedEvent::class => 'onLoginFailed',
            AuthLogoutEvent::class => 'onLogout',
            AuthTokenRevokedEvent::class => 'onTokenRevoked',
            AuthOtpRequestedEvent::class => 'onOtpRequested',
            AuthDeviceNewEvent::class => 'onDeviceNew',
        ];
    }
}
