import React from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@pishkhan/ui-kit';
import type { VaultDocumentDetail } from '../types';

export interface VaultDocViewModalProps {
  document: VaultDocumentDetail | null;
  onClose: () => void;
  isOnline: boolean;
}

export function VaultDocViewModal({
  document,
  onClose,
  isOnline,
}: VaultDocViewModalProps): React.JSX.Element | null {
  const { t } = useTranslation();

  if (!document) return null;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label={t('vault.view_modal_title')}
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
    >
      <div className="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-5 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">{document.title}</h3>
          <button
            type="button"
            onClick={onClose}
            className="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 px-2 py-1"
            aria-label={t('common.close')}
          >
            {t('common.close')}
          </button>
        </div>

        {!isOnline ? (
          <div className="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 space-y-2">
            <p className="font-semibold">{t('vault.offline_view_title')}</p>
            <p className="leading-relaxed">{t('vault.offline_view_desc')}</p>
          </div>
        ) : document.view_url ? (
          <div className="space-y-3">
            <div className="rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center min-h-[200px]">
              <img
                src={document.view_url}
                alt={document.title}
                className="max-h-80 w-auto object-contain"
              />
            </div>
            <p className="text-[11px] text-slate-400 text-center">{t('vault.signed_url_notice')}</p>
          </div>
        ) : (
          <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 text-xs text-slate-500 text-center">
            {t('vault.no_view_url')}
          </div>
        )}

        <div className="flex justify-end pt-2">
          <Button variant="secondary" size="sm" onClick={onClose} aria-label={t('common.close')}>
            {t('common.close')}
          </Button>
        </div>
      </div>
    </div>
  );
}
