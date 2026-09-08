<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ValidIranianNationalId implements ValidationRule
{
    /**
     * Run the validation rule.
     * Official Iranian National ID Check-Digit Algorithm (Architecture §5.3, §5.6).
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('کد ملی وارد شده نامعتبر است.');

            return;
        }

        $code = (string) $value;

        // Normalization: convert Persian/Arabic digits to ASCII
        $code = self::normalizeDigits($code);

        if (! self::isValid($code)) {
            $fail('کد ملی وارد شده نامعتبر است.');
        }
    }

    /**
     * Checks if given national ID conforms to check-digit specification.
     */
    public static function isValid(string $rawCode): bool
    {
        $code = self::normalizeDigits(trim($rawCode));

        // Must be exactly 10 digits
        if (! preg_match('/^\d{10}$/', $code)) {
            return false;
        }

        // Disallow repetitive patterns like 0000000000, 1111111111, ..., 9999999999
        for ($i = 0; $i <= 9; $i++) {
            if ($code === str_repeat((string) $i, 10)) {
                return false;
            }
        }

        // Check digit calculation
        $check = (int) $code[9];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $code[$i]) * (10 - $i);
        }

        $remainder = $sum % 11;

        if ($remainder < 2) {
            return $check === $remainder;
        }

        return $check === (11 - $remainder);
    }

    /**
     * Normalize Persian/Arabic digits to ASCII.
     */
    public static function normalizeDigits(string $input): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $str = str_replace($persian, $english, $input);

        return str_replace($arabic, $english, $str);
    }
}
