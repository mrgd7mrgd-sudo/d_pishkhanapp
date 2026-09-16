import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { getCourierTypeMeta, getDeliveryStatusMeta } from '@pishkhan/domain';
import type { DeliveryItem } from '../types';

interface PostalBarcodesListProps {
  deliveries: DeliveryItem[];
  isLoading?: boolean;
}

export const PostalBarcodesList: React.FC<PostalBarcodesListProps> = ({ deliveries, isLoading = false }) => {
  const [copiedId, setCopiedId] = useState<string | null>(null);

  const postalDeliveries = deliveries.filter(
    (d) => d.courier_type === 'special_post' || d.courier_type === 'registered_post'
  );

  const handleCopyBarcode = (barcode: string, id: string) => {
    navigator.clipboard.writeText(barcode);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  return (
    <div className="space-y-4">
      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 className="text-sm font-bold text-slate-900 dark:text-white">
          بارنامه‌ها و بارکدهای پستی شرکت ملی پست
        </h3>
        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
          مرسولات پست پیشتاز و سفارشی با بارکد ۲۴ رقمی استاندارد برای رهگیری سراسری.
        </p>
      </div>

      {isLoading ? (
        <div className="p-8 text-center text-sm text-slate-400">در حال بارگذاری بارکدها...</div>
      ) : postalDeliveries.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
          هیچ مرسوله پستی ثبت نشده است.
        </div>
      ) : (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <table className="w-full text-right text-xs">
            <thead className="border-b border-slate-100 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
              <tr>
                <th className="p-3">بارکد رهگیری پست</th>
                <th className="p-3">نوع پست</th>
                <th className="p-3">مقصد</th>
                <th className="p-3">کد پستی</th>
                <th className="p-3">وضعیت</th>
                <th className="p-3 text-center">عملیات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {postalDeliveries.map((item) => {
                const statusMeta = getDeliveryStatusMeta(item.delivery_status);
                const courierMeta = getCourierTypeMeta(item.courier_type);
                const barcode = item.tracking_barcode ?? `POST-${item.id.slice(0, 12)}`;

                return (
                  <tr key={item.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                    <td className="p-3 font-mono font-bold text-slate-900 dark:text-white">
                      {barcode}
                    </td>
                    <td className="p-3">{courierMeta.label}</td>
                    <td className="max-w-xs truncate p-3 text-slate-600 dark:text-slate-300">
                      {item.destination_address}
                    </td>
                    <td className="p-3 font-mono text-slate-600 dark:text-slate-300">
                      {item.destination_postal_code}
                    </td>
                    <td className="p-3">
                      <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {statusMeta.label}
                      </span>
                    </td>
                    <td className="p-3 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <button
                          type="button"
                          onClick={() => handleCopyBarcode(barcode, item.id)}
                          className="rounded border border-slate-200 px-2 py-1 text-xs text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                          {copiedId === item.id ? 'کپی شد!' : 'کپی بارکد'}
                        </button>
                        <Link
                          to={`/delivery/${item.id}/waybill`}
                          className="rounded bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-400"
                        >
                          چاپ
                        </Link>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};
