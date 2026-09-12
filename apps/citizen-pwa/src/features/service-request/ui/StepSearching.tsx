import React from 'react';
import { useTranslation } from 'react-i18next';
import { Compass } from 'lucide-react';

export const StepSearching: React.FC = () => {
  const { t } = useTranslation();

  return (
    <div className="flex flex-col items-center justify-center py-12 px-4 text-center space-y-6">
      <div className="relative flex items-center justify-center w-28 h-28">
        <div
          className="absolute inset-0 rounded-full bg-sky-500/20 animate-ping duration-1000"
          aria-hidden="true"
        />
        <div
          className="absolute inset-2 rounded-full bg-sky-500/30 animate-pulse duration-700"
          aria-hidden="true"
        />
        <div className="relative flex items-center justify-center w-16 h-16 rounded-full bg-sky-600 text-white shadow-xl shadow-sky-600/30">
          <Compass className="w-8 h-8 animate-spin duration-3000" aria-hidden="true" />
        </div>
      </div>

      <div className="space-y-2 max-w-sm">
        <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">
          {t('request.searching_title')}
        </h3>
        <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
          {t('request.searching_desc')}
        </p>
      </div>

      <div className="flex items-center gap-2 text-xs text-slate-400 font-medium">
        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
        <span>{t('request.connecting_network')}</span>
      </div>
    </div>
  );
};
