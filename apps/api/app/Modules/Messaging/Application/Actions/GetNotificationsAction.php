<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Application\Actions;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Messaging\Domain\Models\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

final class GetNotificationsAction
{
    /**
     * @return array{unread_count: int, items: list<array<string, mixed>>}
     */
    public function execute(Authenticatable $user, bool $unreadOnly = false, int $limit = 20): array
    {
        if (! $user instanceof Citizen) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'detail' => 'Only citizens can access notifications directly.',
            ], 403));
        }

        $unreadCount = Notification::query()
            ->where('citizen_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $query = Notification::query()
            ->where('citizen_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        /** @var Collection<int, Notification> $notifications */
        $notifications = $query->get();

        $items = $notifications->map(fn (Notification $n): array => [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'payload' => $n->payload,
            'read_at' => $n->read_at?->toISOString(),
            'created_at' => $n->created_at->toISOString(),
        ])->values()->all();

        return [
            'unread_count' => $unreadCount,
            'items' => $items,
        ];
    }
}
