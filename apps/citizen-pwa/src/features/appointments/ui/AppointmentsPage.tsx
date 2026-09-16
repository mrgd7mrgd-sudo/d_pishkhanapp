import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Calendar, Plus, RefreshCw, AlertCircle } from 'lucide-react';
import { Button } from '@pishkhan/ui-kit';
import { appointmentsApi } from '../api/appointmentsApi';
import { AppointmentCard } from '../components/AppointmentCard';
import { SlotPickerModal } from '../components/SlotPickerModal';
import type { AppointmentItem, BookAppointmentPayload } from '../types';

export const AppointmentsPage: React.FC = () => {
  const [showPicker, setShowPicker] = useState(false);
  const [selectedDate, setSelectedDate] = useState(() => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0] ?? '';
  });
  const [feedback, setFeedback] = useState<string | null>(null);

  // Default office & service for new appointment booking demo
  const [targetOfficeId] = useState('off_default');
  const [targetOfficeName] = useState('دفتر پیشخوان دولت مرکزی');
  const [targetServiceId] = useState('srv_default');

  const queryClient = useQueryClient();

  const { data: appointments = [], isLoading, refetch } = useQuery({
    queryKey: ['citizen', 'appointments'],
    queryFn: () => appointmentsApi.fetchAppointments(),
  });

  const { data: slotsData, isLoading: isLoadingSlots } = useQuery({
    queryKey: ['citizen', 'office-slots', targetOfficeId, selectedDate],
    queryFn: () => appointmentsApi.fetchOfficeSlots(targetOfficeId, selectedDate),
    enabled: showPicker,
  });

  const bookMutation = useMutation({
    mutationFn: (payload: BookAppointmentPayload) => appointmentsApi.bookAppointment(payload),
    onSuccess: (newApp) => {
      queryClient.invalidateQueries({ queryKey: ['citizen', 'appointments'] });
      setShowPicker(false);
      setFeedback(`نوبت شما با موفقیت ثبت شد. شماره نوبت در صف: ${newApp.queue_number}`);
      setTimeout(() => setFeedback(null), 5000);
    },
  });

  const cancelMutation = useMutation({
    mutationFn: (id: string) => appointmentsApi.cancelAppointment(id, 'انصراف شهروند'),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['citizen', 'appointments'] });
      setFeedback('نوبت با موفقیت لغو گردید.');
      setTimeout(() => setFeedback(null), 4000);
    },
  });

  const handleSelectSlot = (slot: string) => {
    bookMutation.mutate({
      office_id: targetOfficeId,
      service_id: targetServiceId,
      appointment_date: selectedDate,
      time_slot: slot,
      reminder_enabled: true,
      reminder_type: 'both',
    });
  };

  return (
    <div className="space-y-5 max-w-2xl mx-auto p-4 sm:p-6" data-testid="appointments-page">
      <header className="flex items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
          <h1 className="text-lg sm:text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <Calendar className="w-5 h-5 text-blue-600" aria-hidden="true" />
            <span>نوبت‌های حضوری من</span>
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            مشاهده، رزرو و مدیریت نوبت‌های باجه در دفاتر پیشخوان دولت
          </p>
        </div>

        <div className="flex items-center gap-2">
          <Button
            variant="secondary"
            size="sm"
            onClick={() => refetch()}
            disabled={isLoading}
            aria-label="به‌روزرسانی لیست نوبت‌ها"
            data-testid="refresh-appointments-btn"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} aria-hidden="true" />
          </Button>
          <Button
            variant="primary"
            size="sm"
            onClick={() => setShowPicker(true)}
            data-testid="new-appointment-btn"
          >
            <Plus className="w-4 h-4 me-1" aria-hidden="true" />
            <span>دریافت نوبت جدید</span>
          </Button>
        </div>
      </header>

      {feedback && (
        <div
          role="status"
          aria-live="polite"
          className="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-semibold"
        >
          {feedback}
        </div>
      )}

      {/* Reminder Notification Banner */}
      <div className="p-3.5 rounded-2xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 text-xs text-blue-800 dark:text-blue-300 flex items-start gap-2.5">
        <AlertCircle className="w-4 h-4 text-blue-600 shrink-0 mt-0.5" aria-hidden="true" />
        <div className="space-y-0.5">
          <span className="font-bold block">یادآوری هوشمند و مدارک همراه:</span>
          <span>
            پیش از ساعت مراجعه، پیامک حاوی شماره باجه و فهرست مدارک الزامی برای شما ارسال می‌گردد.
          </span>
        </div>
      </div>

      {/* Appointments List */}
      <main className="space-y-3">
        {isLoading ? (
          <div className="text-center py-12 text-xs text-slate-500">
            در حال بارگذاری لیست نوبت‌ها...
          </div>
        ) : appointments.length === 0 ? (
          <div className="text-center py-12 bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-6">
            <Calendar className="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" aria-hidden="true" />
            <p className="text-xs text-slate-500">تاکنون نوبت حضوری فعالی رزرو نکرده‌اید.</p>
          </div>
        ) : (
          appointments.map((app: AppointmentItem) => (
            <AppointmentCard
              key={app.id}
              appointment={app}
              onCancel={(id) => cancelMutation.mutate(id)}
              isCancelling={cancelMutation.isPending}
            />
          ))
        )}
      </main>

      {/* Booking Slot Picker Modal */}
      {showPicker && (
        <SlotPickerModal
          officeName={targetOfficeName}
          slotsData={slotsData}
          isLoading={isLoadingSlots}
          selectedDate={selectedDate}
          onDateChange={setSelectedDate}
          onSelectSlot={handleSelectSlot}
          onClose={() => setShowPicker(false)}
          isBooking={bookMutation.isPending}
        />
      )}
    </div>
  );
};
