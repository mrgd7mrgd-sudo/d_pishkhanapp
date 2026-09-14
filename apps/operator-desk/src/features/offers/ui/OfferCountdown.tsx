import React from 'react';

interface OfferCountdownProps {
  remainingSeconds: number;
  totalSeconds?: number;
}

export const OfferCountdown: React.FC<OfferCountdownProps> = ({
  remainingSeconds,
  totalSeconds = 90,
}) => {
  const percentage = Math.min(100, Math.max(0, (remainingSeconds / totalSeconds) * 100));
  const isUrgent = remainingSeconds <= 20;

  // Convert English digits to Persian
  const toPersianDigits = (num: number): string => {
    return num.toString().replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)]);
  };

  return (
    <div
      className="flex items-center gap-2"
      role="timer"
      aria-label={`زمان باقی‌مانده: ${remainingSeconds} ثانیه`}
      aria-live="polite"
    >
      <div className="relative w-10 h-10 flex items-center justify-center">
        <svg className="w-10 h-10 -rotate-90" viewBox="0 0 36 36">
          <circle
            className="text-gray-200"
            strokeWidth="3"
            stroke="currentColor"
            fill="transparent"
            r="16"
            cx="18"
            cy="18"
          />
          <circle
            className={`transition-all duration-1000 ${
              isUrgent ? 'text-red-500' : 'text-emerald-500'
            }`}
            strokeWidth="3"
            strokeDasharray={100}
            strokeDashoffset={100 - percentage}
            strokeLinecap="round"
            stroke="currentColor"
            fill="transparent"
            r="16"
            cx="18"
            cy="18"
          />
        </svg>
        <span
          className={`absolute text-xs font-bold ${
            isUrgent ? 'text-red-600 animate-pulse' : 'text-gray-700'
          }`}
        >
          {toPersianDigits(remainingSeconds)}
        </span>
      </div>
      <span className="text-xs text-gray-500 font-medium">ثانیه</span>
    </div>
  );
};
