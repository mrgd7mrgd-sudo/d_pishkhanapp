import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { RefreshCw, X } from 'lucide-react';
import { setupServiceWorker } from './registerSW';

export function UpdatePrompt(): React.ReactElement | null {
  const { t } = useTranslation();
  const [needRefresh, setNeedRefresh] = useState(false);
  const [updateFunction, setUpdateFunction] = useState<((reloadPage?: boolean) => Promise<void>) | null>(null);

  useEffect(() => {
    const update = setupServiceWorker({
      onNeedRefresh() {
        setNeedRefresh(true);
      },
    });
    setUpdateFunction(() => update);
  }, []);

  const handleUpdate = async (): Promise<void> => {
    if (updateFunction) {
      await updateFunction(true);
    }
  };

  const handleDismiss = (): void => {
    setNeedRefresh(false);
  };

  if (!needRefresh) {
    return null;
  }

  return (
    <aside
      aria-label={t('pwa.update_available')}
      className="fixed bottom-4 inset-x-4 max-w-md mx-auto z-50 p-4 rounded-2xl bg-slate-900/95 text-white shadow-2xl border border-slate-700 flex items-center justify-between gap-3"
      role="alert"
    >
      <div className="flex items-center gap-3">
        <RefreshCw aria-hidden="true" className="w-5 h-5 text-emerald-400 animate-spin" />
        <span className="text-sm font-medium">
          {t('pwa.new_version_ready')}
        </span>
      </div>
      <div className="flex items-center gap-2">
        <button
          className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-xs font-semibold text-white transition-colors"
          onClick={handleUpdate}
          type="button"
        >
          {t('pwa.update_now')}
        </button>
        <button
          aria-label={t('pwa.close')}
          className="p-1 rounded-lg text-slate-400 hover:text-white transition-colors"
          onClick={handleDismiss}
          type="button"
        >
          <X aria-hidden="true" className="w-4 h-4" />
        </button>
      </div>
    </aside>
  );
}