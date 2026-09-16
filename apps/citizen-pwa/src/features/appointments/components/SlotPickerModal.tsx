import React, { useState } from 'react';
import { Calendar, Clock, AlertCircle } from 'lucide-react';
import { Button } from '@pishkhan/ui-kit';
import type { OfficeSlotsResponse } from '../types';

export interface SlotPickerModalProps {
  officeName: string;
  slotsData?: OfficeSlotsResponse | undefined;
  isLoading: boolean;
  selectedDate: string;
  onDateChange: (date: string) => void;
  onSelectSlot: (slot: string) => void;
  onClose: () => void;
  isBooking: boolean;
}

export const SlotPickerModal: React.FC<SlotPickerModalProps> = ({
  officeName,
  slotsData,
  isLoading,
  selectedDate,
  onDateChange,
  onSelectSlot,
  onClose,
  isBooking,
}) => {
  const [chosenSlot, setChosenSlot] = useState<string | null>(null);

  const handleConfirm = () => {
    if (chosenSlot) {
      onSelectSlot(chosenSlot);
    }
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="slot-picker-title"
    >
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl w-full max-w-md p-5 space-y-4">
        <div>
          <h2 id="slot-picker-title" className="text-base font-bold text-slate-900 dark:text-white">
            رزرو نوبت حضوری در {officeName}
          </h2>
          <p className="text-xs text-slate-500 mt-1">
            لطفاً تاریخ و بازه زمانی مراجعه به باجه را انتخاب نمایید.
          </p>
        </div>

        {/* Date Selector */}
        <div>
          <label htmlFor="appointment-date-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            تاریخ مراجعه
          </label>
          <div className="relative">
            <Calendar className="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true" />
            <input
              id="appointment-date-input"
              type="date"
              value={selectedDate}
              onChange={(e) => onDateChange(e.target.value)}
              className="w-full text-xs ps-9 pe-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:outline-hidden"
            />
          </div>
        </div>

        {/* Slots Grid */}
        <div>
          <span className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
            بازه‌های زمانی آزاد باجه
          </span>

          {isLoading ? (
            <div className="text-center py-8 text-xs text-slate-500">
              در حال دریافت ظرفیت باجه‌ها...
            </div>
          ) : !slotsData || !slotsData.is_open ? (
            <div className="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 text-xs flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" aria-hidden="true" />
              <span>دفتر در تاریخ انتخاب‌شده تعطیل بوده یا امکان پذیرش حضوری ندارد.</span>
            </div>
          ) : slotsData.slots.length === 0 ? (
            <div className="text-center py-6 text-xs text-slate-500">
              هیچ بازه نوبت‌دهی فعالی تعریف نشده است.
            </div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-56 overflow-y-auto p-1">
              {slotsData.slots.map((s) => {
                const isSelected = chosenSlot === s.time_slot;
                const isFull = !s.is_available;

                return (
                  <button
                    key={s.time_slot}
                    type="button"
                    disabled={isFull || isBooking}
                    data-testid={`slot-btn-${s.time_slot}`}
                    onClick={() => setChosenSlot(s.time_slot)}
                    className={`p-2.5 rounded-xl border text-xs font-mono transition-all flex flex-col items-center gap-0.5 ${
                      isSelected
                        ? 'border-blue-600 bg-blue-600 text-white font-bold shadow-xs'
                        : isFull
                        ? 'border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 text-slate-400 cursor-not-allowed opacity-50'
                        : 'border-slate-200 dark:border-slate-700 hover:border-blue-400 text-slate-800 dark:text-slate-200'
                    }`}
                  >
                    <span className="flex items-center gap-1 text-xs">
                      <Clock className="w-3 h-3" aria-hidden="true" />
                      <span>{s.time_slot}</span>
                    </span>
                    <span className="text-[10px] opacity-80">
                      {isFull ? 'تکمیل ظرفیت' : `${s.capacity - s.booked_count} ظرفیت`}
                    </span>
                  </button>
                );
              })}
            </div>
          )}
        </div>

        {/* Buttons */}
        <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
          <Button variant="secondary" size="sm" type="button" onClick={onClose} disabled={isBooking}>
            انصراف
          </Button>
          <Button
            variant="primary"
            size="sm"
            type="button"
            onClick={handleConfirm}
            disabled={!chosenSlot || isBooking}
            data-testid="confirm-booking-btn"
          >
            {isBooking ? 'در حال رزرو...' : 'تأیید و دریافت نوبت'}
          </Button>
        </div>
      </div>
    </div>
  );
};
