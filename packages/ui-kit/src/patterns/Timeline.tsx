import React from 'react';
import {
  type TimelineStepStatus,
  type TurnOwner,
  getTimelineStepStatusMeta,
} from '@pishkhan/domain';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { TurnOwnerChip } from './TurnOwnerChip';

export interface TimelineItem {
  id: string;
  title: string;
  description?: string | null | undefined;
  status: TimelineStepStatus;
  turnOwner?: TurnOwner | undefined;
  turnOwnerLabel?: string | undefined;
  occurredAt?: string | undefined;
  officeNote?: string | null | undefined;
  durationActualMinutes?: number | null | undefined;
}

export interface TimelineProps {
  items: TimelineItem[];
  className?: string | undefined;
  ariaLabel?: string | undefined;
  officeNoteLabel?: string | undefined;
  renderDate?: (isoDate: string) => string;
}

const DEFAULT_TIMELINE_ARIA_LABEL = 'تایم‌لاین پیشرفت پرونده';
const DEFAULT_OFFICE_NOTE_LABEL = 'توضیح دفتر: ';

const STATUS_ICONS: Record<TimelineStepStatus, React.ReactNode> = {
  done: (
    <svg className="w-4 h-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
    </svg>
  ),
  current: (
    <span className="w-2.5 h-2.5 rounded-full bg-blue-600 animate-ping" aria-hidden="true" />
  ),
  pending: (
    <span className="w-2 h-2 rounded-full bg-slate-400" aria-hidden="true" />
  ),
  warning: (
    <svg className="w-4 h-4 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
    </svg>
  ),
  failed: (
    <svg className="w-4 h-4 text-rose-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
    </svg>
  ),
};

const STATUS_NODE_STYLES: Record<TimelineStepStatus, string> = {
  done: 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 ring-4 ring-emerald-50 dark:ring-emerald-950/30',
  current: 'border-blue-500 bg-blue-50 dark:bg-blue-950/40 ring-4 ring-blue-100 dark:ring-blue-900/40',
  pending: 'border-slate-300 bg-white dark:bg-slate-800 ring-2 ring-slate-100 dark:ring-slate-700',
  warning: 'border-amber-500 bg-amber-50 dark:bg-amber-950/40 ring-4 ring-amber-100 dark:ring-amber-900/40',
  failed: 'border-rose-500 bg-rose-50 dark:bg-rose-950/40 ring-4 ring-rose-100 dark:ring-rose-900/40',
};

interface TimelineStepItemProps {
  item: TimelineItem;
  officeNoteLabel: string;
  renderDate?: ((isoDate: string) => string) | undefined;
}

function TimelineStepItem({ item, officeNoteLabel, renderDate }: TimelineStepItemProps): React.JSX.Element {
  const meta = getTimelineStepStatusMeta(item.status);
  const nodeClass = STATUS_NODE_STYLES[item.status] ?? STATUS_NODE_STYLES['pending'];
  const metaLabelText = `(${meta.label})`;

  return (
    <li className="ms-6 relative group">
      <span
        className={clsx(
          'absolute -start-[35px] flex items-center justify-center w-7 h-7 rounded-full border transition-all duration-200',
          nodeClass,
        )}
        aria-hidden="true"
      >
        {STATUS_ICONS[item.status]}
      </span>

      <div className="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xs rounded-xl p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div className="flex flex-wrap items-center justify-between gap-2 mb-1">
          <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
            <span>{item.title}</span>
            <span className="text-xs font-normal text-slate-500">{metaLabelText}</span>
          </h3>
          {item.turnOwner ? (
            <TurnOwnerChip owner={item.turnOwner} labelOverride={item.turnOwnerLabel} />
          ) : null}
        </div>

        {item.description ? (
          <p className="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
            {item.description}
          </p>
        ) : null}

        {item.officeNote ? (
          <div className="mt-2 p-2 rounded-lg bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-800/40 text-xs text-amber-800 dark:text-amber-200">
            <span className="font-medium">{officeNoteLabel}</span>
            {item.officeNote}
          </div>
        ) : null}

        {item.occurredAt ? (
          <time dateTime={item.occurredAt} className="block text-[11px] text-slate-400 mt-2 text-start font-mono">
            {renderDate ? renderDate(item.occurredAt) : item.occurredAt}
          </time>
        ) : null}
      </div>
    </li>
  );
}

/**
 * Timeline pattern component renders an accessible graphical step-by-step timeline
 */
export function Timeline({
  items,
  className,
  ariaLabel = DEFAULT_TIMELINE_ARIA_LABEL,
  officeNoteLabel = DEFAULT_OFFICE_NOTE_LABEL,
  renderDate,
}: TimelineProps): React.JSX.Element {
  return (
    <ol
      className={twMerge('relative border-s border-slate-200 dark:border-slate-700 ms-4 space-y-6', className)}
      aria-label={ariaLabel}
    >
      {items.map((item) => (
        <TimelineStepItem
          key={item.id}
          item={item}
          officeNoteLabel={officeNoteLabel}
          renderDate={renderDate}
        />
      ))}
    </ol>
  );
}
