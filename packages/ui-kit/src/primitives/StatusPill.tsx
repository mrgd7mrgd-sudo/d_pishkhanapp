import React from 'react';
import { type CaseStatus, getCaseStatusMeta } from '@pishkhan/domain';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export interface StatusPillProps {
  status: CaseStatus;
  className?: string;
}

/**
 * StatusPill reflects all 11 CaseStatus domain states (§3.5) with Persian labels
 */
export function StatusPill({ status, className }: StatusPillProps): React.JSX.Element {
  const meta = getCaseStatusMeta(status);

  const colorStyles: Record<string, string> = {
    slate: 'bg-slate-100 text-slate-700 border-slate-200',
    amber: 'bg-amber-50 text-amber-700 border-amber-200',
    blue: 'bg-blue-50 text-blue-700 border-blue-200',
    indigo: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    orange: 'bg-orange-50 text-orange-700 border-orange-200',
    cyan: 'bg-cyan-50 text-cyan-700 border-cyan-200',
    teal: 'bg-teal-50 text-teal-700 border-teal-200',
    violet: 'bg-violet-50 text-violet-700 border-violet-200',
    emerald: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    rose: 'bg-rose-50 text-rose-700 border-rose-200',
    zinc: 'bg-zinc-100 text-zinc-600 border-zinc-200',
  };

  const style = colorStyles[meta.color] ?? colorStyles['slate'];

  return (
    <span
      className={twMerge(
        clsx(
          'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border shadow-xs transition-colors',
          style,
          className,
        ),
      )}
      role="status"
    >
      <span className="w-1.5 h-1.5 rounded-full bg-current opacity-75" />
      {meta.label}
    </span>
  );
}
