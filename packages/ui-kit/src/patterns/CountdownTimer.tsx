import React, { useEffect, useState, useRef } from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export interface CountdownTimerProps {
  initialSeconds: number;
  onExpire?: () => void;
  className?: string | undefined;
}

interface TimeParts {
  hours: number;
  minutes: number;
  seconds: number;
}

const toPersianDigits = (num: number): string => {
  const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  return num
    .toString()
    .padStart(2, '0')
    .split('')
    .map((ch) => {
      const idx = Number.parseInt(ch, 10);
      return Number.isNaN(idx) ? ch : (persianDigits[idx] ?? ch);
    })
    .join('');
};

const calculateParts = (totalSec: number): TimeParts => {
  const safeTotal = Math.max(0, totalSec);
  const hours = Math.floor(safeTotal / 3600);
  const minutes = Math.floor((safeTotal % 3600) / 60);
  const seconds = safeTotal % 60;
  return { hours, minutes, seconds };
};

/**
 * CountdownTimer counts down from server-provided remaining seconds
 */
export function CountdownTimer({
  initialSeconds,
  onExpire,
  className,
}: CountdownTimerProps): React.JSX.Element {
  const [remaining, setRemaining] = useState<number>(initialSeconds);
  const onExpireRef = useRef(onExpire);
  onExpireRef.current = onExpire;

  useEffect(() => {
    setRemaining(initialSeconds);
  }, [initialSeconds]);

  useEffect(() => {
    if (remaining <= 0) {
      return;
    }

    const timer = setInterval(() => {
      setRemaining((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          onExpireRef.current?.();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [remaining]);

  const { hours, minutes, seconds } = calculateParts(remaining);
  const isUrgent = remaining > 0 && remaining < 3600; // less than 1 hour
  const isExpired = remaining <= 0;

  const colorStyles = isExpired
    ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
    : isUrgent
      ? 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-700 animate-pulse'
      : 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700';

  const timeAriaLabel = isExpired
    ? 'مهلت اقدام به پایان رسیده است'
    : `مهلت باقی‌مانده: ${hours} ساعت و ${minutes} دقیقه و ${seconds} ثانیه`;

  return (
    <div
      className={twMerge(
        clsx(
          'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border font-mono text-sm shadow-2xs transition-colors dir-ltr',
          colorStyles,
          className,
        ),
      )}
      role="timer"
      aria-label={timeAriaLabel}
      dir="ltr"
    >
      <span data-testid="hours">{toPersianDigits(hours)}</span>
      <span className="opacity-60">:</span>
      <span data-testid="minutes">{toPersianDigits(minutes)}</span>
      <span className="opacity-60">:</span>
      <span data-testid="seconds">{toPersianDigits(seconds)}</span>
    </div>
  );
}
