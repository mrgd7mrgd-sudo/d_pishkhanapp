import React from 'react';
import { type TurnOwner, getTurnOwnerMeta } from '@pishkhan/domain';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export interface TurnOwnerChipProps {
  owner: TurnOwner;
  labelOverride?: string | undefined;
  className?: string | undefined;
}

const COLOR_MAP: Record<string, string> = {
  orange: 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-700/50',
  blue: 'bg-blue-50 text-blue-800 border-blue-300 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-700/50',
  cyan: 'bg-cyan-50 text-cyan-800 border-cyan-300 dark:bg-cyan-950/40 dark:text-cyan-300 dark:border-cyan-700/50',
  violet: 'bg-purple-50 text-purple-800 border-purple-300 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-700/50',
  slate: 'bg-slate-100 text-slate-800 border-slate-300 dark:bg-slate-800/60 dark:text-slate-200 dark:border-slate-700',
};

/**
 * TurnOwnerChip renders the turn owner chip according to Architecture §4.7 and domain metadata
 */
export function TurnOwnerChip({ owner, labelOverride, className }: TurnOwnerChipProps): React.JSX.Element {
  const meta = getTurnOwnerMeta(owner);
  const colorClass = COLOR_MAP[meta.color] ?? COLOR_MAP['slate'];
  const text = labelOverride || meta.label;

  return (
    <span
      className={twMerge(
        clsx(
          'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border shadow-xs transition-colors',
          colorClass,
          className,
        ),
      )}
      role="status"
      title={meta.description}
      aria-label={text}
    >
      <span className="w-2 h-2 rounded-full bg-current opacity-80 animate-pulse" aria-hidden="true" />
      <span>{text}</span>
    </span>
  );
}
