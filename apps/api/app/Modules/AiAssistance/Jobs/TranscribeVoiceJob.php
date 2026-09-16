<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Jobs;

use App\Modules\AiAssistance\Application\TranscribeVoiceAction;
use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class TranscribeVoiceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 90;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $audioFilePath,
        public readonly array $context = [],
        public readonly ?Citizen $citizen = null,
        public readonly ?AiConversation $conversation = null,
        public readonly int $durationSeconds = 0
    ) {
        $this->onQueue('ai');
    }

    public function handle(TranscribeVoiceAction $action): void
    {
        $action->execute(
            audio: $this->audioFilePath,
            context: $this->context,
            citizen: $this->citizen,
            conversation: $this->conversation,
            durationSeconds: $this->durationSeconds
        );
    }
}
