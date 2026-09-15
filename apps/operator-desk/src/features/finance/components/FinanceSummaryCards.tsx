import React from 'react';
import { useTranslation } from 'react-i18next';
import { CurrencyText, toPersianDigits } from '@pishkhan/ui-kit';
import { Wallet, TrendingUp, CheckCircle2, ShieldCheck, AlertTriangle } from 'lucide-react';
import type { FinanceSummary } from '../types';

export interface FinanceSummaryCardsProps {
  summary: FinanceSummary;
}

const PayableCard: React.FC<{ balanceRials: number }> = ({ balanceRials }) => {
  const { t } = useTranslation();
  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
      <div className="flex items-center justify-between mb-3">
        <span className="text-sm font-medium text-slate-500 dark:text-slate-400">{t('finance.payable_balance')}</span>
        <div className="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center">
          <Wallet className="w-5 h-5" aria-hidden="true" />
        </div>
      </div>
      <div className="text-2xl font-bold text-slate-900 dark:text-white">
        <CurrencyText amountRials={balanceRials} unit="toman" testId="payable-balance-value" className="text-emerald-600 dark:text-emerald-400" />
      </div>
    </div>
  );
};

const RevenueCard: React.FC<{ officeShareRials: number }> = ({ officeShareRials }) => {
  const { t } = useTranslation();
  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
      <div className="flex items-center justify-between mb-3">
        <span className="text-sm font-medium text-slate-500 dark:text-slate-400">{t('finance.period_revenue')}</span>
        <div className="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 flex items-center justify-center">
          <TrendingUp className="w-5 h-5" aria-hidden="true" />
        </div>
      </div>
      <div className="text-2xl font-bold text-slate-900 dark:text-white">
        <CurrencyText amountRials={officeShareRials} unit="toman" testId="period-revenue-value" />
      </div>
    </div>
  );
};

const CasesCard: React.FC<{ casesCount: number; totalFeeRials: number }> = ({ casesCount, totalFeeRials }) => {
  const { t } = useTranslation();
  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
      <div className="flex items-center justify-between mb-3">
        <span className="text-sm font-medium text-slate-500 dark:text-slate-400">{t('finance.settled_cases')}</span>
        <div className="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 flex items-center justify-center">
          <CheckCircle2 className="w-5 h-5" aria-hidden="true" />
        </div>
      </div>
      <div className="flex items-baseline justify-between">
        <span className="text-2xl font-bold text-slate-900 dark:text-white" data-testid="settled-cases-count">
          {toPersianDigits(casesCount)} {t('finance.cases_count_unit')}
        </span>
        <div className="text-xs text-slate-500">
          <CurrencyText amountRials={totalFeeRials} unit="toman" />
        </div>
      </div>
    </div>
  );
};

const IntegrityCard: React.FC<{ isBalanced: boolean; discrepancyRials: number }> = ({ isBalanced, discrepancyRials }) => {
  const { t } = useTranslation();
  const cardStyle = isBalanced
    ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/40'
    : 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800';

  return (
    <div className={`border rounded-xl p-5 shadow-xs flex flex-col justify-between ${cardStyle}`} data-testid="ledger-integrity-card" role="region" aria-label={t('finance.ledger_status')}>
      <div className="flex items-center justify-between mb-3">
        <span className="text-sm font-medium text-slate-700 dark:text-slate-300">{t('finance.ledger_status')}</span>
        <div className={`w-9 h-9 rounded-lg flex items-center justify-center ${isBalanced ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
          {isBalanced ? <ShieldCheck className="w-5 h-5" aria-hidden="true" /> : <AlertTriangle className="w-5 h-5" aria-hidden="true" />}
        </div>
      </div>
      <div className="text-sm font-semibold">
        {isBalanced ? (
          <span className="text-emerald-700 dark:text-emerald-300" data-testid="ledger-balanced-badge">{t('finance.ledger_balanced')}</span>
        ) : (
          <div className="text-rose-700 dark:text-rose-300 flex flex-col gap-1" data-testid="ledger-imbalance-badge">
            <span>{t('finance.ledger_imbalance')}</span>
            <span className="text-xs font-mono">{t('finance.discrepancy_amount')}: <CurrencyText amountRials={discrepancyRials} /></span>
          </div>
        )}
      </div>
    </div>
  );
};

export const FinanceSummaryCards: React.FC<FinanceSummaryCardsProps> = ({ summary }) => {
  const isBalanced = summary.ledger_integrity.is_office_ledger_consistent && summary.ledger_integrity.is_global_ledger_balanced;

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" data-testid="finance-summary-cards">
      <PayableCard balanceRials={summary.payable_balance_rials} />
      <RevenueCard officeShareRials={summary.period_summary.office_share_rials} />
      <CasesCard casesCount={summary.period_summary.total_cases_count} totalFeeRials={summary.period_summary.total_fee_rials} />
      <IntegrityCard isBalanced={isBalanced} discrepancyRials={summary.ledger_integrity.discrepancy_rials} />
    </div>
  );
};
