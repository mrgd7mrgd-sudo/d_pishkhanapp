import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  DELIVERY_STATUSES,
  getCourierTypeMeta,
  getDeliveryDocTypeMeta,
  getDeliveryStatusMeta,
} from '@pishkhan/domain';
import type { DeliveryItem } from '../types';

interface ActiveDeliveriesListProps {
  deliveries: DeliveryItem[];
  onOpenAssignCourier: (delivery: DeliveryItem) => void;
  onOpenOtpConfirm: (delivery: DeliveryItem) => void;
  onMarkInTransit: (deliveryId: string) => Promise<void>;
  onMarkFailed: (deliveryId: string) => Promise<void>;
  isLoading?: boolean;
}

export const ActiveDeliveriesList: React.FC<ActiveDeliveriesListProps> = ({
  deliveries,
  onOpenAssignCourier,
  onOpenOtpConfirm,
  onMarkInTransit,
  onMarkFailed,
  isLoading = false,
}) => {
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [searchTerm, setSearchTerm] = useState('');

  const filtered = deliveries.filter((item) => {
    if (statusFilter !== 'all' && item.delivery_status !== statusFilter) {
      return false;
    }
    if (searchTerm.trim()) {
      const q = searchTerm.toLowerCase();
      const matchBarcode = item.tracking_barcode?.toLowerCase().includes(q) ?? false;
      const matchAddress = item.destination_address.toLowerCase().includes(q);
      const matchCourier = item.courier_name?.toLowerCase().includes(q) ?? false;
      return matchBarcode || matchAddress || matchCourier;
    }
    return true;
  });

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex flex-wrap items-center gap-1.5">
          <button
            type="button"
            onClick={() => setStatusFilter('all')}
            className={`rounded-lg px-3 py-1.5 text-xs font-medium transition-colors ${
              statusFilter === 'all'
                ? 'bg-blue-600 text-white'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'
            }`}
          >
            همه ({deliveries.length})
          </button>
          {DELIVERY_STATUSES.map((st) => {
            const meta = getDeliveryStatusMeta(st);
            const count = deliveries.filter((d) => d.delivery_status === st).length;
            return (
              <button
                key={st}
                type="button"
                onClick={() => setStatusFilter(st)}
                className={`rounded-lg px-3 py-1.5 text-xs font-medium transition-colors ${
                  statusFilter === st
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'
                }`}
              >
                {meta.label} ({count})
              </button>
            );
          })}
        </div>

        <div className="w-full sm:w-64">
          <input
            type="search"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="جستجوی بارکد، نشانی، سفیر..."
            className="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="p-8 text-center text-sm text-slate-400">در حال بارگذاری مرسوله‌ها...</div>
      ) : filtered.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
          مرسوله‌ای مطابق فیلترهای انتخابی یافت نشد.
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-3">
          {filtered.map((item) => {
            const statusMeta = getDeliveryStatusMeta(item.delivery_status);
            const docMeta = getDeliveryDocTypeMeta(item.doc_type);
            const courierMeta = getCourierTypeMeta(item.courier_type);

            return (
              <div
                key={item.id}
                data-testid={`delivery-item-${item.id}`}
                className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900"
              >
                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-sm font-bold text-slate-900 dark:text-white">
                      {item.tracking_barcode ?? `DEL-${item.id.slice(0, 8)}`}
                    </span>
                    <span className="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                      {docMeta.label}
                    </span>
                    <span className="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                      {courierMeta.label}
                    </span>
                  </div>

                  <span
                    data-testid={`status-badge-${item.delivery_status}`}
                    className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                      item.delivery_status === 'delivered'
                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                        : item.delivery_status === 'in_transit'
                          ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'
                          : item.delivery_status === 'courier_assigned'
                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300'
                            : item.delivery_status === 'failed'
                              ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300'
                              : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300'
                    }`}
                  >
                    {statusMeta.label}
                  </span>
                </div>

                <div className="mt-3 grid grid-cols-1 gap-2 text-xs text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                  <div>
                    <span className="font-medium text-slate-400">نشانی مقصد:</span> {item.destination_address}
                  </div>
                  <div>
                    <span className="font-medium text-slate-400">کد پستی:</span>{' '}
                    <span className="font-mono">{item.destination_postal_code}</span>
                  </div>
                  {item.courier_name && (
                    <div>
                      <span className="font-medium text-slate-400">سفیر:</span> {item.courier_name} (
                      <span dir="ltr">{item.courier_phone}</span>)
                    </div>
                  )}
                  {item.doc_serial_number && (
                    <div>
                      <span className="font-medium text-slate-400">سریال سند:</span>{' '}
                      <span className="font-mono">{item.doc_serial_number}</span>
                    </div>
                  )}
                </div>

                <div className="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                  <div className="flex items-center gap-2">
                    {item.delivery_status === 'ready_for_dispatch' && (
                      <button
                        type="button"
                        onClick={() => onOpenAssignCourier(item)}
                        className="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-500"
                      >
                        تخصیص سفیر
                      </button>
                    )}
                    {item.delivery_status === 'courier_assigned' && (
                      <button
                        type="button"
                        onClick={() => onMarkInTransit(item.id)}
                        className="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-500"
                      >
                        آغاز ارسال
                      </button>
                    )}
                    {item.delivery_status === 'in_transit' && (
                      <>
                        <button
                          type="button"
                          onClick={() => onOpenOtpConfirm(item)}
                          className="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-500"
                        >
                          تأیید تحویل با OTP
                        </button>
                        <button
                          type="button"
                          onClick={() => onMarkFailed(item.id)}
                          className="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400"
                        >
                          ثبت عدم تحویل
                        </button>
                      </>
                    )}
                  </div>

                  <Link
                    to={`/delivery/${item.id}/waybill`}
                    className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                  >
                    چاپ بارنامه
                  </Link>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};
