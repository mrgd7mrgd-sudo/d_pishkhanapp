import React, { useState } from 'react';
import { Bell, Clock, FileText, CheckCircle, Smartphone } from 'lucide-react';
import { Button } from '@pishkhan/ui-kit';
import type { SmartReminderItem, ReminderChannel } from '../types';

const INITIAL_REMINDERS: SmartReminderItem[] = [
  {
    id: 'rem_1',
    title: 'مراجعه حضوری تعویض کارت ملی هوشمند',
    description: 'نوبت رزرو شده در باجه شماره ۲ دفتر پیشخوان شریعتی',
    due_date: '1405/06/25',
    due_time: '10:30',
    channel: 'both',
    lead_time_minutes: 120,
    is_enabled: true,
    required_documents: [
      'اصل شناسنامه عکس‌دار',
      'کد پستی ۱۰ رقمی محل سکونت',
      'عکس پرسنلی جدید',
    ],
  },
  {
    id: 'rem_2',
    title: 'تمدید گواهی پایان خدمت و مدارک نظام وظیفه',
    description: 'پیگیری آنلاین و رفع نقص مدرک در سامانه پیشخوان',
    due_date: '1405/06/30',
    channel: 'sms',
    lead_time_minutes: 1440,
    is_enabled: false,
    required_documents: [
      'کارت ملی هوشمند',
      'گواهی اشتغال به تحصیل یا معافیت',
    ],
  },
];

export const SmartReminderPage: React.FC = () => {
  const [reminders, setReminders] = useState<SmartReminderItem[]>(INITIAL_REMINDERS);
  const [feedback, setFeedback] = useState<string | null>(null);

  const toggleReminder = (id: string) => {
    setReminders((prev) =>
      prev.map((r) => (r.id === id ? { ...r, is_enabled: !r.is_enabled } : r))
    );
    setFeedback('تنظیمات یادآور هوشمند با موفقیت به‌روزرسانی شد.');
    setTimeout(() => setFeedback(null), 3000);
  };

  const updateLeadTime = (id: string, minutes: number) => {
    setReminders((prev) =>
      prev.map((r) => (r.id === id ? { ...r, lead_time_minutes: minutes } : r))
    );
  };

  const updateChannel = (id: string, channel: ReminderChannel) => {
    setReminders((prev) =>
      prev.map((r) => (r.id === id ? { ...r, channel } : r))
    );
  };

  return (
    <div className="space-y-5 max-w-2xl mx-auto p-4 sm:p-6" data-testid="smart-reminders-page">
      <header className="border-b border-slate-200 dark:border-slate-800 pb-4">
        <h1 className="text-lg sm:text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <Bell className="w-5 h-5 text-blue-600" aria-hidden="true" />
          <span>یادآورهای هوشمند مراجعات و مدارک</span>
        </h1>
        <p className="text-xs text-slate-500 mt-1">
          تنظیم زمان‌بندی هشدارهای پیامکی و اعلانات همراه با چک‌لیست مدارک موردنیاز
        </p>
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

      <main className="space-y-4">
        {reminders.map((rem) => (
          <article
            key={rem.id}
            data-testid={`reminder-card-${rem.id}`}
            className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3"
          >
            <div className="flex items-start justify-between gap-3">
              <div>
                <h2 className="text-sm font-bold text-slate-900 dark:text-white">
                  {rem.title}
                </h2>
                <p className="text-xs text-slate-500 mt-0.5">{rem.description}</p>
              </div>

              <button
                type="button"
                onClick={() => toggleReminder(rem.id)}
                data-testid={`toggle-rem-${rem.id}`}
                className={`px-3 py-1 rounded-full text-xs font-semibold transition-colors ${
                  rem.is_enabled
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
                }`}
              >
                {rem.is_enabled ? 'یادآوری فعال' : 'غیرفعال'}
              </button>
            </div>

            {/* Timing and Settings */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-xl text-xs">
              <div className="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                <Clock className="w-3.5 h-3.5 text-blue-600" aria-hidden="true" />
                <span>مهلت موعد:</span>
                <span className="font-mono font-bold">{rem.due_date} {rem.due_time ?? ''}</span>
              </div>

              <div className="flex items-center gap-2">
                <span className="text-slate-500 text-[11px]">ارسال یادآور:</span>
                <select
                  value={rem.lead_time_minutes}
                  onChange={(e) => updateLeadTime(rem.id, Number(e.target.value))}
                  aria-label="زمان پیش از موعد برای یادآوری"
                  className="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-md text-[11px] p-1 focus:outline-hidden"
                >
                  <option value={60}>۱ ساعت قبل</option>
                  <option value={120}>۲ ساعت قبل</option>
                  <option value={1440}>۲۴ ساعت قبل</option>
                </select>
              </div>
            </div>

            {/* Required Documents Checklist */}
            <div className="space-y-1.5 pt-1">
              <span className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                <FileText className="w-3.5 h-3.5 text-blue-600" aria-hidden="true" />
                <span>مدارک لازم برای همراه داشتن در زمان مراجعه:</span>
              </span>
              <ul className="space-y-1 ps-4">
                {rem.required_documents.map((doc, idx) => (
                  <li key={idx} className="text-xs text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                    <CheckCircle className="w-3 h-3 text-emerald-500 shrink-0" aria-hidden="true" />
                    <span>{doc}</span>
                  </li>
                ))}
              </ul>
            </div>
          </article>
        ))}
      </main>
    </div>
  );
};
