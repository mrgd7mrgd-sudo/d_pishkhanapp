<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Application;

use App\Integration\Ai\AiProvider;
use App\Integration\Ai\AiRequest;
use App\Integration\Ai\AiTask;
use App\Modules\AiAssistance\Domain\EntityNameCollector;
use App\Modules\AiAssistance\Domain\Enums\AiMessageRole;
use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\AiAssistance\Domain\Models\AiMessage;
use App\Modules\AiAssistance\Domain\Models\AiUsageRecord;
use App\Modules\AiAssistance\Domain\PiiRedactor;
use App\Modules\AiAssistance\Domain\RedactionMap;
use App\Modules\AiAssistance\Infrastructure\AiBudgetGuard;
use App\Modules\AiAssistance\Infrastructure\CatalogFallbackResponder;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Shared\Resilience\CircuitBreaker;
use Carbon\CarbonImmutable;
use Throwable;

final class AnswerGenerator
{
    private readonly CircuitBreaker $circuitBreaker;
    private readonly AiBudgetGuard $budgetGuard;
    private readonly CatalogFallbackResponder $fallbackResponder;

    public function __construct(
        private readonly IntentClassifier $intentClassifier,
        private readonly ContextRetriever $contextRetriever,
        private readonly PiiRedactor $piiRedactor,
        private readonly EntityNameCollector $entityCollector,
        private readonly AiProvider $aiProvider,
        ?AiBudgetGuard $budgetGuard = null,
        ?CatalogFallbackResponder $fallbackResponder = null,
        ?CircuitBreaker $circuitBreaker = null
    ) {
        $this->budgetGuard = $budgetGuard ?? new AiBudgetGuard;
        $this->fallbackResponder = $fallbackResponder ?? new CatalogFallbackResponder;
        $this->circuitBreaker = $circuitBreaker ?? new CircuitBreaker('ai_egress_proxy', 3, 60);
    }

    /**
     * Executes the 7-step RAG pipeline (§5.6 #10, §8.1).
     *
     * @param  array<string, mixed>  $context
     * @return array{
     *     reply: string,
     *     intent: string,
     *     confidence: float,
     *     citations: list<array{type: string, id: string}>,
     *     suggested_actions: list<array{type: string, label: string, payload: array<string, mixed>}>,
     *     usage: array{model: string, input_tokens: int, output_tokens: int, cost_rials: int}
     * }
     */
    public function generate(
        string $message,
        array $context = [],
        ?Citizen $citizen = null,
        ?AiConversation $conversation = null
    ): array {
        // Step 1: Detect intent
        $intentResult = $this->intentClassifier->classify($message);
        $intent = $intentResult['intent'];
        $confidence = $intentResult['confidence'];

        // If out-of-domain, handle gracefully without hallucination
        if ($intent === 'out_of_domain') {
            return $this->handleOutOfDomain($intent, $confidence, $conversation);
        }

        // Step 2: Context Retrieval
        $retrieved = $this->contextRetriever->retrieve($message, $context, $citizen);

        // Fallback Guard: Proxy unavailable, Circuit Breaker Open, or Monthly Budget Exhausted (§8.1.4, §9.4)
        if (! $this->aiProvider->isAvailable() || ! $this->circuitBreaker->isAvailable() || ! $this->budgetGuard->isBudgetAvailable()) {
            return $this->fallbackResponder->respond($message, $retrieved, $intent, $confidence, $conversation);
        }

        // Step 3: PII Redaction
        $redactionMap = new RedactionMap;
        $knownEntities = $citizen !== null ? $this->entityCollector->collectFromCitizen($citizen) : [];
        $redactedMessage = $this->piiRedactor->redact($message, $redactionMap, $knownEntities);
        $redactedGrounding = $this->piiRedactor->redact($retrieved['grounding_text'], $redactionMap, $knownEntities);

        // Step 4: Prompt Assembly
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = "{$redactedGrounding}\n\nپرسش شهروند:\n{$redactedMessage}";

        // Step 5: LLM Completion via AiProvider with Circuit Breaker resilience
        $aiRequest = new AiRequest(
            task: AiTask::ChatbotResponse,
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            maxTokens: 1024,
            temperature: 0.3
        );

        try {
            $aiResponse = $this->circuitBreaker->execute(fn () => $this->aiProvider->complete($aiRequest));
        } catch (Throwable) {
            return $this->fallbackResponder->respond($message, $retrieved, $intent, $confidence, $conversation);
        }

        // Step 6: Token Restoration (Server-side only)
        $restoredReply = $this->piiRedactor->restore($aiResponse->content, $redactionMap);

        // Step 7: Record Usage and Persist
        $costRials = max(100, (int) round(($aiResponse->inputTokens * 0.4) + ($aiResponse->outputTokens * 1.2)));
        $this->budgetGuard->recordUsage($costRials);
        $this->persistConversationState($conversation, $message, $restoredReply, $retrieved['citations'], $retrieved['suggested_actions'], $aiResponse->model, $aiResponse->inputTokens, $aiResponse->outputTokens, $costRials);

        return [
            'reply' => $restoredReply,
            'intent' => $intent,
            'confidence' => $confidence,
            'citations' => $retrieved['citations'],
            'suggested_actions' => $retrieved['suggested_actions'],
            'usage' => [
                'model' => $aiResponse->model,
                'input_tokens' => $aiResponse->inputTokens,
                'output_tokens' => $aiResponse->outputTokens,
                'cost_rials' => $costRials,
            ],
        ];
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
شما دستیار هوشمند و رسمی سامانه پیشخوان خدمات دولت و امور شهروندی ایران هستید.
وظیفه شما راهنمایی دقیق، محترمانه و کوتاه شهروندان بر اساس اطلاعات موثق زیر است.
قواعد مهم:
۱. فقط و فقط بر اساس اطلاعات مستند ارائه‌شده در بخش «زمینه مستندات» پاسخ دهید.
۲. مدارک لازم و مراحل را به صورت واضح و بالت‌گذاری‌شده توضیح دهید.
۳. در صورتی که کاربر درباره دفتری پرسیده است، اطلاعات نزدیک‌ترین دفتر را ذکر کنید.
۴. هیچ‌گونه اطلاعات ساختگی یا نامعتبر ارائه ندهید.
PROMPT;
    }

