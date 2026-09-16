import React from 'react';
import { Calendar, Clock, MapPin, Ticket, ShieldAlert } from 'lucide-react';
import { Badge, Button } from '@pishkhan/ui-kit';
import type { AppointmentItem } from '../types';

export interface AppointmentCardProps {
  appointment: AppointmentItem;
  onCancel?: ((id: string) => void) | undefined;
  isCancelling?: boolean | undefined;
}

export const AppointmentCard: React.FC<AppointmentCardProps> = ({
  appointment,
  onCancel,
  isCancelling,
}) => {
  const isScheduled = appointment.status === 'scheduled';

  return (
    <article
      data-testid={`appointment-card-${appointment.id}`}
      className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3"
    >
      <div className="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
        <div>
          <h2 className="text-sm font-bold text-slate-900 dark:text-white">
            {appointment.service_title || 'خدمت حضوری پیشخوان'}
          </h2>
          <p className="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
            <MapPin className="w-3 h-3 text-blue-600" aria-hidden="true" />
            <span>{appointment.office_name || 'دفتر پیشخوان دولت'}</span>
            {appointment.office_code && (
              <span className="font-mono text-[11px] text-slate-400">({appointment.office_code})</span>
            )}
          </p>
        </div>

        <div>
          {appointment.status === 'scheduled' && (
            <Badge variant="brand">نوبت معتبر</Badge>
          )}
          {appointment.status === 'attended' && (
            <Badge variant="success">مراجعه انجام شد</Badge>
          )}
          {appointment.status === 'cancelled' && (
            <Badge variant="danger">لغو شده</Badge>
          )}
          {appointment.status === 'expired' && (
            <Badge variant="warning">منقضی</Badge>
          )}
        </div>
      </div>

      {/* Ticket & Queue Information */}
      <div className="grid grid-cols-2 gap-2 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-xl text-center">
        <div className="border-e border-slate-200 dark:border-slate-700">
          <span className="text-[10px] text-slate-500 block mb-0.5">شماره نوبت در صف</span>
          <span className="text-base font-black font-mono text-blue-600 dark:text-blue-400 flex items-center justify-center gap-1">
            <Ticket className="w-4 h-4" aria-hidden="true" />
            <span>{appointment.queue_number}</span>
          </span>
        </div>
        <div>
          <span className="text-[10px] text-slate-500 block mb-0.5">شماره باجه پاسخگویی</span>
          <span className="text-base font-bold font-mono text-slate-800 dark:text-slate-200">
            باجه {appointment.counter_number}
          </span>
        </div>
      </div>

      {/* Date and Time Slot */}
      <div className="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300 px-1">
        <span className="flex items-center gap-1.5">
          <Calendar className="w-3.5 h-3.5 text-slate-400" aria-hidden="true" />
          <span>تاریخ: {appointment.appointment_date}</span>
        </span>
        <span className="flex items-center gap-1.5 font-mono">
          <Clock className="w-3.5 h-3.5 text-slate-400" aria-hidden="true" />
          <span>بازه {appointment.time_slot}</span>
        </span>
      </div>

      {appointment.office_address && (
        <p className="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">
          {appointment.office_address}
        </p>
      )}

      {/* Action footer */}
      {isScheduled && onCancel && (
        <div className="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-end">
          <Button
            variant="secondary"
            size="sm"
            onClick={() => onCancel(appointment.id)}
            disabled={isCancelling}
            data-testid={`cancel-appointment-btn-${appointment.id}`}
          >
            <ShieldAlert className="w-3.5 h-3.5 me-1 text-rose-500" aria-hidden="true" />
            <span>انصراف و لغو نوبت</span>
          </Button>
        </div>
      )}
    </article>
  );
};
