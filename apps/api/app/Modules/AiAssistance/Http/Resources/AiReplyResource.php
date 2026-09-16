<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AiReplyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'conversation_id' => $this->resource['conversation_id'],
            'message_id' => $this->resource['message_id'],
            'reply' => $this->resource['reply'],
            'intent' => $this->resource['intent'],
            'confidence' => $this->resource['confidence'],
            'citations' => $this->resource['citations'],
            'suggested_actions' => $this->resource['suggested_actions'],
            'usage' => $this->resource['usage'],
        ];
    }
}
