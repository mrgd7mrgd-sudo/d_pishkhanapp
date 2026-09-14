<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Infrastructure\Policies;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\Messaging\Domain\Models\CaseMessage;
use Illuminate\Contracts\Auth\Authenticatable;

final class CaseMessagePolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $user instanceof Operator;
    }

    public function view(?Authenticatable $user, CaseMessage $message): bool
    {
        /** @var CaseRequest|null $case */
        $case = $message->case;
        if (! $case) {
            return false;
        }

        return $this->canAccessCase($user, $case);
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $user instanceof Operator;
    }

    public function update(?Authenticatable $user, CaseMessage $message): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user !== null && $user->getAuthIdentifier() === $message->sender_id;
    }

    public function delete(?Authenticatable $user, CaseMessage $message): bool
    {
        return $this->isSystemAdmin($user);
    }

    /**
     * Check if user is a party of the case (citizen owner or assigned office operator).
     */
    public function canAccessCase(?Authenticatable $user, CaseRequest $case): bool
    {
        if ($user instanceof Citizen) {
            return $user->id === $case->citizen_id;
        }

        if ($user instanceof Operator) {
            if ($this->isSystemAdmin($user) || $user->hasRole('auditor')) {
                return true;
            }

            return $case->office_id !== null && $user->office_id === $case->office_id;
        }

        return false;
    }
}
