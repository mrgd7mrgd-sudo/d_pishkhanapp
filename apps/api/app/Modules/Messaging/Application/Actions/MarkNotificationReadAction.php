<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Application\Actions;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Messaging\Domain\Models\Notification;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class MarkNotificationReadAction
{
    public function execute(Authenticatable $user, string $notificationId): Notification
    {
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'detail' => 'Only citizens can mark notifications as read.',
            ], 403));
        }

        /** @var Notification|null $notification */
        $notification = Notification::query()
            ->where('id', $notificationId)
            ->where('citizen_id', $user->id)
            ->first();

        if (! $notification) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'Notification not found.',
            ], 404));
        }

        if ($notification->read_at === null) {
            $notification->read_at = Carbon::now();
            $notification->save();
        }

        return $notification;
    }
}
