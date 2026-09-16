<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance;

use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\AiAssistance\Domain\Models\AiMessage;
use App\Modules\AiAssistance\Domain\Models\AiUsageRecord;
use App\Modules\AiAssistance\Infrastructure\Policies\AiConversationPolicy;
use App\Modules\AiAssistance\Infrastructure\Policies\AiMessagePolicy;
use App\Modules\AiAssistance\Infrastructure\Policies\AiUsageRecordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AiAssistanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings handled in module or container
    }

    public function boot(): void
    {
        Gate::policy(AiConversation::class, AiConversationPolicy::class);
        Gate::policy(AiMessage::class, AiMessagePolicy::class);
        Gate::policy(AiUsageRecord::class, AiUsageRecordPolicy::class);
    }
}
