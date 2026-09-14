<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

Broadcast::routes(['middleware' => ['auth:sanctum,operator']]);
Broadcast::routes(['prefix' => 'api/v1', 'middleware' => ['auth:sanctum,operator']]);

/**
 * Channel 1: private-citizen.{citizenId} (Architecture §5.7)
 * Permitted: The citizen themselves or System Admin.
 */
Broadcast::channel('citizen.{citizenId}', function (?Authenticatable $user, string $citizenId): bool {
    if ($user instanceof Operator && $user->hasRole('system_admin')) {
        return true;
    }

    return $user instanceof Citizen && $user->id === $citizenId;
});

/**
 * Channel 2: private-case.{caseId} (Architecture §5.7)
 * Permitted: Citizen owner, operator belonging to assigned office, or System Admin (via CaseRequestPolicy::view).
 */
Broadcast::channel('case.{caseId}', function (?Authenticatable $user, string $caseId): bool {
    $case = CaseRequest::query()->find($caseId);
    if ($case === null || $user === null) {
        return false;
    }

    return Gate::forUser($user)->allows('view', $case);
});

/**
 * Channel 3: private-office.{officeId} (Architecture §5.7, TASK-070-T)
 * Permitted: Operators/managers belonging strictly to that office, or System Admin.
 * Cross-office access (Operator from Office A accessing private-office.{B}) is strictly denied.
 */
Broadcast::channel('office.{officeId}', function (?Authenticatable $user, string $officeId): bool {
    if (! $user instanceof Operator) {
        return false;
    }

    if ($user->hasRole('system_admin')) {
        return true;
    }

    return $user->office_id !== null && $user->office_id === $officeId;
});

/**
 * Channel 4: presence-office-desk.{officeId} (Architecture §5.7)
 * Permitted: Operators belonging strictly to that office, or System Admin.
 * Returns member presence state (ID, name, counter number, role).
 */
Broadcast::channel('office-desk.{officeId}', function (?Authenticatable $user, string $officeId): array|bool {
    if (! $user instanceof Operator) {
        return false;
    }

    if ($user->office_id === $officeId || $user->hasRole('system_admin')) {
        return [
            'id' => $user->id,
            'name' => $user->full_name,
            'counter_number' => $user->counter_number,
            'role' => $user->role->value,
        ];
    }

    return false;
});

/**
 * Channel 5: private-consultation.{sessionId} (Architecture §5.7)
 * Permitted: Consultation participant (citizen / advisor) or System Admin.
 */
Broadcast::channel('consultation.{sessionId}', function (?Authenticatable $user, string $sessionId): bool {
    if ($user === null) {
        return false;
    }

    if ($user instanceof Operator && $user->hasRole('system_admin')) {
        return true;
    }

    if (Schema::hasTable('consultation_sessions')) {
        $session = DB::table('consultation_sessions')->where('id', $sessionId)->first();
        if ($session !== null) {
            return $user->id === $session->citizen_id || $user->id === $session->advisor_id;
        }
    }

    return false;
});

/**
 * Channel 6: private-admin.system (Architecture §5.7)
 * Permitted: System Admin only.
 */
Broadcast::channel('admin.system', function (?Authenticatable $user): bool {
    return $user instanceof Operator && $user->hasRole('system_admin');
});