    /**
     * @return array{
     *     reply: string,
     *     intent: string,
     *     confidence: float,
     *     citations: list<array{type: string, id: string}>,
     *     suggested_actions: list<array{type: string, label: string, payload: array<string, mixed>}>,
     *     usage: array{model: string, input_tokens: int, output_tokens: int, cost_rials: int}
     * }
     */
    private function handleOutOfDomain(string $intent, float $confidence, ?AiConversation $conversation): array
    {
        $reply = 'من دستیار هوشمند خدمات پیشخوان دولت و امور شهروندی هستم و تنها می‌توانم به پرسش‌های مربوط به خدمات دولتی، مدارک مورد نیاز، وضعیت پرونده‌ها و دفاتر پیشخوان پاسخ دهم. برای راهنمایی در سایر زمینه‌ها لطفاً از سامانه‌های عمومی استفاده نمایید.';

        $this->persistConversationState($conversation, '', $reply, [], [], 'rule-based-fallback', 0, 0, 0);

        return [
            'reply' => $reply,
            'intent' => $intent,
            'confidence' => $confidence,
            'citations' => [],
            'suggested_actions' => [],
            'usage' => [
                'model' => 'rule-based-fallback',
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_rials' => 0,
            ],
        ];
    }

    /**
     * @param  list<array{type: string, id: string}>  $citations
     * @param  list<array{type: string, label: string, payload: array<string, mixed>}>  $suggestedActions
     */
    private function persistConversationState(
        ?AiConversation $conversation,
        string $userMessage,
        string $assistantReply,
        array $citations,
        array $suggestedActions,
        string $model,
        int $inputTokens,
        int $outputTokens,
        int $costRials
    ): void {
        if ($conversation === null) {
            return;
        }

        $now = CarbonImmutable::now();

        if ($userMessage !== '') {
            AiMessage::query()->create([
                'conversation_id' => $conversation->id,
                'role' => AiMessageRole::User,
                'content' => $userMessage,
                'created_at' => $now,
            ]);
        }

        AiMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => AiMessageRole::Assistant,
            'content' => $assistantReply,
            'citations' => $citations,
            'suggested_actions' => $suggestedActions,
            'created_at' => $now,
        ]);

        if ($inputTokens > 0 || $outputTokens > 0) {
            AiUsageRecord::query()->create([
                'conversation_id' => $conversation->id,
                'model' => $model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost_rials' => $costRials,
                'created_at' => $now,
            ]);
        }
    }
}
