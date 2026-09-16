<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Infrastructure\Policies;

use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class AiConversationPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function view(?Authenticatable $user, AiConversation $conversation): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Citizen && $conversation->citizen_id === $user->id;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, AiConversation $conversation): bool
    {
        return $user instanceof Citizen && $conversation->citizen_id === $user->id;
    }

    public function delete(?Authenticatable $user, AiConversation $conversation): bool
    {
        return $this->isSystemAdmin($user) || ($user instanceof Citizen && $conversation->citizen_id === $user->id);
    }
}
