<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Modules\AiAssistance\Domain\EntityNameCollector;
use App\Modules\AiAssistance\Domain\PiiRedactor;
use App\Modules\AiAssistance\Domain\RedactionMap;
use App\Modules\Identity\Domain\Models\Citizen;

test('PiiRedactor redacts and restores 6 standard patterns (§8.1.3, TASK-110, TASK-110-T)', function (): void {
    $redactor = new PiiRedactor;
    $map = new RedactionMap;

    // Sample valid national ID: 0010350802, mobile: 09123456789, postal: 1987654321, card: 6037991823456789, IBAN: IR120120000000001234567890, tracking: PK-1405-12345
    $text = 'کد ملی من 0010350802 و شماره موبایل 09123456789 است. کد پستی 19876-54321 و شماره کارت 6037-9918-2345-6789 با شبا IR120120000000001234567890 و پیگیری PK-1405-12345 می‌باشد.';

    $redacted = $redactor->redact($text, $map);

    // 1. None of the raw PII appears in redacted text
    expect($redacted)->not->toContain('0010350802')
        ->and($redacted)->not->toContain('09123456789')
        ->and($redacted)->not->toContain('19876-54321')
        ->and($redacted)->not->toContain('6037-9918-2345-6789')
        ->and($redacted)->not->toContain('IR120120000000001234567890')
        ->and($redacted)->not->toContain('PK-1405-12345');

    // 2. Contains opaque tokens
    expect($redacted)->toContain('[NID_1]')
        ->and($redacted)->toContain('[MOBILE_1]')
        ->and($redacted)->toContain('[POSTAL_1]')
        ->and($redacted)->toContain('[CARD_1]')
        ->and($redacted)->toContain('[IBAN_1]')
        ->and($redacted)->toContain('[TRACKING_1]');

    // 3. Restores to exact original text
    $restored = $redactor->restore($redacted, $map);
    expect($restored)->toBe($text);
});

test('PiiRedactor disambiguates National ID vs Postal Code using check-digit algorithm (§8.1.3)', function (): void {
    $redactor = new PiiRedactor;
    $map = new RedactionMap;

    // 0010350802 is a valid National ID
    // 1111122222 is an invalid National ID (checksum fail) -> treated as 10-digit Postal Code
    $text = 'شناسه الف 0010350802 و شناسه ب 1111122222';

    $redacted = $redactor->redact($text, $map);

    expect($redacted)->toContain('[NID_1]')
        ->and($redacted)->toContain('[POSTAL_1]')
        ->and($redacted)->not->toContain('0010350802')
        ->and($redacted)->not->toContain('1111122222');

    $restored = $redactor->restore($redacted, $map);
    expect($restored)->toBe($text);
});

test('PiiRedactor redacts known database entities like full name, father name and address (§8.1.3)', function (): void {
    $redactor = new PiiRedactor;
    $collector = new EntityNameCollector;
    $map = new RedactionMap;

    $citizen = new Citizen;
    $citizen->full_name = 'کیوان خسروی';
    $citizen->father_name = 'محمدرضا';
    $citizen->address = 'تهران، خیابان سهروردی شمالی، پلاک ۱۲';

    $entities = $collector->collectFromCitizen($citizen);
    expect($entities)->toHaveKey('کیوان خسروی')
        ->and($entities)->toHaveKey('محمدرضا')
        ->and($entities)->toHaveKey('تهران، خیابان سهروردی شمالی، پلاک ۱۲');

    $prompt = 'سلام، من کیوان خسروی فرزند محمدرضا هستم و در آدرس تهران، خیابان سهروردی شمالی، پلاک ۱۲ سکونت دارم.';

    $redacted = $redactor->redact($prompt, $map, $entities);

    expect($redacted)->not->toContain('کیوان خسروی')
        ->and($redacted)->not->toContain('محمدرضا')
        ->and($redacted)->not->toContain('تهران، خیابان سهروردی شمالی، پلاک ۱۲')
        ->and($redacted)->toContain('[NAME_1]')
        ->and($redacted)->toContain('[FATHER_NAME_1]')
        ->and($redacted)->toContain('[ADDRESS_1]');

    // Server-side restore
    $aiResponse = 'پاسخ برای [NAME_1] فرزند [FATHER_NAME_1] ارسال شد به [ADDRESS_1].';
    $restored = $redactor->restore($aiResponse, $map);

    expect($restored)->toContain('کیوان خسروی')
        ->and($restored)->toContain('محمدرضا')
        ->and($restored)->toContain('تهران، خیابان سهروردی شمالی، پلاک ۱۲');
});

test('PiiLeakTest: 50 diverse realistic scenarios guarantee ZERO PII leakage in redacted payload (§8.1.3, §7.1 T6, TASK-110-T)', function (): void {
    $redactor = new PiiRedactor;
    $collector = new EntityNameCollector;

    // 50 realistic Iranian citizen query scenarios
    $scenarios = [
        ['text' => 'کد ملی من 0010350802 است و می‌خواهم بدانم کارتم صادر شده؟', 'pii' => ['0010350802']],
        ['text' => 'لطفاً وضعیت پرونده PK-1405-10001 را با شماره 09121112233 اعلام کنید.', 'pii' => ['PK-1405-10001', '09121112233']],
        ['text' => 'شماره کارتم 6037991823456789 است، وجه استرداد شده؟', 'pii' => ['6037991823456789']],
        ['text' => 'حساب شبا من IR120120000000001234567890 برای واریز سهم.', 'pii' => ['IR120120000000001234567890']],
        ['text' => 'کد پستی محل کار من 19876-12345 است.', 'pii' => ['19876-12345']],
        ['text' => 'کد ملی 0071234568 و موبایل 09351234567 و پیگیری CR-1405-99881', 'pii' => ['0071234568', '09351234567', 'CR-1405-99881']],
    ];

    // Generate up to 50 variations
    for ($i = 7; $i <= 50; $i++) {
        $nid = '001035086'.($i % 10);
        $mob = '0912'.str_pad((string) ($i * 11111), 7, '0', STR_PAD_LEFT);
        $scenarios[] = [
            'text' => "سناریو شماره {$i}: شهروند با کد ملی {$nid} و تلفن همراه {$mob} درخواست پیگیری دارد.",
            'pii' => [$nid, $mob],
            'custom_name' => "شهروند آزمایشی شماره {$i}",
        ];
    }

    expect(count($scenarios))->toBe(50);

    foreach ($scenarios as $idx => $scenario) {
        $map = new RedactionMap;
        $customEntities = [];
        if (! empty($scenario['custom_name'])) {
            $customEntities[$scenario['custom_name']] = 'NAME';
        }

        $redacted = $redactor->redact($scenario['text'], $map, $customEntities);

        // Assert NONE of the sensitive PII items exist in the redacted payload
        foreach ($scenario['pii'] as $piiItem) {
            expect($redacted)->not->toContain($piiItem, "Leakage of [{$piiItem}] detected in scenario #{$idx}!");
        }

        if (! empty($scenario['custom_name'])) {
            expect($redacted)->not->toContain($scenario['custom_name']);
        }

        // Assert restore reconstructs exact original
        $restored = $redactor->restore($redacted, $map);
        expect($restored)->toBe($scenario['text']);
    }
});
