import React from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { StatusPill, TurnOwnerChip, ServiceTagBadge } from '@pishkhan/ui-kit';
import type { CaseSummary } from '../types';
import { formatJalaliDate } from '../utils/formatters';

export interface CaseCardProps {
  item: CaseSummary;
}

export function CaseCard({ item }: CaseCardProps): React.JSX.Element {
  const { t } = useTranslation();
  const createdDate = formatJalaliDate(item.created_at);
  const stepProgress = `${item.current_step} ${t('cases.of')} ${item.total_steps}`;

  return (
    <div className="p-4 rounded-2xl bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 shadow-2xs hover:shadow-md transition-all space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <span className="font-mono text-sm font-bold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
            {item.tracking_code}
          </span>
          <ServiceTagBadge tag={item.service.tag as 'online' | 'semi-online' | 'in-person'} />
        </div>
        <StatusPill status={item.status} />
      </div>

      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
        <h3 className="text-base font-semibold text-slate-900 dark:text-slate-100">
          {item.service.title}
        </h3>
        <TurnOwnerChip owner={item.turn_owner} labelOverride={item.turn_owner_label} />
      </div>

      <div className="flex flex-wrap items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-800/80 gap-2">
        <div className="flex items-center gap-3">
          <span>{createdDate}</span>
          {item.assigned_office ? <span>• {item.assigned_office.name}</span> : null}
          <span>• {stepProgress}</span>
        </div>

        <Link
          to={`/cases/${item.tracking_code}`}
          className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline px-2 py-1"
        >
          {t('cases.view_details')}
        </Link>
      </div>
    </div>
  );
}
