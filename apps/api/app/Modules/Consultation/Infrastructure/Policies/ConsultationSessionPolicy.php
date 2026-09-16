<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Infrastructure\Policies;

use App\Modules\Consultation\Domain\Models\ConsultationSession;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class ConsultationSessionPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function view(?Authenticatable $user, ConsultationSession $session): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Citizen && ($session->citizen_id === $user->id || $session->advisor?->citizen_id === $user->id);
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, ConsultationSession $session): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Citizen && ($session->citizen_id === $user->id || $session->advisor?->citizen_id === $user->id);
    }

    public function delete(?Authenticatable $user, ConsultationSession $session): bool
    {
        return $this->isSystemAdmin($user);
    }
}
