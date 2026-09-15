import React from 'react';

export interface CurrencyTextProps {
  amountRials: number;
  unit?: 'toman' | 'rial' | undefined;
  showUnit?: boolean | undefined;
  className?: string | undefined;
  testId?: string | undefined;
}

const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

/**
 * Convert any string of ASCII digits to Persian digits.
 */
export function toPersianDigits(input: number | string): string {
  return input.toString().replace(/\d/g, (d) => PERSIAN_DIGITS[parseInt(d, 10)] ?? d);
}

/**
 * Format a number with Persian thousands separators (٬) and Persian digits.
 */
export function formatPersianNumber(value: number): string {
  const parts = Math.abs(Math.floor(value)).toString().split('.');
  const intPart = parts[0]?.replace(/\B(?=(\d{3})+(?!\d))/g, '٬') ?? '۰';
  const persian = toPersianDigits(intPart);
  return value < 0 ? `-${persian}` : persian;
}

/**
 * CurrencyText Primitive (Architecture §4.1, §4.4, TASK-092).
 * Formats Rials to Tomans with Persian number grouping and WAI-ARIA accessibility.
 */
export const CurrencyText: React.FC<CurrencyTextProps> = ({
  amountRials,
  unit = 'toman',
  showUnit = true,
  className = '',
  testId,
}) => {
  const isToman = unit === 'toman';
  const displayAmount = isToman ? Math.floor(amountRials / 10) : amountRials;
  const formattedNumber = formatPersianNumber(displayAmount);
  const unitLabel = isToman ? 'تومان' : 'ریال';

  const fullLabel = showUnit ? `${formattedNumber} ${unitLabel}` : formattedNumber;

  return (
    <span
      className={`inline-flex items-baseline gap-1 font-medium font-vazirmatn text-text-primary ${className}`}
      aria-label={fullLabel}
      data-testid={testId}
      dir="rtl"
    >
      <span className="tabular-nums">{formattedNumber}</span>
      {showUnit && (
        <span className="text-xs text-text-muted select-none font-normal" aria-hidden="true">
          {unitLabel}
        </span>
      )}
    </span>
  );
};
