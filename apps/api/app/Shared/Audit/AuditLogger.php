<?php

declare(strict_types=1);

namespace App\Shared\Audit;

use App\Shared\Http\Middleware\RequestId;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AuditLogger
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Compute cryptographically secure immutable SHA-256 hash chaining entry_hash:
     * SHA256(prev_hash ‖ action ‖ actor_type ‖ actor_id ‖ subject ‖ changes ‖ occurred_at)
     *
     * @param  array<string, mixed>  $changes
     */
    public static function computeEntryHash(
        string $prevHash,
        string $action,
        ?string $actorType,
        ?string $actorId,
        ?string $subject,
        array $changes,
        string $occurredAtIso
    ): string {
        $data = implode('||', [
            $prevHash,
            $action,
            $actorType ?? '',
            $actorId ?? '',
            $subject ?? '',
            json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $occurredAtIso,
        ]);

        return hash('sha256', $data);
    }

    /**
     * Record an auditable action into immutable audit_logs table.
     *
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $context
     */
    public static function record(
        AuditableAction|string $action,
        Model|Authenticatable|string|null $subject = null,
        array $changes = [],
        array $context = [],
        ?string $actorType = null,
        ?string $actorId = null,
        ?CarbonImmutable $occurredAt = null
    ): string {
        $actionValue = $action instanceof AuditableAction ? $action->value : $action;
        $occurredAt = $occurredAt ?? CarbonImmutable::now();
        $occurredAtIso = $occurredAt->toIso8601String();

        $subjectType = null;
        $subjectId = null;
        if ($subject instanceof Model) {
            $subjectType = $subject->getMorphClass();
            $subjectId = (string) $subject->getKey();
        } elseif ($subject instanceof Authenticatable) {
            $subjectType = get_class($subject);
            $subjectId = (string) $subject->getAuthIdentifier();
        } elseif (is_string($subject) && $subject !== '') {
            $subjectType = 'Resource';
            $subjectId = $subject;
        }

        $subjectString = ($subjectType !== null) ? "{$subjectType}:{$subjectId}" : null;

        // Determine actor from auth if not passed explicitly
        if ($actorType === null && $actorId === null) {
            $user = auth()->user();
            if ($user !== null) {
                $actorType = get_class($user);
                $actorId = (string) $user->getAuthIdentifier();
            }
        }

        // Retrieve latest entry_hash to build blockchain-style hash chaining
        $latestRecord = DB::table('audit_logs')
            ->orderBy('occurred_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->select('entry_hash')
            ->first();

        $prevHash = $latestRecord !== null ? (string) $latestRecord->entry_hash : self::GENESIS_HASH;

        $entryHash = self::computeEntryHash(
            $prevHash,
            $actionValue,
            $actorType,
            $actorId,
            $subjectString,
            $changes,
            $occurredAtIso
        );

        $id = (string) Str::uuid();
        $request = request();
        $requestId = $request->header(RequestId::HEADER_NAME);
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        DB::table('audit_logs')->insert([
            'id' => $id,
            'occurred_at' => $occurredAt,
            'entry_hash' => $entryHash,
            'prev_hash' => $prevHash,
            'action' => $actionValue,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'changes' => json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'request_id' => $requestId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => CarbonImmutable::now(),
        ]);

        return $id;
    }
}
