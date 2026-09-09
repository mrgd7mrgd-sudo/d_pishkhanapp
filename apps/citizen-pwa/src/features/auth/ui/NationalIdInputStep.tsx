import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Input, Button } from '@pishkhan/ui-kit';
import { normalizeDigits } from '@pishkhan/domain';
import { useAuthStore } from '../model/useAuthStore';

interface ActionProps {
  isLoading: boolean;
  onBack: () => void;
}

const StepActions: React.FC<ActionProps> = ({ isLoading, onBack }) => {
  const { t } = useTranslation();
  return (
    <div className="flex items-center gap-3">
      <Button
        type="button"
        variant="secondary"
        className="flex-1"
        onClick={onBack}
        disabled={isLoading}
      >
        {t('auth.back')}
      </Button>
      <Button
        type="submit"
        variant="primary"
        className="flex-1"
        isLoading={isLoading}
      >
        {t('auth.continue')}
      </Button>
    </div>
  );
};

export const NationalIdInputStep: React.FC = () => {
  const { t } = useTranslation();
  const nationalId = useAuthStore((s) => s.nationalId);
  const error = useAuthStore((s) => s.error);
  const isLoading = useAuthStore((s) => s.isLoading);
  const submitNationalId = useAuthStore((s) => s.submitNationalId);
  const setStep = useAuthStore((s) => s.setStep);
  const [localId, setLocalId] = useState(nationalId);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await submitNationalId(localId);
  };

  const errorMessage = error === 'invalid_national_id' ? t('auth.invalid_national_id') : error;

  return (
    <form onSubmit={handleSubmit} className="space-y-6" aria-labelledby="national-id-heading">
      <div className="space-y-2">
        <h2 id="national-id-heading" className="text-xl font-bold text-slate-900 dark:text-slate-100">
          {t('auth.national_id_step_title')}
        </h2>
        <p className="text-sm text-slate-600 dark:text-slate-400">
          {t('auth.national_id_step_subtitle')}
        </p>
      </div>

      <div className="space-y-4">
        <Input
          id="national-id-input"
          type="text"
          inputMode="numeric"
          maxLength={10}
          dir="ltr"
          label={t('auth.national_id_label')}
          placeholder={t('auth.national_id_placeholder')}
          value={localId}
          onChange={(e) => setLocalId(normalizeDigits(e.target.value))}
          error={errorMessage ?? undefined}
          autoFocus
        />

        <StepActions isLoading={isLoading} onBack={() => setStep('phone_input')} />
      </div>
    </form>
  );
};
