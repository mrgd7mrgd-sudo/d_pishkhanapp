<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Shared\Logging\PiiRedactionProcessor;
use App\Shared\Logging\StructuredJsonFormatter;
use App\Shared\Security\PiiRedactor;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

final class LogRedactionTest extends TestCase
{
    public function test_pii_redactor_masks_national_id_mobile_card_and_postal_code(): void
    {
        $rawMessage = 'User with mobile 09123456789 and national id 0012345678 submitted card 6037991827364512 at postal code 1998765432';

        $redacted = PiiRedactor::redact($rawMessage);

        // Mobile is redacted
        $this->assertStringNotContainsString('09123456789', $redacted);
        $this->assertStringContainsString('0912***6789', $redacted);

        // Bank card is redacted
        $this->assertStringNotContainsString('6037991827364512', $redacted);
        $this->assertStringContainsString('6037-****-****-4512', $redacted);

        // National ID / 10-digit is redacted
        $this->assertStringNotContainsString('0012345678', $redacted);
        $this->assertStringNotContainsString('1998765432', $redacted);
    }

    public function test_pii_redaction_processor_redacts_record_message_and_context(): void
    {
        $processor = new PiiRedactionProcessor;

        $record = new LogRecord(
            datetime: new DateTimeImmutable('2026-09-15T12:00:00Z'),
            channel: 'testing',
            level: Level::Info,
            message: 'Payment received for card 5022291012345678 from 09351112233',
            context: [
                'user_mobile' => '09351112233',
                'national_id' => '0012345678',
                'nested' => [
                    'card_number' => '5022291012345678',
                ],
            ],
            extra: [],
        );

        $processed = $processor($record);

        // Message checks
        $this->assertStringNotContainsString('5022291012345678', $processed->message);
        $this->assertStringNotContainsString('09351112233', $processed->message);
        $this->assertStringContainsString('5022-****-****-5678', $processed->message);
        $this->assertStringContainsString('0935***2233', $processed->message);

        // Context checks
        $this->assertSame('0935***2233', $processed->context['user_mobile']);
        $this->assertStringNotContainsString('0012345678', (string) $processed->context['national_id']);
        $this->assertSame('5022-****-****-5678', $processed->context['nested']['card_number']);
    }

    public function test_structured_json_formatter_produces_valid_json_with_architecture_keys(): void
    {
        $formatter = new StructuredJsonFormatter;

        $record = new LogRecord(
            datetime: new DateTimeImmutable('2026-09-15T12:00:00Z'),
            channel: 'testing',
            level: Level::Info,
            message: 'case.created',
            context: [
                'request_id' => 'req_test123',
                'trace_id' => 'trace_xyz789',
                'actor_type' => 'citizen',
                'actor_id' => 'cit_01',
                'route' => 'POST /api/v1/cases',
                'status' => 201,
                'duration_ms' => 45,
                'case_id' => 'case-101',
                'phone' => '09121112233',
            ],
            extra: [],
        );

        $jsonOutput = $formatter->format($record);
        $data = json_decode($jsonOutput, true);

        $this->assertIsArray($data);
        $this->assertSame('api', $data['service']);
        $this->assertSame('info', $data['level']);
        $this->assertSame('req_test123', $data['request_id']);
        $this->assertSame('trace_xyz789', $data['trace_id']);
        $this->assertSame('citizen', $data['actor_type']);
        $this->assertSame('cit_01', $data['actor_id']);
        $this->assertSame('POST /api/v1/cases', $data['route']);
        $this->assertSame(201, $data['status']);
        $this->assertSame(45, $data['duration_ms']);
        $this->assertSame('case.created', $data['message']);

        // Check PII redacted in context
        $this->assertStringNotContainsString('09121112233', $jsonOutput);
        $this->assertSame('0912***2233', $data['context']['phone']);
    }
}
