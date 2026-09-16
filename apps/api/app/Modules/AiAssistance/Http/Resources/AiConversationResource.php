<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Http\Resources;

use App\Modules\AiAssistance\Domain\Models\AiConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiConversation
 */
final class AiConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel->value,
            'title' => $this->title,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'messages' => $this->whenLoaded('messages', function () {
                return $this->messages->map(fn ($m) => [
                    'id' => $m->id,
                    'role' => $m->role->value,
                    'content' => $m->content,
                    'citations' => $m->citations ?? [],
                    'suggested_actions' => $m->suggested_actions ?? [],
                    'created_at' => $m->created_at->toIso8601String(),
                ]);
            }),
        ];
    }
}
