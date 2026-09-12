<?php

declare(strict_types=1);

namespace App\Modules\Documents\Infrastructure\Policies;

use App\Modules\Documents\Domain\Models\VaultDocumentAttribute;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class VaultDocumentAttributePolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function view(?Authenticatable $user, VaultDocumentAttribute $attr): bool
    {
        return $user instanceof Citizen && $attr->document?->citizen_id === $user->getAuthIdentifier();
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, VaultDocumentAttribute $attr): bool
    {
        return $user instanceof Citizen && $attr->document?->citizen_id === $user->getAuthIdentifier();
    }

    public function delete(?Authenticatable $user, VaultDocumentAttribute $attr): bool
    {
        return $user instanceof Citizen && $attr->document?->citizen_id === $user->getAuthIdentifier();
    }
}
