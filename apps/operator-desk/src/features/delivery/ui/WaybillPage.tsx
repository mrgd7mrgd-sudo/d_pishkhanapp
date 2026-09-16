import React from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { deliveryApi } from '../api/deliveryApi';
import { WaybillPrintView } from '../components/WaybillPrintView';
import type { WaybillData } from '../types';

export const WaybillPage: React.FC = () => {
  const { deliveryId } = useParams<{ deliveryId: string }>();

  const { data: waybill, isLoading, error } = useQuery<WaybillData>({
    queryKey: ['desk', 'waybill', deliveryId],
    queryFn: async () => {
      if (!deliveryId) throw new Error('شناسه مرسوله نامعتبر است.');
      return deliveryApi.fetchWaybill(deliveryId);
    },
    enabled: Boolean(deliveryId),
  });

  if (isLoading) {
    return (
      <div className="flex h-64 items-center justify-center text-sm text-slate-500">
        در حال آماده‌سازی بارنامه جهت چاپ...
      </div>
    );
  }

  if (error || !waybill) {
    return (
      <div className="mx-auto max-w-lg p-6 text-center">
        <div className="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300">
          <h2 className="text-base font-bold">خطا در بارگذاری بارنامه</h2>
          <p className="mt-2 text-xs">
            اطلاعات بارنامه برای این مرسوله یافت نشد یا شما به این دفتر دسترسی ندارید.
          </p>
        </div>
      </div>
    );
  }

  return <WaybillPrintView waybill={waybill} />;
};
