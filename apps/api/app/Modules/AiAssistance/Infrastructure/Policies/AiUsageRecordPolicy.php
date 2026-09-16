<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Infrastructure\Policies;

use App\Modules\AiAssistance\Domain\Models\AiUsageRecord;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class AiUsageRecordPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function view(?Authenticatable $user, AiUsageRecord $record): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, AiUsageRecord $record): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, AiUsageRecord $record): bool
    {
        return $this->isSystemAdmin($user);
    }
}
