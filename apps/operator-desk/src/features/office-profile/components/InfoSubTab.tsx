import React, { useState } from 'react';
import { Button } from '@pishkhan/ui-kit';
import { Building, Phone, MapPin, Layers, Save } from 'lucide-react';
import type { OfficeProfileData, UpdateOfficeInfoPayload } from '../types';

export interface InfoSubTabProps {
  office: OfficeProfileData;
  onUpdate: (payload: UpdateOfficeInfoPayload) => Promise<void>;
  isUpdating: boolean;
}

export const InfoSubTab: React.FC<InfoSubTabProps> = ({ office, onUpdate, isUpdating }) => {
  const [phone, setPhone] = useState(office.phone ?? '');
  const [address, setAddress] = useState(office.address ?? '');
  const [activeCounters, setActiveCounters] = useState(office.active_counters);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSuccessMsg(null);
    await onUpdate({
      phone: phone || undefined,
      address: address || undefined,
      active_counters: Number(activeCounters),
    });
    setSuccessMsg('اطلاعات پایه دفتر با موفقیت ذخیره شد.');
    setTimeout(() => setSuccessMsg(null), 4000);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5" aria-labelledby="info-subtab-title">
      <h2 id="info-subtab-title" className="sr-only">اطلاعات عمومی و مشخصات دفتر</h2>

      {successMsg && (
        <div role="status" aria-live="polite" className="p-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs">
          {successMsg}
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-3">
          <h3 className="text-xs font-bold text-slate-500 flex items-center gap-1.5">
            <Building className="w-4 h-4 text-blue-600" aria-hidden="true" />
            <span>مشخصات سازمانی (غیرقابل ویرایش)</span>
          </h3>
          <div className="space-y-2 text-xs">
            <div className="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
              <span className="text-slate-500">نام رسمی دفتر:</span>
              <span className="font-semibold text-slate-800 dark:text-slate-200">{office.name}</span>
            </div>
            <div className="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
              <span className="text-slate-500">کد رهگیری پیشخوان:</span>
              <span className="font-mono font-bold text-slate-800 dark:text-slate-200">{office.code}</span>
            </div>
            <div className="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
              <span className="text-slate-500">مدیر مسئول:</span>
              <span className="font-semibold text-slate-800 dark:text-slate-200">{office.manager_name}</span>
            </div>
            <div className="flex justify-between py-1">
              <span className="text-slate-500">وضعیت عضویت:</span>
              <span className="text-emerald-600 font-semibold">{office.membership_status}</span>
            </div>
          </div>
        </div>

        <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-3">
          <h3 className="text-xs font-bold text-slate-500 flex items-center gap-1.5">
            <Layers className="w-4 h-4 text-blue-600" aria-hidden="true" />
            <span>باجه‌ها و ارتباطات</span>
          </h3>

          <div>
            <label htmlFor="active-counters-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              تعداد باجه‌های فعال
            </label>
            <input
              id="active-counters-input"
              type="number"
              min={1}
              max={50}
              value={activeCounters}
              onChange={(e) => setActiveCounters(Number(e.target.value))}
              className="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-hidden font-mono"
              required
            />
          </div>

          <div>
            <label htmlFor="office-phone-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              شماره تلفن مستقیم دفتر
            </label>
            <div className="relative">
              <Phone className="w-3.5 h-3.5 absolute start-2.5 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true" />
              <input
                id="office-phone-input"
                type="tel"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="مثال: 02122334455"
                className="w-full text-xs ps-8 pe-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-hidden font-mono"
              />
            </div>
          </div>
        </div>
      </div>

      <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-3">
        <label htmlFor="office-address-input" className="block text-xs font-bold text-slate-700 dark:text-slate-300">
          نشانی دقیق پستی دفتر پیشخوان
        </label>
        <div className="relative">
          <MapPin className="w-4 h-4 absolute start-2.5 top-2.5 text-slate-400" aria-hidden="true" />
          <textarea
            id="office-address-input"
            rows={2}
            value={address}
            onChange={(e) => setAddress(e.target.value)}
            placeholder="استان، شهر، خیابان اصلی، کوچه، پلاک، طبقه و کد پستی..."
            className="w-full text-xs ps-8 pe-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-hidden leading-relaxed"
          />
        </div>
      </div>

      <div className="flex justify-end">
        <Button variant="primary" size="sm" type="submit" disabled={isUpdating}>
          <Save className="w-4 h-4 me-1.5" aria-hidden="true" />
          <span>{isUpdating ? 'در حال ذخیره‌سازی...' : 'ذخیره تغییرات مشخصات'}</span>
        </Button>
      </div>
    </form>
  );
};
