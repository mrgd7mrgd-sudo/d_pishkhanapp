<?php

declare(strict_types=1);

namespace App\Shared\Logging;

use App\Shared\Security\PiiRedactor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class PiiRedactionProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $redactedMessage = PiiRedactor::redact($record->message);
        $redactedContext = PiiRedactor::redactArray($record->context);
        $redactedExtra = PiiRedactor::redactArray($record->extra);

        return $record->with(
            message: $redactedMessage,
            context: $redactedContext,
            extra: $redactedExtra,
        );
    }
}
