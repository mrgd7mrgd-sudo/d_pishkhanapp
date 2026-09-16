<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Infrastructure;

use App\Modules\AiAssistance\Domain\Enums\AiMessageRole;
use App\Modules\AiAssistance\Domain\Models\AiConversation;
use App\Modules\AiAssistance\Domain\Models\AiMessage;
use Carbon\CarbonImmutable;

final class CatalogFallbackResponder
{
    /**
     * Generates a template response directly from catalog context without LLM (§8.1.4, TASK-113).
     *
     * @param  array<string, mixed>  $retrievedContext
     * @return array{
     *     reply: string,
     *     intent: string,
     *     confidence: float,
     *     citations: list<array{type: string, id: string}>,
     *     suggested_actions: list<array{type: string, label: string, payload: array<string, mixed>}>,
     *     usage: array{model: string, input_tokens: int, output_tokens: int, cost_rials: int}
     * }
     */
    public function respond(
        string $query,
        array $retrievedContext,
        string $intent,
        float $confidence,
        ?AiConversation $conversation = null
    ): array {
        $reply = $this->buildFallbackText($retrievedContext);

        if ($conversation !== null) {
            $now = CarbonImmutable::now();
            AiMessage::query()->create([
                'conversation_id' => $conversation->id,
                'role' => AiMessageRole::Assistant,
                'content' => $reply,
                'citations' => $retrievedContext['citations'] ?? [],
                'suggested_actions' => $retrievedContext['suggested_actions'] ?? [],
                'created_at' => $now,
            ]);
        }

        return [
            'reply' => $reply,
            'intent' => $intent,
            'confidence' => $confidence,
            'citations' => $retrievedContext['citations'] ?? [],
            'suggested_actions' => $retrievedContext['suggested_actions'] ?? [],
            'usage' => [
                'model' => 'catalog-fallback',
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_rials' => 0,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildFallbackText(array $context): string
    {
        $services = $context['services'] ?? collect();
        $offices = $context['offices'] ?? [];

        $sections = [
            'پاسخ پشتیبان سامانه پیشخوان (جستجوی مستقیم کاتالوگ خدمات):',
        ];

        if (! empty($services) && count($services) > 0) {
            foreach ($services as $svc) {
                $reqs = ! empty($svc->requirements) ? implode('، ', (array) $svc->requirements) : 'مدارک هویتی معتبر';
                $fee = number_format($svc->fee_rials ?? 0);
                $sections[] = "\nخدمت مرتبط «{$svc->title}»:\n- مدارک لازم: {$reqs}\n- هزینه مصوب: {$fee} ریال\n- زمان تقریبی: {$svc->estimated_days_min} تا {$svc->estimated_days_max} روز کاری.";
            }
        }

        if (! empty($offices) && count($offices) > 0) {
            $firstOffice = is_array($offices) ? $offices[0] : (is_array($offices[0] ?? null) ? $offices[0] : null);
            $officeName = is_array($firstOffice) ? ($firstOffice['name'] ?? 'دفتر پیشخوان') : ($firstOffice->name ?? 'دفتر پیشخوان');
            $officeAddr = is_array($firstOffice) ? ($firstOffice['address'] ?? '') : ($firstOffice->address ?? '');
            $sections[] = "\nنزدیک‌ترین دفتر فعال: {$officeName} ({$officeAddr})";
        }

        if (count($sections) === 1) {
            $sections[] = "\nدرخواست شما ثبت شد. برای راهنمایی دقیق‌تر می‌توانید به بخش فهرست خدمات یا دفاتر پیشخوان مراجعه نمایید.";
        }

        return implode("\n", $sections);
    }
}
