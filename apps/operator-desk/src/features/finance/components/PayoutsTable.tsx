import React from 'react';
import { useTranslation } from 'react-i18next';
import { CurrencyText, toPersianDigits } from '@pishkhan/ui-kit';
import { Receipt } from 'lucide-react';
import type { PayoutItem, PayoutStatus } from '../types';

export interface PayoutsTableProps {
  payouts: PayoutItem[];
}

function getStatusBadge(status: PayoutStatus, t: (key: string) => string): React.JSX.Element {
  switch (status) {
    case 'completed':
      return (
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
          {t('finance.status_completed')}
        </span>
      );
    case 'processing':
      return (
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
          {t('finance.status_processing')}
        </span>
      );
    case 'failed':
      return (
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
          {t('finance.status_failed')}
        </span>
      );
    case 'pending':
    default:
      return (
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
          {t('finance.status_pending')}
        </span>
      );
  }
}

export const PayoutsTable: React.FC<PayoutsTableProps> = ({ payouts }) => {
  const { t } = useTranslation();

  return (
    <div
      className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-xs"
      data-testid="payouts-table-card"
    >
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <Receipt className="w-5 h-5 text-indigo-600" aria-hidden="true" />
          <span>{t('finance.payouts_title')}</span>
        </h3>
      </div>

      {payouts.length === 0 ? (
        <div className="py-8 text-center" data-testid="payouts-empty">
          <p className="text-sm text-slate-500">{t('finance.payouts_empty')}</p>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-start" aria-label={t('finance.payouts_title')}>
            <thead>
              <tr className="border-b border-slate-200 dark:border-slate-800 text-slate-500 text-xs">
                <th scope="col" className="py-3 px-4 text-start font-medium">{t('finance.col_date')}</th>
                <th scope="col" className="py-3 px-4 text-start font-medium">{t('finance.col_amount')}</th>
                <th scope="col" className="py-3 px-4 text-start font-medium">{t('finance.col_cases')}</th>
                <th scope="col" className="py-3 px-4 text-start font-medium">{t('finance.col_reference')}</th>
                <th scope="col" className="py-3 px-4 text-start font-medium">{t('finance.col_status')}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {payouts.map((payout) => (
                <tr key={payout.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/50" data-testid={`payout-row-${payout.id}`}>
                  <td className="py-3 px-4 font-mono text-xs text-slate-600 dark:text-slate-400">
                    {payout.generated_at.slice(0, 10)}
                  </td>
                  <td className="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                    <CurrencyText amountRials={payout.amount_rials} unit="toman" />
                  </td>
                  <td className="py-3 px-4 text-slate-600 dark:text-slate-400">
                    {toPersianDigits(payout.total_cases_count)} {t('finance.cases_count_unit')}
                  </td>
                  <td className="py-3 px-4 font-mono text-xs text-slate-500">
                    {payout.reference_number || '—'}
                  </td>
                  <td className="py-3 px-4">
                    {getStatusBadge(payout.status, t)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};
