<?php

declare(strict_types=1);

namespace App\Shared\Security;

use App\Modules\Identity\Domain\Rules\ValidIranianNationalId;

final class PiiRedactor
{
    private const CARD_PATTERN = '/\b(?:\d{4}[-\s]?){3}\d{4}\b/u';

    private const MOBILE_PATTERN = '/\b09\d{9}\b/u';

    private const DIGITS_10_PATTERN = '/\b\d{10}\b/u';

    private const IBAN_PATTERN = '/\bIR\d{24}\b/iu';

    public static function maskNationalId(string $nid): string
    {
        $clean = preg_replace('/\D/', '', $nid) ?? '';
        if (strlen($clean) !== 10) {
            return '***';
        }

        return substr($clean, 0, 3).'****'.substr($clean, 7, 3);
    }

    public static function maskMobile(string $mobile): string
    {
        $clean = preg_replace('/\D/', '', $mobile) ?? '';
        if (strlen($clean) !== 11) {
            return '***';
        }

        return substr($clean, 0, 4).'***'.substr($clean, 7, 4);
    }

    public static function maskCard(string $card): string
    {
        $clean = preg_replace('/\D/', '', $card) ?? '';
        if (strlen($clean) !== 16) {
            return '***';
        }

        return substr($clean, 0, 4).'-****-****-'.substr($clean, 12, 4);
    }

    public static function redact(string $text): string
    {
        // 1. Redact bank card numbers (16 digits)
        $text = (string) preg_replace_callback(self::CARD_PATTERN, function (array $m): string {
            $digits = preg_replace('/\D/', '', $m[0]) ?? '';
            if (strlen($digits) === 16) {
                return substr($digits, 0, 4).'-****-****-'.substr($digits, 12, 4);
            }

            return '[REDACTED_CARD]';
        }, $text);

        // 2. Redact Iranian mobile numbers (09xxxxxxxxx)
        $text = (string) preg_replace_callback(self::MOBILE_PATTERN, function (array $m): string {
            return substr($m[0], 0, 4).'***'.substr($m[0], 7, 4);
        }, $text);

        // 3. Redact 10-digit numbers (National ID vs Postal Code)
        $text = (string) preg_replace_callback(self::DIGITS_10_PATTERN, function (array $m): string {
            $val = $m[0];
            if (ValidIranianNationalId::isValid($val)) {
                return substr($val, 0, 3).'****'.substr($val, 7, 3);
            }

            return substr($val, 0, 5).'*****';
        }, $text);

        // 4. Redact IBAN
        return (string) preg_replace(self::IBAN_PATTERN, '[REDACTED_IBAN]', $text);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function redactArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $result[$key] = self::redact($value);
            } elseif (is_array($value)) {
                /** @var array<string, mixed> $value */
                $result[$key] = self::redactArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
