import React from 'react';
import { useTranslation } from 'react-i18next';
import { WifiOff, RefreshCw } from 'lucide-react';

export default function OfflinePage(): React.ReactElement {
  const { t } = useTranslation();

  const handleRetry = (): void => {
    window.location.reload();
  };

  return (
    <main className="min-h-screen flex flex-col items-center justify-center p-6 text-center bg-slate-50 text-slate-800">
      <div className="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-6 text-emerald-600">
        <WifiOff aria-hidden="true" className="w-8 h-8" />
      </div>
      <h1 className="text-xl font-bold text-slate-900 mb-2">
        {t('offline.title', { defaultValue: 'اتصال اینترنت برقرار نیست' })}
      </h1>
      <p className="text-sm text-slate-600 max-w-sm mb-6 leading-relaxed">
        {t('offline.description', {
          defaultValue: 'شما در حالت آفلاین هستید. اطلاعات کش‌شده کاتالوگ و پرونده‌های قبلی در دسترس شما هستند.',
        })}
      </p>
      <button
        className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-sm font-semibold text-white transition-colors shadow-sm"
        onClick={handleRetry}
        type="button"
      >
        <RefreshCw aria-hidden="true" className="w-4 h-4" />
        <span>{t('offline.retry', { defaultValue: 'تلاش مجدد' })}</span>
      </button>
    </main>
  );
}