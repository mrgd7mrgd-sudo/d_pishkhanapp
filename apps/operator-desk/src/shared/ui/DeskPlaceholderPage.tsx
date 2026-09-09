import React from 'react';
import { useTranslation } from 'react-i18next';

interface DeskPlaceholderProps {
  titleKey: string;
}

export function DeskPlaceholderPage({ titleKey }: DeskPlaceholderProps): React.JSX.Element {
  const { t } = useTranslation();

  return (
    <div className="p-8">
      <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h1 className="text-xl font-bold text-slate-900 mb-2">
          {t(`routes.${titleKey}`)}
        </h1>
        <p className="text-sm text-slate-500">
          {t('app.module_description')}
        </p>
      </div>
    </div>
  );
}
