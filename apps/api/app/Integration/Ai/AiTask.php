<?php

declare(strict_types=1);

namespace App\Integration\Ai;

enum AiTask: string
{
    case ChatbotResponse = 'chatbot_response';
    case IntentClassification = 'intent_classification';
    case PersianVoiceTranscription = 'persian_voice_transcription';
    case DocumentQualityVision = 'document_quality_vision';
    case CaseSummaryOperator = 'case_summary_operator';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
