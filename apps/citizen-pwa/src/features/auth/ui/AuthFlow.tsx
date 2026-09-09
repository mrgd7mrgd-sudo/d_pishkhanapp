import React from 'react';
import { useTranslation } from 'react-i18next';
import { PhoneInputStep } from './PhoneInputStep';
import { NationalIdInputStep } from './NationalIdInputStep';
import { OtpVerifyStep } from './OtpVerifyStep';
import { useAuthStore } from '../model/useAuthStore';

export const AuthFlow: React.FC = () => {
  const { t } = useTranslation();
  const step = useAuthStore((s) => s.step);
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const citizen = useAuthStore((s) => s.citizen);

  if (isAuthenticated && citizen) {
    return (
      <div className="w-full max-w-md mx-auto p-6 rounded-2xl bg-white dark:bg-slate-900 shadow-sm border border-slate-100 dark:border-slate-800 text-center space-y-4">
        <div className="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center font-bold text-xl">
          ✓
        </div>
        <h2 className="text-lg font-bold text-slate-900 dark:text-slate-100">
          {t('auth.login_success')}
        </h2>
        <p className="text-sm text-slate-600 dark:text-slate-400">
          {citizen.mobile}
        </p>
      </div>
    );
  }

  return (
    <div className="w-full max-w-md mx-auto p-6 rounded-2xl bg-white dark:bg-slate-900 shadow-sm border border-slate-100 dark:border-slate-800">
      <div className="mb-6 text-center">
        <h1 className="text-lg font-extrabold text-emerald-800 dark:text-emerald-400">
          {t('auth.login_title')}
        </h1>
      </div>

      {step === 'phone_input' && <PhoneInputStep />}
      {step === 'national_id_input' && <NationalIdInputStep />}
      {step === 'otp_verify' && <OtpVerifyStep />}
    </div>
  );
};
