import React from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@pishkhan/ui-kit';
import { useOperatorAuthStore } from '../model/useOperatorAuthStore';

export const OperatorProfileSummary: React.FC = () => {
  const { t } = useTranslation();
  const operator = useOperatorAuthStore((s) => s.operator);
  const logout = useOperatorAuthStore((s) => s.logout);
  const isLoading = useOperatorAuthStore((s) => s.isLoading);

  if (!operator) {
    return null;
  }

  return (
    <div
      role="region"
      aria-label={t('auth.welcome_operator', { name: operator.full_name })}
      className="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-5"
    >
      <div className="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
        <div>
          <h2 className="text-lg font-bold text-slate-900 dark:text-slate-100">
            {operator.full_name}
          </h2>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            {t('auth.role_label', { role: operator.role_name })}
          </p>
        </div>

        <span className="px-3 py-1 rounded-full text-xs font-semibold bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300">
          {t('auth.counter_label', { number: operator.counter_number })}
        </span>
      </div>

      <div className="text-xs text-slate-600 dark:text-slate-400 space-y-1">
        <p>{t('auth.office_label', { code: operator.office_id })}</p>
      </div>

      <Button
        type="button"
        variant="danger"
        className="w-full"
        onClick={() => logout()}
        isLoading={isLoading}
      >
        {t('auth.logout_button')}
      </Button>
    </div>
  );
};
