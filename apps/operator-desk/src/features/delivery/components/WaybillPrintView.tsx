import React from 'react';
import { useNavigate } from 'react-router-dom';
import type { WaybillData } from '../types';

interface WaybillPrintViewProps {
  waybill: WaybillData;
}

export const WaybillPrintView: React.FC<WaybillPrintViewProps> = ({ waybill }) => {
  const navigate = useNavigate();

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="mx-auto max-w-2xl p-4">
      {/* Actions (hidden in print) */}
      <div className="mb-4 flex items-center justify-between print:hidden">
        <button
          type="button"
          onClick={() => navigate(-1)}
          className="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
        >
          بازگشت به لیست تحویل
        </button>
        <button
          type="button"
          onClick={handlePrint}
          className="rounded-xl bg-blue-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-500"
        >
          چاپ بارنامه (Print)
        </button>
      </div>

      {/* Printable Document A5 format */}
      <div
        data-testid="waybill-printable"
        className="rounded-2xl border-2 border-slate-900 bg-white p-6 text-slate-900 shadow-lg print:border-2 print:p-8 print:shadow-none"
      >
        <div className="border-b-2 border-slate-900 pb-4 text-center">
          <div className="text-xs font-bold text-slate-500">جمهوری اسلامی ایران</div>
          <h1 className="mt-1 text-base font-extrabold text-slate-900">
            سامانه پیشخوان دولت هوشمند — بارنامه رسمی تحویل مدارک
          </h1>
          <div className="mt-2 text-xs font-medium text-slate-600">
            شناسه رهگیری و تحویل امن مدارک دولتی (OTP Verification)
          </div>
        </div>

        {/* Barcode Section */}
        <div className="my-4 flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
          <div className="font-mono text-2xl tracking-widest font-extrabold">
            ||| | |||| | |||||| || | |||| ||
          </div>
          <div
            data-testid="waybill-barcode"
            className="mt-1 font-mono text-base font-bold text-slate-800"
          >
            {waybill.tracking_barcode}
          </div>
        </div>

        {/* Sender & Receiver Boxes */}
        <div className="grid grid-cols-2 gap-4 text-xs">
          <div className="rounded-xl border border-slate-300 p-3">
            <div className="font-bold text-slate-700">فرستنده (دفتر مبدأ):</div>
            <div className="mt-1 font-medium">{waybill.office_name ?? 'دفتر پیشخوان دولت'}</div>
            <div className="mt-1 text-slate-600">تلفن: {waybill.office_phone ?? '۰۲۱-۸۸۹۰۰۰۰۰'}</div>
            <div className="mt-1 text-slate-600">نشانی: {waybill.office_address ?? 'تهران، میدان ولیعصر'}</div>
          </div>

          <div className="rounded-xl border border-slate-300 p-3">
            <div className="font-bold text-slate-700">گیرنده (شهروند / متقاضی):</div>
            <div className="mt-1 font-medium">{waybill.citizen_name ?? 'شهروند متقاضی'}</div>
            <div className="mt-1 text-slate-600">نشانی: {waybill.destination_address ?? '—'}</div>
            <div className="mt-1 font-mono text-slate-600">
              کد پستی: {waybill.destination_postal_code ?? '—'}
            </div>
          </div>
        </div>

        {/* Spec Table */}
        <div className="mt-4 overflow-hidden rounded-xl border border-slate-300 text-xs">
          <table className="w-full text-right">
            <tbody>
              <tr className="border-b border-slate-200 bg-slate-50">
                <td className="p-2 font-bold">نوع سند:</td>
                <td className="p-2">{waybill.doc_type_label ?? 'سند دولتی'}</td>
                <td className="p-2 font-bold">سریال سند:</td>
                <td className="p-2 font-mono">{waybill.doc_serial_number ?? '—'}</td>
              </tr>
              <tr className="border-b border-slate-200">
                <td className="p-2 font-bold">نوع ارسال:</td>
                <td className="p-2">{waybill.courier_type_label ?? 'پیک اکسپرس'}</td>
                <td className="p-2 font-bold">روش پرداخت:</td>
                <td className="p-2">{waybill.payment_method_label ?? 'کیف پول دفتر'}</td>
              </tr>
              <tr className="bg-slate-50">
                <td className="p-2 font-bold">پلمب امنیتی:</td>
                <td className="p-2">{waybill.is_sealed_pack ? 'دارد (مهر و موم)' : 'خیر'}</td>
                <td className="p-2 font-bold">استرداد مدرک کهنه:</td>
                <td className="p-2">{waybill.require_old_doc_return ? 'الزامی است' : 'خیر'}</td>
              </tr>
            </tbody>
          </table>
        </div>

        {/* Notice & Security */}
        <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
          <span className="font-bold">تذکر امنیتی مهم به سفیر تحویل:</span> تحویل این مرسوله منوط به
          دریافت کد ۶ رقمی OTP از متقاضی است. بدون ثبت کد در سامانه، تحویل مدارک فاقد اعتبار قانونی می‌باشد.
        </div>

        <div className="mt-6 flex items-center justify-between border-t border-slate-200 pt-4 text-xs text-slate-500">
          <div>تاریخ صدور: {waybill.created_at ?? '۱۴۰۵/۰۶/۲۵'}</div>
          <div>مهر و امضای تحویل‌دهنده دفتر پیشخوان</div>
        </div>
      </div>
    </div>
  );
};
