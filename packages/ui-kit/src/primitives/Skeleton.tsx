import React, { forwardRef, type HTMLAttributes } from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export type SkeletonProps = HTMLAttributes<HTMLDivElement>;

export const Skeleton = forwardRef<HTMLDivElement, SkeletonProps>(
  ({ className, ...props }, ref) => {
    return (
      <div
        ref={ref}
        aria-hidden="true"
        className={twMerge(
          clsx('animate-pulse rounded-xl bg-slate-200/80', className),
        )}
        {...props}
      />
    );
  },
);

Skeleton.displayName = 'Skeleton';
