<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Infrastructure\Policies;

use App\Modules\AiAssistance\Domain\Models\AiMessage;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class AiMessagePolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function view(?Authenticatable $user, AiMessage $message): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        $conversation = $message->conversation;

        return $user instanceof Citizen && $conversation !== null && $conversation->citizen_id === $user->id;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, AiMessage $message): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, AiMessage $message): bool
    {
        return $this->isSystemAdmin($user);
    }
}
