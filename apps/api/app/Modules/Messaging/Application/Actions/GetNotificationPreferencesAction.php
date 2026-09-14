<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Application\Actions;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Messaging\Domain\Enums\NotificationType;
use App\Modules\Messaging\Domain\Models\NotificationPreference;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class GetNotificationPreferencesAction
{
    /**
     * @return list<array<string, mixed>>
     */
    public function execute(Authenticatable $user): array
    {
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'detail' => 'Only citizens can access notification preferences.',
            ], 403));
        }

        /** @var Collection<int, NotificationPreference> $savedPreferences */
        $savedPreferences = NotificationPreference::query()
            ->where('citizen_id', $user->id)
            ->get();

        $savedByType = $savedPreferences->keyBy('notification_type');

        $result = [];
        foreach (NotificationType::cases() as $type) {
            $existing = $savedByType->get($type->value);

            $result[] = [
                'notification_type' => $type->value,
                'label' => $type->label(),
                'sms_enabled' => $existing ? $existing->sms_enabled : true,
                'push_enabled' => $existing ? $existing->push_enabled : true,
            ];
        }

        return $result;
    }
}
