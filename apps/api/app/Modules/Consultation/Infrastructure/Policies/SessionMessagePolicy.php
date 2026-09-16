<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Infrastructure\Policies;

use App\Modules\Consultation\Domain\Models\SessionMessage;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class SessionMessagePolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function view(?Authenticatable $user, SessionMessage $message): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        $session = $message->session;
        if ($session === null) {
            return false;
        }

        return $user instanceof Citizen && ($session->citizen_id === $user->id || $session->advisor?->citizen_id === $user->id);
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, SessionMessage $message): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, SessionMessage $message): bool
    {
        return $this->isSystemAdmin($user);
    }
}
