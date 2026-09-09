import React, { forwardRef, type InputHTMLAttributes } from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  error?: string | undefined;
  label?: string | undefined;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ className, error, label, id, ...props }, ref) => {
    return (
      <div className="w-full text-start">
        {label && (
          <label htmlFor={id} className="block text-xs font-medium text-slate-700 mb-1.5">
            {label}
          </label>
        )}
        <input
          ref={ref}
          id={id}
          className={twMerge(
            clsx(
              'w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 text-sm min-h-[44px]',
              'focus:outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition-colors',
              error && 'border-rose-500 focus:border-rose-600 focus:ring-rose-600',
              className,
            ),
          )}
          {...props}
        />
        {error && <p className="text-xs text-rose-600 mt-1">{error}</p>}
      </div>
    );
  },
);

Input.displayName = 'Input';
