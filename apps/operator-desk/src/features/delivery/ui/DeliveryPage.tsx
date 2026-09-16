import React, { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { deliveryApi } from '../api/deliveryApi';
import { ActiveDeliveriesList } from '../components/ActiveDeliveriesList';
import { AssignCourierModal } from '../components/AssignCourierModal';
import { NewCourierRequestForm } from '../components/NewCourierRequestForm';
import { OtpConfirmModal } from '../components/OtpConfirmModal';
import { PostalBarcodesList } from '../components/PostalBarcodesList';
import type {
  AssignCourierPayload,
  CreateDeliveryPayload,
  DeliveryItem,
  DeliverySubTab,
} from '../types';

export const DeliveryPage: React.FC = () => {
  const [activeTab, setActiveTab] = useState<DeliverySubTab>('active_deliveries');
  const [selectedForOtp, setSelectedForOtp] = useState<DeliveryItem | null>(null);
  const [selectedForCourier, setSelectedForCourier] = useState<DeliveryItem | null>(null);
  const [feedbackMessage, setFeedbackMessage] = useState<string | null>(null);

  const queryClient = useQueryClient();

  const { data: deliveries = [], isLoading: isLoadingDeliveries } = useQuery({
    queryKey: ['desk', 'deliveries'],
    queryFn: () => deliveryApi.fetchDeliveries(),
  });

  const { data: readyCases = [] } = useQuery({
    queryKey: ['desk', 'ready-cases'],
    queryFn: () => deliveryApi.fetchReadyCases(),
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreateDeliveryPayload) => deliveryApi.createDelivery(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'deliveries'] });
      queryClient.invalidateQueries({ queryKey: ['desk', 'ready-cases'] });
      setFeedbackMessage('درخواست تحویل با موفقیت ثبت شد.');
      setActiveTab('active_deliveries');
      setTimeout(() => setFeedbackMessage(null), 4000);
    },
  });

  const assignCourierMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: AssignCourierPayload }) =>
      deliveryApi.assignCourier(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'deliveries'] });
      setFeedbackMessage('سفیر تحویل با موفقیت تخصیص یافت.');
      setSelectedForCourier(null);
      setTimeout(() => setFeedbackMessage(null), 4000);
    },
  });

  const inTransitMutation = useMutation({
    mutationFn: (id: string) => deliveryApi.markInTransit(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'deliveries'] });
      setFeedbackMessage('وضعیت مرسوله به «در مسیر ارسال» تغییر یافت.');
      setTimeout(() => setFeedbackMessage(null), 4000);
    },
  });

  const confirmOtpMutation = useMutation({
    mutationFn: ({ id, otp }: { id: string; otp: string }) => deliveryApi.confirmDelivery(id, otp),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'deliveries'] });
      setFeedbackMessage('مرسوله تحویل داده شد و پرونده با موفقیت تکمیل گردید.');
      setSelectedForOtp(null);
      setTimeout(() => setFeedbackMessage(null), 4000);
    },
  });

  const failMutation = useMutation({
    mutationFn: (id: string) => deliveryApi.markFailed(id, 'عدم حضور متقاضی در محل'),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'deliveries'] });
      setFeedbackMessage('مرسوله به عنوان تحویل ناموفق ثبت و پرونده به دفتر بازگشت.');
      setTimeout(() => setFeedbackMessage(null), 4000);
    },
  });

  const postalCount = deliveries.filter(
    (d) => d.courier_type === 'special_post' || d.courier_type === 'registered_post'
  ).length;

  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4">
      {/* Header & Subtabs */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-xl font-bold text-slate-900 dark:text-white">
            مدیریت پیک و تحویل مدارک
          </h1>
          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
            صدور حواله، رهگیری مرسولات شهری و پستی، تأیید امن تحویل با OTP.
          </p>
        </div>

        <div className="flex items-center gap-1 rounded-2xl bg-slate-100 p-1 dark:bg-slate-800">
          <button
            type="button"
            data-testid="tab-active-deliveries"
            onClick={() => setActiveTab('active_deliveries')}
            className={`rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all ${
              activeTab === 'active_deliveries'
                ? 'bg-white text-blue-600 shadow-sm dark:bg-slate-900 dark:text-white'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
            }`}
          >
            مرسولات فعال ({deliveries.length})
          </button>
          <button
            type="button"
            data-testid="tab-new-courier"
            onClick={() => setActiveTab('new_courier_request')}
            className={`rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all ${
              activeTab === 'new_courier_request'
                ? 'bg-white text-blue-600 shadow-sm dark:bg-slate-900 dark:text-white'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
            }`}
          >
            درخواست پیک جدید ({readyCases.length})
          </button>
          <button
            type="button"
            data-testid="tab-postal-barcodes"
            onClick={() => setActiveTab('postal_barcodes')}
            className={`rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all ${
              activeTab === 'postal_barcodes'
                ? 'bg-white text-blue-600 shadow-sm dark:bg-slate-900 dark:text-white'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
            }`}
          >
            بارکدهای پستی ({postalCount})
          </button>
        </div>
      </div>

      {feedbackMessage && (
        <div
          role="status"
          className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300"
        >
          {feedbackMessage}
        </div>
      )}

      {/* Tab Panels */}
      {activeTab === 'active_deliveries' && (
        <ActiveDeliveriesList
          deliveries={deliveries}
          isLoading={isLoadingDeliveries}
          onOpenAssignCourier={(d) => setSelectedForCourier(d)}
          onOpenOtpConfirm={(d) => setSelectedForOtp(d)}
          onMarkInTransit={async (id) => {
            await inTransitMutation.mutateAsync(id);
          }}
          onMarkFailed={async (id) => {
            await failMutation.mutateAsync(id);
          }}
        />
      )}

      {activeTab === 'new_courier_request' && (
        <NewCourierRequestForm
          readyCases={readyCases}
          isSubmitting={createMutation.isPending}
          onSubmit={async (payload) => {
            await createMutation.mutateAsync(payload);
          }}
        />
      )}

      {activeTab === 'postal_barcodes' && (
        <PostalBarcodesList deliveries={deliveries} isLoading={isLoadingDeliveries} />
      )}

      {/* Modals */}
      <AssignCourierModal
        delivery={selectedForCourier}
        isOpen={selectedForCourier !== null}
        onClose={() => setSelectedForCourier(null)}
        isSubmitting={assignCourierMutation.isPending}
        onAssign={async (id, payload) => {
          await assignCourierMutation.mutateAsync({ id, payload });
        }}
      />

      <OtpConfirmModal
        delivery={selectedForOtp}
        isOpen={selectedForOtp !== null}
        onClose={() => setSelectedForOtp(null)}
        isSubmitting={confirmOtpMutation.isPending}
        onConfirm={async (id, otp) => {
          await confirmOtpMutation.mutateAsync({ id, otp });
        }}
      />
    </div>
  );
};
