import React, { useState } from 'react';
import type { AssignCourierPayload, DeliveryItem } from '../types';

interface AssignCourierModalProps {
  delivery: DeliveryItem | null;
  isOpen: boolean;
  onClose: () => void;
  onAssign: (deliveryId: string, payload: AssignCourierPayload) => Promise<void>;
  isSubmitting?: boolean;
}

export const AssignCourierModal: React.FC<AssignCourierModalProps> = ({
  delivery,
  isOpen,
  onClose,
  onAssign,
  isSubmitting = false,
}) => {
  const [courierName, setCourierName] = useState('');
  const [courierPhone, setCourierPhone] = useState('');
  const [courierPlate, setCourierPlate] = useState('');
  const [error, setError] = useState<string | null>(null);

  if (!isOpen || !delivery) {
    return null;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!courierName.trim()) {
      setError('نام سفیر / پیک الزامی است.');
      return;
    }
    if (!courierPhone.trim() || !/^09\d{9}$/.test(courierPhone)) {
      setError('شماره موبایل معتبر ۱۱ رقمی سفیر الزامی است.');
      return;
    }

    try {
      setError(null);
      await onAssign(delivery.id, {
        courier_name: courierName.trim(),
        courier_phone: courierPhone.trim(),
        courier_plate: courierPlate.trim() || undefined,
      });
      onClose();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'خطا در تخصیص سفیر');
    }
  };

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="courier-modal-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
        <h2 id="courier-modal-title" className="text-lg font-bold text-slate-900 dark:text-white">
          تخصیص سفیر به مرسوله
        </h2>
        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
          مشخصات پیک یا سفیر تحویل‌گیرنده را ثبت نمایید.
        </p>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          <div>
            <label htmlFor="courier-name-input" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              نام و نام خانوادگی سفیر *
            </label>
            <input
              id="courier-name-input"
              type="text"
              value={courierName}
              onChange={(e) => setCourierName(e.target.value)}
              placeholder="مثال: علی محمدی"
              className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            />
          </div>

          <div>
            <label htmlFor="courier-phone-input" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              شماره موبایل سفیر *
            </label>
            <input
              id="courier-phone-input"
              type="tel"
              value={courierPhone}
              onChange={(e) => setCourierPhone(e.target.value)}
              placeholder="۰۹۱۲۳۴۵۶۷۸۹"
              dir="ltr"
              className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            />
          </div>

          <div>
            <label htmlFor="courier-plate-input" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              پلاک موتورسیکلت / خودرو (اختیاری)
            </label>
            <input
              id="courier-plate-input"
              type="text"
              value={courierPlate}
              onChange={(e) => setCourierPlate(e.target.value)}
              placeholder="مثال: ۱۲۳ - ۴۵ ب ۶۷"
              className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            />
          </div>

          {error && (
            <p role="alert" className="text-sm font-medium text-rose-600 dark:text-rose-400">
              {error}
            </p>
          )}

          <div className="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
            >
              انصراف
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              className="rounded-xl bg-blue-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
            >
              {isSubmitting ? 'در حال ثبت...' : 'ثبت و تخصیص'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
