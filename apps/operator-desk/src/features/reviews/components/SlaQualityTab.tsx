import React from 'react';
import { Award, AlertTriangle, TrendingUp, CheckCircle, ShieldCheck } from 'lucide-react';
import type { SlaStatsData, SlaTrendPoint } from '../types';

export interface SlaQualityTabProps {
  stats?: SlaStatsData | undefined;
  isLoading: boolean;
}

const EVENT_TYPE_LABELS: Record<string, string> = {
  late_return: 'تأخیر در عودت مدرک فیزیکی',
  offer_declined: 'رد غیرمجاز پیشنهاد ارجاع (Dispatch)',
  dispatch_expired: 'انقضای بدون پاسخ پیشنهاد توزیع',
  review_delayed: 'تأخیر در بررسی کارشناسی پرونده',
};

const SlaTrendBar: React.FC<{ point: SlaTrendPoint; maxBreaches: number }> = ({
  point,
  maxBreaches,
}) => {
  const heightPercent = point.breaches_count > 0
    ? Math.max(12, Math.round((point.breaches_count / maxBreaches) * 100))
    : 4;
  const label = `${point.date}: ${point.breaches_count} نقض (${point.penalties} نمره جریمه)`;

  return (
    <div className="flex-1 min-w-[2rem] max-w-[3.5rem] flex flex-col items-center gap-1 group relative">
      <div className="absolute -top-9 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity bg-slate-900 text-white text-[10px] py-1 px-2 rounded-md whitespace-nowrap pointer-events-none z-10 shadow-lg">
        {label}
      </div>
      <div
        tabIndex={0}
        role="img"
        aria-label={label}
        style={{ height: `${heightPercent}%` }}
        className={`w-full rounded-t-md transition-all duration-200 cursor-pointer ${
          point.breaches_count > 0
            ? 'bg-rose-500 hover:bg-rose-600 focus:bg-rose-700'
            : 'bg-emerald-300 dark:bg-emerald-800 hover:bg-emerald-400'
        }`}
      />
      <span className="text-[9px] text-slate-500 font-mono rotate-45 md:rotate-0 mt-1 truncate max-w-full">
        {point.date.slice(5)}
      </span>
    </div>
  );
};

export const SlaQualityTab: React.FC<SlaQualityTabProps> = ({ stats, isLoading }) => {
  if (isLoading || !stats) {
    return (
      <div className="text-center py-12 text-sm text-slate-500">
        در حال بارگذاری شاخص‌ها و روند کیفی SLA...
      </div>
    );
  }

  const maxBreaches = Math.max(...stats.trend.map((p) => p.breaches_count), 1);
  const totalBreaches = stats.trend.reduce((sum, p) => sum + p.breaches_count, 0);
  const totalPenalties = stats.trend.reduce((sum, p) => sum + p.penalties, 0);

  return (
    <section aria-labelledby="sla-quality-heading" className="space-y-6">
      <h2 id="sla-quality-heading" className="sr-only">
        سنجش کیفیت خدمات و رویدادهای نقض SLA
      </h2>

      {/* SLA Score KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
          <div className="flex items-center justify-between text-xs text-slate-500 mb-1">
            <span>امتیاز زنده کیفیت SLA</span>
            <Award className="w-4 h-4 text-amber-500" aria-hidden="true" />
          </div>
          <div className="flex items-baseline gap-1.5">
            <span className="text-2xl font-black text-slate-900 dark:text-white font-mono">
              {stats.current_score.toFixed(2)}
            </span>
            <span className="text-xs text-slate-500">از ۱۰۰</span>
          </div>
          <div className="mt-2 text-[11px] text-slate-500 flex items-center gap-1">
            <ShieldCheck className="w-3.5 h-3.5 text-blue-500" aria-hidden="true" />
            <span>محاسبه در بازه گردان ۳۰ روزه</span>
          </div>
        </div>

        <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
          <div className="flex items-center justify-between text-xs text-slate-500 mb-1">
            <span>تعداد کل نقض‌های ثبت‌شده</span>
            <AlertTriangle className="w-4 h-4 text-rose-500" aria-hidden="true" />
          </div>
          <div className="text-2xl font-black text-slate-900 dark:text-white font-mono">
            {totalBreaches}
          </div>
          <div className="mt-2 text-[11px] text-slate-500">
            {totalBreaches === 0 ? 'عملکرد کاملاً ایده‌آل و بدون تخطی' : 'نیازمند کاهش زمان پاسخ و بررسی'}
          </div>
        </div>

        <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
          <div className="flex items-center justify-between text-xs text-slate-500 mb-1">
            <span>مجموع نمرات منفی جریمه</span>
            <TrendingUp className="w-4 h-4 text-blue-500" aria-hidden="true" />
          </div>
          <div className="text-2xl font-black text-slate-900 dark:text-white font-mono">
            {totalPenalties}
          </div>
          <div className="mt-2 text-[11px] text-slate-500">
            کسر شده از سقف نمره ۱۰۰
          </div>
        </div>
      </div>

      {/* 30-Day SLA Trend Chart */}
      <div className="p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <TrendingUp className="w-4 h-4 text-blue-600" aria-hidden="true" />
            <span>روند نقض‌های SLA در ۳۰ روز گذشته</span>
          </h3>
          <div className="flex items-center gap-3 text-xs">
            <span className="flex items-center gap-1 text-slate-500">
              <span className="w-2.5 h-2.5 rounded-xs bg-rose-500 inline-block" />
              رویداد نقض
            </span>
            <span className="flex items-center gap-1 text-slate-500">
              <span className="w-2.5 h-2.5 rounded-xs bg-emerald-400 inline-block" />
              عملکرد منطبق
            </span>
          </div>
        </div>

        <div
          className="h-44 flex items-end gap-1.5 pt-4 pb-2 px-1 border-b border-slate-200 dark:border-slate-800 overflow-x-auto"
          role="group"
          aria-label="نمودار روند روزانه نقض‌های توافق‌نامه سطح خدمت SLA"
        >
          {stats.trend.map((point) => (
            <SlaTrendBar key={point.date} point={point} maxBreaches={maxBreaches} />
          ))}
        </div>
      </div>

      {/* Breach Category Breakdown */}
      <div className="p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-3">
        <h3 className="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <AlertTriangle className="w-4 h-4 text-amber-500" aria-hidden="true" />
          <span>تفکیک رویدادهای نقض بر اساس نوع تخطی</span>
        </h3>

        {stats.breakdown.length === 0 ? (
          <div className="text-center py-6 text-xs text-emerald-600 dark:text-emerald-400 flex items-center justify-center gap-1.5">
            <CheckCircle className="w-4 h-4" aria-hidden="true" />
            <span>در بازه ۳۰ روز گذشته هیچ نقض SLA در هیچ دسته‌ای ثبت نشده است.</span>
          </div>
        ) : (
          <div className="divide-y divide-slate-100 dark:divide-slate-800">
            {stats.breakdown.map((item) => (
              <div key={item.event_type} className="py-2.5 flex items-center justify-between text-xs">
                <span className="font-semibold text-slate-800 dark:text-slate-200">
                  {EVENT_TYPE_LABELS[item.event_type] || item.event_type}
                </span>
                <div className="flex items-center gap-4">
                  <span className="text-slate-500 font-mono">{item.count} بار</span>
                  <span className="text-rose-600 dark:text-rose-400 font-mono font-bold">
                    -{item.total_penalty} نمره
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
};
