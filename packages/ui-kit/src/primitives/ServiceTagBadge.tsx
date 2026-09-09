import React, { forwardRef, type HTMLAttributes } from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { type ServiceTag, getServiceTagMeta } from '@pishkhan/domain';

export interface ServiceTagBadgeProps extends HTMLAttributes<HTMLSpanElement> {
  tag: ServiceTag;
  size?: 'sm' | 'md';
}

const TAG_STYLES: Record<ServiceTag, string> = {
  online: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60',
  'semi-online': 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60',
  'in-person': 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60',
};

export const ServiceTagBadge = forwardRef<HTMLSpanElement, ServiceTagBadgeProps>(
  ({ tag, size = 'sm', className, ...props }, ref) => {
    const meta = getServiceTagMeta(tag);

    const sizeClasses = size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm';

    return (
      <span
        ref={ref}
        data-testid={`service-tag-badge-${tag}`}
        className={twMerge(
          clsx(
            'inline-flex items-center gap-1 rounded-full font-medium border transition-colors',
            sizeClasses,
            TAG_STYLES[tag],
            className,
          ),
        )}
        title={meta?.description}
        {...props}
      >
        <span className="w-1.5 h-1.5 rounded-full bg-current opacity-80" aria-hidden="true" />
        {meta?.label ?? tag}
      </span>
    );
  },
);

ServiceTagBadge.displayName = 'ServiceTagBadge';
