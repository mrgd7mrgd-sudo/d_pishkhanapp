import React from 'react';
import { useTranslation } from 'react-i18next';
import { Badge, Button } from '@pishkhan/ui-kit';
import type { VaultDocumentItem } from '../types';

export interface VaultDocCardProps {
  item: VaultDocumentItem;
  onView: (id: string) => void;
  onDelete: (id: string) => void;
}

const checkExpiry = (expiryDate?: string | null): { isExpired: boolean; isNearExpiry: boolean; daysLeft: number } => {
  if (!expiryDate) return { isExpired: false, isNearExpiry: false, daysLeft: 999 };
  const expiry = new Date(expiryDate).getTime();
  const now = Date.now();
  const diffDays = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
  return {
    isExpired: diffDays <= 0,
    isNearExpiry: diffDays > 0 && diffDays <= 30,
    daysLeft: diffDays,
  };
};

export function VaultDocCard({ item, onView, onDelete }: VaultDocCardProps): React.JSX.Element {
  const { t } = useTranslation();
  const { isExpired, isNearExpiry, daysLeft } = checkExpiry(item.expiry_date);

  const versionText = item.latest_version ? `نسخه ${item.latest_version.version}` : 'نسخه ۱';
  const sizeKb = item.latest_version ? `${Math.round(item.latest_version.size_bytes / 1024)} کیلوبایت` : '';

  return (
    <div className="p-4 rounded-2xl bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 shadow-2xs hover:shadow-md transition-all space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <span className="text-xs px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-mono text-slate-700 dark:text-slate-300">
            {versionText}
          </span>
          {item.is_verified ? (
            <Badge variant="success">{t('vault.verified_badge')}</Badge>
          ) : (
            <Badge variant="neutral">{t('vault.unverified_badge')}</Badge>
          )}
        </div>

        {isExpired ? (
          <Badge variant="danger">{t('vault.expired_badge')}</Badge>
        ) : isNearExpiry ? (
          <Badge variant="warning">{`${t('vault.near_expiry_badge')} (${daysLeft} روز)`}</Badge>
        ) : null}
      </div>

      <div>
        <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">{item.title}</h3>
        {item.doc_number ? (
          <p className="text-xs text-slate-500 font-mono mt-0.5">
            {`${t('vault.doc_number_label')}: ${item.doc_number}`}
          </p>
        ) : null}
      </div>

      {item.attributes.length > 0 ? (
        <div className="flex flex-wrap gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
          {item.attributes.map((attr, i) => (
            <span key={i} className="text-[11px] px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
              {`${attr.label}: ${attr.value}`}
            </span>
          ))}
        </div>
      ) : null}

      <div className="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800/80 text-xs text-slate-400">
        <span>{sizeKb}</span>
        <div className="flex items-center gap-2">
          <Button variant="secondary" size="sm" onClick={() => onView(item.id)} aria-label={t('vault.view_btn')}>
            {t('vault.view_btn')}
          </Button>
          <Button variant="ghost" size="sm" onClick={() => onDelete(item.id)} aria-label={t('vault.delete_btn')}>
            {t('vault.delete_btn')}
          </Button>
        </div>
      </div>
    </div>
  );
}
