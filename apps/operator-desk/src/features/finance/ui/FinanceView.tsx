import React from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Button } from '@pishkhan/ui-kit';
import { ShieldAlert, RefreshCw, Building2 } from 'lucide-react';
import { useOperatorAuthStore } from '@/features/auth/model/useOperatorAuthStore';
import { financeApi } from '../api/financeApi';
import { FinanceSummaryCards } from '../components/FinanceSummaryCards';
import { RevenueChart } from '../components/RevenueChart';
import { PayoutsTable } from '../components/PayoutsTable';
import type { FinanceSummary } from '../types';

const AccessDeniedBanner: React.FC = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();

  return (
    <main className="min-h-[70vh] flex flex-col items-center justify-center p-6 text-center" data-testid="finance-access-denied" role="alert">
      <div className="w-16 h-16 rounded-full bg-rose-100 dark:bg-rose-950/50 text-rose-600 flex items-center justify-center mb-4">
        <ShieldAlert className="w-8 h-8" aria-hidden="true" />
      </div>
      <h1 className="text-xl font-bold text-slate-900 dark:text-white mb-2">{t('finance.access_denied_title')}</h1>
      <p className="text-sm text-slate-600 dark:text-slate-400 max-w-md mb-6">{t('finance.access_denied_detail')}</p>
      <Button variant="secondary" onClick={() => navigate('/workspace')} data-testid="return-to-workspace-btn">
        {t('finance.return_to_workspace')}
      </Button>
    </main>
  );
};

const FinanceHeader: React.FC<{ summary?: FinanceSummary | undefined; isLoading: boolean; onRefresh: () => void }> = ({ summary, isLoading, onRefresh }) => {
  const { t } = useTranslation();

  return (
    <header className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
      <div>
        <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <Building2 className="w-6 h-6 text-blue-600" aria-hidden="true" />
          <span>{t('finance.title')}</span>
        </h1>
        <p className="text-sm text-slate-500 mt-1">
          {summary ? `${summary.office.name} (${summary.office.code}) — ` : ''}
          {t('finance.subtitle')}
        </p>
      </div>
      <Button variant="secondary" size="sm" onClick={onRefresh} disabled={isLoading} data-testid="finance-refresh-btn">
        <RefreshCw className={`w-4 h-4 me-1.5 ${isLoading ? 'animate-spin' : ''}`} aria-hidden="true" />
        <span>{t('finance.refresh')}</span>
      </Button>
    </header>
  );
};

export const FinanceView: React.FC = () => {
  const { t } = useTranslation();
  const operator = useOperatorAuthStore((state) => state.operator);
  const isManager = operator?.role === 'manager';

  const { data: summary, isLoading, error, refetch } = useQuery({
    queryKey: ['desk-finance'],
    queryFn: () => financeApi.fetchFinance(),
    enabled: isManager,
  });

  if (!isManager) {
    return <AccessDeniedBanner />;
  }

  return (
    <main className="p-6 max-w-7xl mx-auto space-y-6" data-testid="finance-dashboard-view">
      <FinanceHeader summary={summary} isLoading={isLoading} onRefresh={() => void refetch()} />

      {isLoading && (
        <div className="py-12 flex justify-center items-center" data-testid="finance-loading">
          <div className="w-8 h-8 border-4 border-slate-200 border-t-blue-600 rounded-full animate-spin" />
        </div>
      )}

      {error && !isLoading && (
        <div className="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center justify-between" role="alert" data-testid="finance-error">
          <span>{(error as Error).message}</span>
          <Button size="sm" variant="secondary" onClick={() => void refetch()}>
            {t('app.retry')}
          </Button>
        </div>
      )}

      {summary && !isLoading && (
        <div className="space-y-6">
          <FinanceSummaryCards summary={summary} />
          <RevenueChart points={summary.revenue_chart} />
          <PayoutsTable payouts={summary.recent_payouts} />
        </div>
      )}
    </main>
  );
};
