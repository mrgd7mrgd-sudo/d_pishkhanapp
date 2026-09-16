<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Application;

use App\Integration\Ai\AiProvider;
use App\Integration\Ai\AiRequest;
use App\Integration\Ai\AiTask;
use Throwable;

final class IntentClassifier
{
    private const ALLOWED_INTENTS = [
        'service_inquiry',
        'case_tracking',
        'office_locator',
        'general_faq',
        'out_of_domain',
    ];

    public function __construct(
        private readonly AiProvider $aiProvider
    ) {}

    /**
     * Classifies citizen message intent (§5.6 #10, §8.1.2).
     *
     * @return array{intent: string, confidence: float}
     */
    public function classify(string $message): array
    {
        // 1. Fast heuristic pre-check for blatant out-of-domain queries
        if ($this->isOutOfDomainHeuristic($message)) {
            return ['intent' => 'out_of_domain', 'confidence' => 0.98];
        }

        try {
            $prompt = $this->buildClassificationPrompt($message);
            $response = $this->aiProvider->complete(new AiRequest(
                task: AiTask::IntentClassification,
                messages: [
                    ['role' => 'system', 'content' => 'You are an intent classifier for Iranian Pishkhan government services. Classify the user query into exactly one intent: service_inquiry, case_tracking, office_locator, general_faq, out_of_domain. Output ONLY valid JSON: {"intent":"...","confidence":0.95}'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                maxTokens: 128,
                temperature: 0.1
            ));

            $parsed = $this->parseJsonResponse($response->content);
            if ($parsed !== null) {
                return $parsed;
            }
        } catch (Throwable) {
            // Fallback to domain heuristic below
        }

        return $this->fallbackHeuristic($message);
    }

    private function buildClassificationPrompt(string $message): string
    {
        return "دسته‌بندی پیام زیر:\n".trim($message);
    }

    /**
     * @return array{intent: string, confidence: float}|null
     */
    private function parseJsonResponse(string $content): ?array
    {
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (is_array($data) && isset($data['intent']) && in_array($data['intent'], self::ALLOWED_INTENTS, true)) {
                $confidence = isset($data['confidence']) && is_numeric($data['confidence'])
                    ? (float) $data['confidence']
                    : 0.90;

                return [
                    'intent' => (string) $data['intent'],
                    'confidence' => min(1.0, max(0.1, $confidence)),
                ];
            }
        }

        return null;
    }

    private function isOutOfDomainHeuristic(string $text): bool
    {
        $patterns = [
            '/\b(?:شعر|حافظ|سعدی|مولانا|فردوسی|خیام|شاهنامه|غزل|قصیده)\b/u',
            '/\b(?:جوک|لطیفه|داستان طنز)\b/u',
            '/\b(?:پایتون|جاوااسکریپت|برنامه‌نویسی|کد پایتون|html|css)\b/iu',
            '/\b(?:آشپزی|دستور پخت|طرز تهیه|قورمه‌سبزی|قیمه|کباب)\b/u',
            '/\b(?:آب و هوا|پیش‌بینی هوا|هواشناسی|دمای هوا)\b/u',
            '/\b(?:فوتبال|لیگ برتر|استقلال|پرسپولیس|بارسلونا|رئال مادرید)\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{intent: string, confidence: float}
     */
    private function fallbackHeuristic(string $text): array
    {
        if (preg_match('/\b(?:پیگیری|وضعیت پرونده|کد پیگیری|شماره پرونده|CR-\d{4}|PK-\d{4})\b/u', $text)) {
            return ['intent' => 'case_tracking', 'confidence' => 0.92];
        }

        if (preg_match('/\b(?:نزدیک‌ترین دفتر|آدرس دفتر|دفاتر|دفتر پیشخوان|شعبه|ساعت کاری|نوبت)\b/u', $text)) {
            return ['intent' => 'office_locator', 'confidence' => 0.91];
        }

        if (preg_match('/\b(?:کارت ملی|شناسنامه|گواهینامه|پاسپورت|گذرنامه|مدارک|هزینه|شرایط|تعویض|صدور)\b/u', $text)) {
            return ['intent' => 'service_inquiry', 'confidence' => 0.94];
        }

        return ['intent' => 'general_faq', 'confidence' => 0.85];
    }
}
