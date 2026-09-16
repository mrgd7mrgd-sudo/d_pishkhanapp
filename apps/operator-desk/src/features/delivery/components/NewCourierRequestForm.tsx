import React, { useState } from 'react';
import {
  COURIER_TYPES,
  DELIVERY_DOC_TYPES,
  DELIVERY_PAYMENT_METHODS,
  getCourierTypeMeta,
  getDeliveryDocTypeMeta,
  getDeliveryPaymentMethodMeta,
} from '@pishkhan/domain';
import type {
  CourierType,
  DeliveryDocType,
  DeliveryPaymentMethod,
} from '@pishkhan/domain';
import type { CreateDeliveryPayload, ReadyCase } from '../types';

interface NewCourierRequestFormProps {
  readyCases: ReadyCase[];
  onSubmit: (payload: CreateDeliveryPayload) => Promise<void>;
  isSubmitting?: boolean;
}

export const NewCourierRequestForm: React.FC<NewCourierRequestFormProps> = ({
  readyCases,
  onSubmit,
  isSubmitting = false,
}) => {
  const [selectedCaseId, setSelectedCaseId] = useState('');
  const [docType, setDocType] = useState<DeliveryDocType>('smart_card');
  const [docSerial, setDocSerial] = useState('');
  const [destAddress, setDestAddress] = useState('');
  const [destPostalCode, setDestPostalCode] = useState('');
  const [courierType, setCourierType] = useState<CourierType>('express_courier');
  const [paymentMethod, setPaymentMethod] = useState<DeliveryPaymentMethod>('office_wallet');
  const [shippingFee, setShippingFee] = useState<number>(350000);
  const [requireOldDoc, setRequireOldDoc] = useState(false);
  const [isSealed, setIsSealed] = useState(true);
  const [securityNote, setSecurityNote] = useState('');
  const [error, setError] = useState<string | null>(null);

  const handleCaseChange = (caseId: string) => {
    setSelectedCaseId(caseId);
    const found = readyCases.find((c) => c.id === caseId);
    if (found) {
      if (found.address) setDestAddress(found.address);
      if (found.postal_code) setDestPostalCode(found.postal_code);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedCaseId) {
      setError('لطفاً یک پرونده آماده صدور انتخاب کنید.');
      return;
    }
    if (!destAddress.trim()) {
      setError('نشانی پستی مقصد الزامی است.');
      return;
    }
    if (!/^\d{10}$/.test(destPostalCode.trim())) {
      setError('کد پستی مقصد باید دقیقاً ۱۰ رقم باشد.');
      return;
    }

    try {
      setError(null);
      await onSubmit({
        case_id: selectedCaseId,
        doc_type: docType,
        doc_serial_number: docSerial.trim() || undefined,
        destination_address: destAddress.trim(),
        destination_postal_code: destPostalCode.trim(),
        courier_type: courierType,
        payment_method: paymentMethod,
        shipping_fee_rials: shippingFee,
        require_old_doc_return: requireOldDoc,
        is_sealed_pack: isSealed,
        security_note: securityNote.trim() || undefined,
      });
      setSelectedCaseId('');
      setDestAddress('');
      setDestPostalCode('');
      setDocSerial('');
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'خطا در ثبت درخواست تحویل');
    }
  };

  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <h2 className="text-lg font-bold text-slate-900 dark:text-white">صدور حواله و درخواست پیک / پست جدید</h2>
      <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
        اطلاعات پرونده را انتخاب و نوع ارسال مدرک را مشخص کنید.
      </p>

      <form onSubmit={handleSubmit} className="mt-6 space-y-6">
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label htmlFor="select-ready-case" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              پرونده آماده تحویل *
            </label>
            <select
              id="select-ready-case"
              value={selectedCaseId}
              onChange={(e) => handleCaseChange(e.target.value)}
              className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            >
              <option value="">-- انتخاب پرونده ({readyCases.length} پرونده آماده) --</option>
              {readyCases.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.tracking_code} - {c.service_title ?? 'خدمت'} ({c.citizen_name ?? 'شهروند'})
                </option>
              ))}
            </select>
          </div>

          <div>
            <label htmlFor="select-doc-type" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              نوع سند تحویلی *
            </label>
            <select
              id="select-doc-type"
              value={docType}
              onChange={(e) => setDocType(e.target.value as DeliveryDocType)}
              className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            >
              {DELIVERY_DOC_TYPES.map((dt) => (
                <option key={dt} value={dt}>
                  {getDeliveryDocTypeMeta(dt).label}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label htmlFor="destination-address-input" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              نشانی مقصد تحویل *
            </label>
            <textarea
              id="destination-address-input"
              rows={2}
              value={destAddress}
              onChange={(e) => setDestAddress(e.target.value)}
              placeholder="تهران، میدان ونک، خیابان ولیعصر..."
              className="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            />
          </div>

          <div className="space-y-4">
            <div>
              <label htmlFor="postal-code-input" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
                کد پستی مقصد (۱۰ رقم) *
              </label>
              <input
                id="postal-code-input"
                type="text"
                maxLength={10}
                value={destPostalCode}
                onChange={(e) => setDestPostalCode(e.target.value.replace(/\D/g, ''))}
                placeholder="۱۲۳۴۵۶۷۸۹۰"
                dir="ltr"
                className="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
              />
            </div>
            <div>
              <label htmlFor="doc-serial-input" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
                شماره سریال فیزیکی سند (اختیاری)
              </label>
              <input
                id="doc-serial-input"
                type="text"
                value={docSerial}
                onChange={(e) => setDocSerial(e.target.value)}
                placeholder="مثال: A/1405-9981"
                className="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
              />
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <label htmlFor="courier-type-select" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              روش ارسال *
            </label>
            <select
              id="courier-type-select"
              value={courierType}
              onChange={(e) => setCourierType(e.target.value as CourierType)}
              className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            >
              {COURIER_TYPES.map((ct) => (
                <option key={ct} value={ct}>
                  {getCourierTypeMeta(ct).label}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label htmlFor="payment-method-select" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              روش پرداخت هزینه ارسال *
            </label>
            <select
              id="payment-method-select"
              value={paymentMethod}
              onChange={(e) => setPaymentMethod(e.target.value as DeliveryPaymentMethod)}
              className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            >
              {DELIVERY_PAYMENT_METHODS.map((pm) => (
                <option key={pm} value={pm}>
                  {getDeliveryPaymentMethodMeta(pm).label}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label htmlFor="shipping-fee-input" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
              کرایه ارسال (ریال)
            </label>
            <input
              id="shipping-fee-input"
              type="number"
              value={shippingFee}
              onChange={(e) => setShippingFee(Number(e.target.value))}
              className="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
            />
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-6 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
          <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
            <input
              type="checkbox"
              checked={isSealed}
              onChange={(e) => setIsSealed(e.target.checked)}
              className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            />
            پاکت پلمب امنیتی ضدجعل
          </label>
          <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
            <input
              type="checkbox"
              checked={requireOldDoc}
              onChange={(e) => setRequireOldDoc(e.target.checked)}
              className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            />
            الزام دریافت لاشه سند قدیمی از شهروند
          </label>
        </div>

        {error && (
          <p role="alert" className="text-sm font-medium text-rose-600 dark:text-rose-400">
            {error}
          </p>
        )}

        <div className="flex justify-end pt-2">
          <button
            type="submit"
            disabled={isSubmitting}
            className="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
          >
            {isSubmitting ? 'در حال صدور...' : 'ثبت و صدور بارنامه تحویل'}
          </button>
        </div>
      </form>
    </div>
  );
};
