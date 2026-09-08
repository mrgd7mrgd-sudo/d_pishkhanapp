import React from 'react';
import { useTranslation } from 'react-i18next';

interface PlaceholderProps {
  titleKey: string;
}

export function PlaceholderPage({ titleKey }: PlaceholderProps): React.JSX.Element {
  const { t } = useTranslation();

  return (
    <div className="flex flex-col items-center justify-center p-8 text-center min-h-[50vh]">
      <div className="max-w-md w-full p-6 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <h1 className="text-xl font-bold text-slate-900 mb-2">
          {t(`routes.${titleKey}`)}
        </h1>
        <p className="text-xs text-slate-400">
          مسیر معتبر سامانه شهروند
        </p>
      </div>
    </div>
  );
}
