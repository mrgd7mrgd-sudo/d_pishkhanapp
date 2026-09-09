import React, { useEffect, useRef, type ReactNode } from 'react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export interface SheetProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
  className?: string;
  closeAriaLabel?: string;
}

const DEFAULT_CLOSE_LABEL = 'بستن پنجره';

export function Sheet({
  isOpen,
  onClose,
  title,
  children,
  className,
  closeAriaLabel = DEFAULT_CLOSE_LABEL,
}: SheetProps): React.JSX.Element | null {
  const sheetRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!isOpen) return;

    const handleKeyDown = (e: KeyboardEvent): void => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 backdrop-blur-xs"
      role="presentation"
      onClick={onClose}
    >
      <div
        ref={sheetRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="sheet-title"
        tabIndex={-1}
        onClick={(e) => e.stopPropagation()}
        className={twMerge(
          clsx(
            'w-full max-w-lg bg-white rounded-t-3xl shadow-2xl border-t border-slate-200 overflow-hidden text-start max-h-[85vh] flex flex-col',
            className,
          ),
        )}
      >
        <div className="w-12 h-1.5 bg-slate-200 rounded-full mx-auto my-3" />
        <div className="flex items-center justify-between px-6 pb-4 border-b border-slate-100">
          <h3 id="sheet-title" className="text-base font-semibold text-slate-900">
            {title}
          </h3>
          <button
            type="button"
            onClick={onClose}
            aria-label={closeAriaLabel}
            className="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
          >
            ✕
          </button>
        </div>
        <div className="p-6 overflow-y-auto">{children}</div>
      </div>
    </div>
  );
}
