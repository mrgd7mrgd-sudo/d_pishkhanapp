import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, OtpInput } from '@pishkhan/ui-kit';
import { useOperatorAuthStore } from '../model/useOperatorAuthStore';

interface TimerRowProps {
  isLoading: boolean;
  resendAvailable: boolean;
  countdown: number;
  onResend: () => void;
  onBack: () => void;
}

const OtpHeader: React.FC<{ maskedMobile: string }> = ({ maskedMobile }) => {
  const { t } = useTranslation();
  return (
    <div className="space-y-1 text-center">
      <h2 id="otp-verify-heading" className="text-xl font-bold text-slate-900 dark:text-slate-100">
        {t('auth.otp_title')}
      </h2>
      <p className="text-sm text-slate-600 dark:text-slate-400">
        {t('auth.otp_subtitle', { mobile: maskedMobile })}
      </p>
    </div>
  );
};

interface InputSectionProps {
  code: string;
  onChange: (val: string) => void;
  isLoading: boolean;
  errorMessage: string | null;
}

const OtpInputSection: React.FC<InputSectionProps> = ({
  code,
  onChange,
  isLoading,
  errorMessage,
}) => (
  <div className="flex flex-col items-center gap-2">
    <OtpInput
      value={code}
      onChange={onChange}
      length={5}
      disabled={isLoading}
      autoFocus
      hasError={Boolean(errorMessage)}
    />
    {errorMessage && (
      <p role="alert" className="text-xs text-rose-600 font-medium mt-1">
        {errorMessage}
      </p>
    )}
  </div>
);

const OtpTimerRow: React.FC<TimerRowProps> = ({
  isLoading,
  resendAvailable,
  countdown,
  onResend,
  onBack,
}) => {
  const { t } = useTranslation();
  return (
    <div className="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
      <button
        type="button"
        onClick={onBack}
        className="text-sky-700 dark:text-sky-400 hover:underline font-medium focus:outline-none"
        disabled={isLoading}
      >
        {t('auth.back_to_credentials')}
      </button>

      {resendAvailable ? (
        <button
          type="button"
          onClick={onResend}
          className="text-sky-700 dark:text-sky-400 hover:underline font-bold focus:outline-none"
          disabled={isLoading}
        >
          {t('auth.resend_code')}
        </button>
      ) : (
        <span aria-live="polite" className="font-mono tabular-nums">
          {t('auth.resend_in', { seconds: countdown })}
        </span>
      )}
    </div>
  );
};

export const OperatorOtpStep: React.FC = () => {
  const { t } = useTranslation();
  const [code, setCode] = useState('');
  const { maskedMobile, countdown, resendAvailable, isLoading, error } = useOperatorAuthStore();
  const { decrementCountdown, verifyOtpCode, resendOtp, setStep } = useOperatorAuthStore();

  useEffect(() => {
    if (resendAvailable) {
      return undefined;
    }
    const timer = setInterval(() => decrementCountdown(), 1000);
    return () => clearInterval(timer);
  }, [resendAvailable, decrementCountdown]);

  const errorMessage = error === 'invalid_otp' ? t('auth.invalid_otp') : error;

  return (
    <form
      onSubmit={async (e) => {
        e.preventDefault();
        await verifyOtpCode(code);
      }}
      className="space-y-6"
      aria-labelledby="otp-verify-heading"
    >
      <OtpHeader maskedMobile={maskedMobile} />
      <div className="space-y-6">
        <OtpInputSection
          code={code}
          onChange={setCode}
          isLoading={isLoading}
          errorMessage={errorMessage}
        />
        <OtpTimerRow
          isLoading={isLoading}
          resendAvailable={resendAvailable}
          countdown={countdown}
          onResend={async () => {
            setCode('');
            await resendOtp();
          }}
          onBack={() => setStep('credentials')}
        />
        <Button
          type="submit"
          variant="primary"
          className="w-full"
          isLoading={isLoading}
          disabled={code.length !== 5 || isLoading}
        >
          {t('auth.verify_button')}
        </Button>
      </div>
    </form>
  );
};
