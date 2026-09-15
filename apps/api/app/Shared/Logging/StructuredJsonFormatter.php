<?php

declare(strict_types=1);

namespace App\Shared\Logging;

use App\Shared\Security\PiiRedactor;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

final class StructuredJsonFormatter implements FormatterInterface
{
    public function format(LogRecord $record): string
    {
        $context = PiiRedactor::redactArray($record->context);
        $extra = PiiRedactor::redactArray($record->extra);

        $payload = [
            'timestamp' => $record->datetime->format('Y-m-d\TH:i:s.v\Z'),
            'level' => strtolower($record->level->getName()),
            'env' => (string) config('app.env', 'production'),
            'service' => 'api',
            'request_id' => $context['request_id'] ?? $extra['request_id'] ?? (request()->header('X-Request-Id') ?? null),
            'trace_id' => $context['trace_id'] ?? $extra['trace_id'] ?? null,
            'actor_type' => $context['actor_type'] ?? null,
            'actor_id' => $context['actor_id'] ?? null,
            'route' => $context['route'] ?? (request()->method().' '.request()->path()),
            'status' => $context['status'] ?? null,
            'duration_ms' => $context['duration_ms'] ?? null,
            'message' => PiiRedactor::redact($record->message),
            'context' => array_diff_key($context, array_flip(['request_id', 'trace_id', 'actor_type', 'actor_id', 'route', 'status', 'duration_ms'])),
        ];

        // Filter null values from top level if needed, but preserve structure
        $cleanPayload = array_filter($payload, fn ($v) => $v !== null);

        return (string) json_encode($cleanPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    }

    /**
     * @param  list<LogRecord>  $records
     */
    public function formatBatch(array $records): string
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }
}
