import React, { useState } from 'react';
import type { DeliveryItem } from '../types';

interface OtpConfirmModalProps {
  delivery: DeliveryItem | null;
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (deliveryId: string, otp: string) => Promise<void>;
  isSubmitting?: boolean;
}

export const OtpConfirmModal: React.FC<OtpConfirmModalProps> = ({
  delivery,
  isOpen,
  onClose,
  onConfirm,
  isSubmitting = false,
}) => {
  const [otp, setOtp] = useState('');
  const [error, setError] = useState<string | null>(null);

  if (!isOpen || !delivery) {
    return null;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (otp.length !== 6 || !/^\d+$/.test(otp)) {
      setError('کد تأیید باید دقیقاً ۶ رقم باشد.');
      return;
    }

    try {
      setError(null);
      await onConfirm(delivery.id, otp);
      setOtp('');
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setError(err.message || 'کد تأیید نامعتبر یا منقضی شده است.');
      } else {
        setError('کد تأیید نامعتبر است.');
      }
    }
  };

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="otp-modal-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
        <h2 id="otp-modal-title" className="text-lg font-bold text-slate-900 dark:text-white">
          تأیید تحویل مرسوله با کد یک‌بارمصرف (OTP)
        </h2>
        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
          کد ۶ رقمی پیامک‌شده به شهروند را وارد کنید تا پرونده تکمیل گردد.
        </p>

        <div className="mt-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
          <div>
            <span className="font-medium">بارکد مرسوله:</span>{' '}
            <span className="font-mono">{delivery.tracking_barcode ?? delivery.id.slice(0, 8)}</span>
          </div>
          <div className="mt-1">
            <span className="font-medium">مقصد:</span> {delivery.destination_address}
          </div>
        </div>

        <form onSubmit={handleSubmit} className="mt-5 space-y-4">
          <div>
            <label htmlFor="delivery-otp-input" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              کد ۶ رقمی تحویل
            </label>
            <input
              id="delivery-otp-input"
              type="text"
              inputMode="numeric"
              maxLength={6}
              value={otp}
              onChange={(e) => {
                setOtp(e.target.value.replace(/\D/g, ''));
                setError(null);
              }}
              placeholder="مثال: ۱۲۳۴۵۶"
              autoFocus
              className="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center font-mono text-2xl tracking-widest text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            />
            {error && (
              <p role="alert" className="mt-2 text-sm font-medium text-rose-600 dark:text-rose-400">
                {error}
              </p>
            )}
          </div>

          <div className="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={() => {
                setError(null);
                setOtp('');
                onClose();
              }}
              className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
            >
              انصراف
            </button>
            <button
              type="submit"
              disabled={isSubmitting || otp.length !== 6}
              className="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
            >
              {isSubmitting ? 'در حال ثبت...' : 'تأیید و ثبت تحویل'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
