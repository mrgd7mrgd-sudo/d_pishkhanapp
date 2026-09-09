import React, { useRef, useEffect } from 'react';
import { normalizeDigits } from '@pishkhan/domain';

export interface OtpInputProps {
  value: string;
  onChange: (val: string) => void;
  length?: number;
  disabled?: boolean;
  autoFocus?: boolean;
  ariaLabel?: string;
  hasError?: boolean;
  digitAriaLabel?: (index: number) => string;
}

const DEFAULT_ARIA_LABEL = 'رمز یکبار مصرف';
const defaultDigitLabel = (index: number): string => `رقم ${index + 1}`;

function useOtpInputHandlers(
  value: string,
  length: number,
  onChange: (val: string) => void,
  autoFocus: boolean,
) {
  const inputRefs = useRef<(HTMLInputElement | null)[]>([]);

  useEffect(() => {
    if (autoFocus && inputRefs.current[0]) {
      inputRefs.current[0].focus();
    }
  }, [autoFocus]);

  const handleChange = (index: number, e: React.ChangeEvent<HTMLInputElement>): void => {
    const raw = normalizeDigits(e.target.value);
    const cleaned = raw.replace(/\D/g, '');
    if (!cleaned) {
      const next = value.substring(0, index) + value.substring(index + 1);
      onChange(next);
      return;
    }

    const digit = cleaned.slice(-1);
    const chars = Array.from({ length }, (_, i) => value[i] ?? ' ');
    chars[index] = digit;
    const nextVal = chars.join('').trimEnd();
    onChange(nextVal);

    if (index < length - 1 && inputRefs.current[index + 1]) {
      inputRefs.current[index + 1]?.focus();
    }
  };

  const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>): void => {
    if (e.key === 'Backspace' && !value[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
  };

  const handlePaste = (e: React.ClipboardEvent<HTMLInputElement>): void => {
    e.preventDefault();
    const pasted = normalizeDigits(e.clipboardData.getData('text/plain'));
    const cleaned = pasted.replace(/\D/g, '').slice(0, length);
    if (cleaned) {
      onChange(cleaned);
      const targetIndex = Math.min(cleaned.length, length - 1);
      inputRefs.current[targetIndex]?.focus();
    }
  };

  return { inputRefs, handleChange, handleKeyDown, handlePaste };
}

export const OtpInput: React.FC<OtpInputProps> = ({
  value,
  onChange,
  length = 5,
  disabled = false,
  autoFocus = false,
  ariaLabel = DEFAULT_ARIA_LABEL,
  hasError = false,
  digitAriaLabel = defaultDigitLabel,
}) => {
  const { inputRefs, handleChange, handleKeyDown, handlePaste } = useOtpInputHandlers(
    value,
    length,
    onChange,
    autoFocus,
  );

  const digits = Array.from({ length }, (_, i) => value[i] ?? '');

  return (
    <div
      role="group"
      aria-label={ariaLabel}
      className="flex items-center justify-center gap-2 sm:gap-3 dir-ltr"
      dir="ltr"
    >
      {digits.map((digit, idx) => (
        <input
          key={idx}
          ref={(el) => {
            inputRefs.current[idx] = el;
          }}
          type="text"
          inputMode="numeric"
          pattern="[0-9]*"
          maxLength={1}
          autoComplete={idx === 0 ? 'one-time-code' : 'off'}
          value={digit}
          disabled={disabled}
          onChange={(e) => handleChange(idx, e)}
          onKeyDown={(e) => handleKeyDown(idx, e)}
          onPaste={handlePaste}
          aria-label={digitAriaLabel(idx)}
          className={`w-11 h-12 sm:w-12 sm:h-14 text-center text-xl font-bold rounded-lg border transition-all focus:outline-none focus:ring-2 ${
            hasError
              ? 'border-error-500 text-error-600 focus:ring-error-500/20'
              : 'border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:border-brand-500 focus:ring-brand-500/20'
          } ${disabled ? 'opacity-50 cursor-not-allowed bg-neutral-100 dark:bg-neutral-800' : ''}`}
        />
      ))}
    </div>
  );
};
