import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, OtpInput } from '@pishkhan/ui-kit';
import { useAuthStore } from '../model/useAuthStore';

interface TimerProps {
  isLoading: boolean;
  resendAvailable: boolean;
  countdown: number;
  onResend: () => void;
  onChangePhone: () => void;
}

const OtpTimerRow: React.FC<TimerProps> = ({
  isLoading,
  resendAvailable,
  countdown,
  onResend,
  onChangePhone,
}) => {
  const { t } = useTranslation();
  return (
    <div className="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
      <button
        type="button"
        onClick={onChangePhone}
        className="text-emerald-700 dark:text-emerald-400 hover:underline font-medium focus:outline-none"
        disabled={isLoading}
      >
        {t('auth.change_phone')}
      </button>

      {resendAvailable ? (
        <button
          type="button"
          onClick={onResend}
          className="text-emerald-700 dark:text-emerald-400 hover:underline font-bold focus:outline-none"
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

const OtpHeader: React.FC<{ maskedMobile: string }> = ({ maskedMobile }) => {
  const { t } = useTranslation();
  return (
    <div className="space-y-2 text-center">
      <h2 id="otp-heading" className="text-xl font-bold text-slate-900 dark:text-slate-100">
        {t('auth.otp_step_title')}
      </h2>
      <p className="text-sm text-slate-600 dark:text-slate-400">
        {t('auth.otp_sent_to', { mobile: maskedMobile })}
      </p>
    </div>
  );
};

const useOtpTimer = (resendAvailable: boolean, decrementCountdown: () => void) => {
  useEffect(() => {
    if (resendAvailable) {
      return undefined;
    }
    const timer = setInterval(() => decrementCountdown(), 1000);
    return () => clearInterval(timer);
  }, [resendAvailable, decrementCountdown]);
};

export const OtpVerifyStep: React.FC = () => {
  const { t } = useTranslation();
  const [code, setCode] = useState('');
  const { maskedMobile, countdown, resendAvailable, isLoading, error } = useAuthStore();
  const { decrementCountdown, verifyOtpCode, resendOtp, setStep } = useAuthStore();

  useOtpTimer(resendAvailable, decrementCountdown);
  const errorMessage = error === 'invalid_otp' ? t('auth.invalid_otp') : error;

  return (
    <form
      onSubmit={async (e) => {
        e.preventDefault();
        await verifyOtpCode(code);
      }}
      className="space-y-6"
      aria-labelledby="otp-heading"
    >
      <OtpHeader maskedMobile={maskedMobile} />

      <div className="space-y-6">
        <div className="flex flex-col items-center gap-2">
          <OtpInput
            value={code}
            onChange={setCode}
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

        <OtpTimerRow
          isLoading={isLoading}
          resendAvailable={resendAvailable}
          countdown={countdown}
          onResend={async () => {
            setCode('');
            await resendOtp();
          }}
          onChangePhone={() => setStep('phone_input')}
        />

        <Button
          type="submit"
          variant="primary"
          className="w-full"
          isLoading={isLoading}
          disabled={code.length !== 5 || isLoading}
        >
          {t('auth.verify_and_login')}
        </Button>
      </div>
    </form>
  );
};
