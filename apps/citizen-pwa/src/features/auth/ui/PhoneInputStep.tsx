import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Input, Button } from '@pishkhan/ui-kit';
import { normalizeDigits } from '@pishkhan/domain';
import { useAuthStore } from '../model/useAuthStore';

export const PhoneInputStep: React.FC = () => {
  const { t } = useTranslation();
  const mobile = useAuthStore((s) => s.mobile);
  const error = useAuthStore((s) => s.error);
  const submitPhone = useAuthStore((s) => s.submitPhone);
  const [localPhone, setLocalPhone] = useState(mobile);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const normalized = normalizeDigits(e.target.value);
    setLocalPhone(normalized);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    submitPhone(localPhone);
  };

  const errorMessage = error === 'invalid_phone' ? t('auth.invalid_phone') : error;

  return (
    <form onSubmit={handleSubmit} className="space-y-6" aria-labelledby="phone-step-heading">
      <div className="space-y-2">
        <h2 id="phone-step-heading" className="text-xl font-bold text-slate-900 dark:text-slate-100">
          {t('auth.phone_step_title')}
        </h2>
        <p className="text-sm text-slate-600 dark:text-slate-400">
          {t('auth.phone_step_subtitle')}
        </p>
      </div>

      <div className="space-y-4">
        <Input
          id="phone-input"
          type="tel"
          inputMode="numeric"
          autoComplete="tel"
          dir="ltr"
          label={t('auth.phone_label')}
          placeholder={t('auth.phone_placeholder')}
          value={localPhone}
          onChange={handleChange}
          error={errorMessage ?? undefined}
          autoFocus
        />

        <Button type="submit" variant="primary" className="w-full">
          {t('auth.continue')}
        </Button>
      </div>
    </form>
  );
};
