<?php

declare(strict_types=1);

use App\Shared\Logging\PiiRedactionProcessor;
use App\Shared\Security\PiiRedactor;
use Monolog\Level;
use Monolog\LogRecord;

test('pii redactor masks national id, mobile, and bank card with standard patterns', function (): void {
    expect(PiiRedactor::maskNationalId('0012345678'))->toBe('001****678')
        ->and(PiiRedactor::maskMobile('09121234567'))->toBe('0912***4567')
        ->and(PiiRedactor::maskCard('6037991827364512'))->toBe('6037-****-****-4512');
});

test('pii redactor sanitizes raw national id, mobile, and cards from freeform text', function (): void {
    $text = 'Citizen with NID 0010350802 and phone 09123456789 paid with card 6037991827364512.';
    $redacted = PiiRedactor::redact($text);

    // Verify raw values are completely gone
    expect($redacted)->not->toContain('0010350802')
        ->and($redacted)->not->toContain('09123456789')
        ->and($redacted)->not->toContain('6037991827364512')
        ->and($redacted)->toContain('001****802')
        ->and($redacted)->toContain('0912***6789')
        ->and($redacted)->toContain('6037-****-****-4512');
});

test('pii redactor sanitizes nested arrays recursively', function (): void {
    $data = [
        'user' => [
            'national_id' => '0010350802',
            'mobile' => '09129876543',
            'details' => [
                'card_number' => '5022291012345678',
            ],
        ],
        'notes' => 'Contact: 09351112233',
    ];

    $redacted = PiiRedactor::redactArray($data);

    expect($redacted['user']['national_id'])->toBe('001****802')
        ->and($redacted['user']['mobile'])->toBe('0912***6543')
        ->and($redacted['user']['details']['card_number'])->toBe('5022-****-****-5678')
        ->and($redacted['notes'])->toBe('Contact: 0935***2233');
});

test('monolog pii redaction processor automatically redacts record message and context', function (): void {
    $processor = new PiiRedactionProcessor;

    $record = new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'testing',
        level: Level::Info,
        message: 'Operator logged in for citizen 0012345678 with mobile 09121112233',
        context: [
            'card' => '6037991827364512',
            'mobile' => '09129998877',
        ],
    );

    $processed = $processor($record);

    // Strict Regex Scans: Verify zero raw PII exists in processed record
    $rawNidRegex = '/\b0012345678\b/';
    $rawMobileRegex = '/\b0912[0-9]{7}\b/';
    $rawCardRegex = '/\b6037991827364512\b/';

    expect(preg_match($rawNidRegex, $processed->message))->toBe(0)
        ->and(preg_match($rawMobileRegex, $processed->message))->toBe(0)
        ->and(preg_match($rawCardRegex, (string) json_encode($processed->context)))->toBe(0)
        ->and(preg_match($rawMobileRegex, (string) json_encode($processed->context)))->toBe(0);
});
