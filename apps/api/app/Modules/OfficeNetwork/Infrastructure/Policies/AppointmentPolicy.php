<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use Illuminate\Contracts\Auth\Authenticatable;

final class AppointmentPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function view(?Authenticatable $user, Appointment $appointment): bool
    {
        if ($user instanceof Citizen) {
            return $user->id === $appointment->citizen_id;
        }

        if ($user instanceof Operator) {
            return $user->office_id === $appointment->office_id || $this->isSystemAdmin($user);
        }

        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function cancel(?Authenticatable $user, Appointment $appointment): bool
    {
        if (! $appointment->isActive()) {
            return false;
        }

        if ($user instanceof Citizen) {
            return $user->id === $appointment->citizen_id;
        }

        if ($user instanceof Operator) {
            return $user->office_id === $appointment->office_id || $this->isSystemAdmin($user);
        }

        return false;
    }

    public function update(?Authenticatable $user, Appointment $appointment): bool
    {
        if ($user instanceof Operator) {
            return $user->office_id === $appointment->office_id || $this->isSystemAdmin($user);
        }

        return false;
    }

    public function delete(?Authenticatable $user, Appointment $appointment): bool
    {
        return $this->cancel($user, $appointment);
    }
}
