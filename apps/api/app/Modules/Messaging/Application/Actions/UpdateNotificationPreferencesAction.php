<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Application\Actions;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Messaging\Domain\Models\NotificationPreference;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class UpdateNotificationPreferencesAction
{
    public function __construct(
        private readonly GetNotificationPreferencesAction $getPreferencesAction
    ) {}

    /**
     * @param  list<array{notification_type: string, sms_enabled: bool, push_enabled: bool}>  $preferences
     * @return list<array<string, mixed>>
     */
    public function execute(Authenticatable $user, array $preferences): array
    {
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'detail' => 'Only citizens can update notification preferences.',
            ], 403));
        }

        foreach ($preferences as $pref) {
            NotificationPreference::query()->updateOrCreate(
                [
                    'citizen_id' => $user->id,
                    'notification_type' => $pref['notification_type'],
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'sms_enabled' => $pref['sms_enabled'],
                    'push_enabled' => $pref['push_enabled'],
                    'updated_at' => Carbon::now(),
                ]
            );
        }

        return $this->getPreferencesAction->execute($user);
    }
}
