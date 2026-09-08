import React, { forwardRef, type HTMLAttributes } from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export interface ToastProps extends HTMLAttributes<HTMLDivElement> {
  variant?: 'info' | 'success' | 'warning' | 'error';
  title?: string;
  onClose?: () => void;
}

export const Toast = forwardRef<HTMLDivElement, ToastProps>(
  ({ className, variant = 'info', title, children, onClose, ...props }, ref) => {
    const variants = {
      info: 'bg-white border-sky-200 text-slate-800',
      success: 'bg-white border-emerald-200 text-slate-800',
      warning: 'bg-white border-amber-200 text-slate-800',
      error: 'bg-white border-rose-200 text-slate-800',
    };

    return (
      <div
        ref={ref}
        role="alert"
        aria-live="polite"
        className={twMerge(
          clsx(
            'flex items-start gap-3 p-4 rounded-2xl border shadow-lg max-w-sm w-full transition-all',
            variants[variant],
            className,
          ),
        )}
        {...props}
      >
        <div className="flex-1 text-start">
          {title && <h4 className="text-sm font-semibold text-slate-900 mb-0.5">{title}</h4>}
          <div className="text-xs text-slate-600 leading-relaxed">{children}</div>
        </div>
        {onClose && (
          <button
            type="button"
            onClick={onClose}
            aria-label="بستن"
            className="text-slate-400 hover:text-slate-600 p-1 min-w-[28px] min-h-[28px] flex items-center justify-center rounded-lg"
          >
            ✕
          </button>
        )}
      </div>
    );
  },
);

Toast.displayName = 'Toast';
