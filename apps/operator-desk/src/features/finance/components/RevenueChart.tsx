import React from 'react';
import { useTranslation } from 'react-i18next';
import { toPersianDigits, formatPersianNumber, CurrencyText } from '@pishkhan/ui-kit';
import { BarChart3 } from 'lucide-react';
import type { RevenueChartPoint } from '../types';

export interface RevenueChartProps {
  points: RevenueChartPoint[];
}

const ChartBarItem: React.FC<{ point: RevenueChartPoint; maxAmount: number }> = ({ point, maxAmount }) => {
  const heightPercent = Math.max(8, Math.round((point.office_share_rials / maxAmount) * 100));
  const toman = Math.floor(point.office_share_rials / 10);
  const label = `${point.date}: ${formatPersianNumber(toman)} (${toPersianDigits(point.cases_count)})`;

  return (
    <div className="flex-1 min-w-[2.5rem] max-w-[4.5rem] flex flex-col items-center gap-1.5 group relative">
      <div className="absolute -top-9 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity bg-slate-900 text-white text-xs py-1 px-2 rounded-md whitespace-nowrap pointer-events-none z-10 shadow-lg">
        <CurrencyText amountRials={point.office_share_rials} unit="toman" />
      </div>
      <div
        tabIndex={0}
        role="img"
        aria-label={label}
        style={{ height: `${heightPercent}%` }}
        className="w-full bg-blue-500 hover:bg-blue-600 focus:bg-blue-700 rounded-t-md transition-all duration-200 cursor-pointer"
      />
      <span className="text-[10px] text-slate-500 font-mono rotate-45 md:rotate-0 mt-1 truncate max-w-full">
        {point.date.slice(5)}
      </span>
    </div>
  );
};

export const RevenueChart: React.FC<RevenueChartProps> = ({ points }) => {
  const { t } = useTranslation();

  if (!points || points.length === 0) {
    return (
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-xs text-center" data-testid="revenue-chart-empty">
        <BarChart3 className="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" aria-hidden="true" />
        <p className="text-sm text-slate-500">{t('finance.revenue_chart_empty')}</p>
      </div>
    );
  }

  const maxAmount = Math.max(...points.map((p) => p.office_share_rials), 1);

  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-xs" data-testid="revenue-chart-card">
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <BarChart3 className="w-5 h-5 text-blue-600" aria-hidden="true" />
          <span>{t('finance.revenue_chart_title')}</span>
        </h3>
      </div>
      <div className="h-56 flex items-end gap-2 pt-6 pb-2 px-1 border-b border-slate-200 dark:border-slate-800 overflow-x-auto" role="group" aria-label={t('finance.revenue_chart_title')}>
        {points.map((p) => (
          <ChartBarItem key={p.date} point={p} maxAmount={maxAmount} />
        ))}
      </div>
    </div>
  );
};
