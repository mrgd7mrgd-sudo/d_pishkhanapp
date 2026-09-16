import React, { useState } from 'react';
import { Button } from '@pishkhan/ui-kit';
import { Bell, Plus, Trash2, AlertCircle } from 'lucide-react';
import type { OfficeAnnouncementItem, CreateAnnouncementPayload } from '../types';

export interface AnnouncementsSubTabProps {
  announcements: OfficeAnnouncementItem[];
  onCreateAnnouncement: (payload: CreateAnnouncementPayload) => Promise<void>;
  onDeleteAnnouncement: (id: string) => Promise<void>;
  isProcessing: boolean;
}

export const AnnouncementsSubTab: React.FC<AnnouncementsSubTabProps> = ({
  announcements,
  onCreateAnnouncement,
  onDeleteAnnouncement,
  isProcessing,
}) => {
  const [showAddForm, setShowAddForm] = useState(false);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [priority, setPriority] = useState<'normal' | 'important' | 'urgent'>('normal');
  const [error, setError] = useState<string | null>(null);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim() || !content.trim()) {
      setError('عنوان و متن اطلاعیه الزامی است.');
      return;
    }
    setError(null);
    try {
      await onCreateAnnouncement({
        title: title.trim(),
        content: content.trim(),
        priority,
      });
      setShowAddForm(false);
      setTitle('');
      setContent('');
      setPriority('normal');
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'خطا در ثبت اطلاعیه');
    }
  };

  return (
    <section aria-labelledby="announcements-subtab-title" className="space-y-6">
      <h2 id="announcements-subtab-title" className="sr-only">اطلاعیه‌های عمومی دفتر</h2>

      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <Bell className="w-4 h-4 text-blue-600" aria-hidden="true" />
            <span>تابلوی اعلانات و اطلاعیه‌های دفتر</span>
          </h3>
          <p className="text-xs text-slate-500 mt-0.5">
            اطلاعیه‌های نمایش داده شده در پروفایل عمومی دفتر برای شهروندان و مراجعین حضوری
          </p>
        </div>

        <Button
          variant="primary"
          size="sm"
          onClick={() => setShowAddForm(!showAddForm)}
          data-testid="add-announcement-btn"
        >
          <Plus className="w-3.5 h-3.5 me-1" aria-hidden="true" />
          <span>{showAddForm ? 'بستن فرم' : 'اطلاعیه جدید'}</span>
        </Button>
      </div>

      {showAddForm && (
        <form onSubmit={handleCreate} className="p-4 rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/40 dark:bg-blue-950/20 space-y-4">
          <h4 className="text-xs font-bold text-blue-900 dark:text-blue-300">
            ثبت اطلاعیه جدید در تابلوی اعلانات
          </h4>

          {error && (
            <div role="alert" className="p-2 text-xs rounded bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400">
              {error}
            </div>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div className="sm:col-span-2">
              <label htmlFor="ann-title-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                عنوان اطلاعیه <span className="text-rose-500">*</span>
              </label>
              <input
                id="ann-title-input"
                type="text"
                required
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="مثال: قطعی موقت سامانه ثبت احوال..."
                className="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden"
              />
            </div>

            <div>
              <label htmlFor="ann-priority-select" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                اولویت نمایش
              </label>
              <select
                id="ann-priority-select"
                value={priority}
                onChange={(e) => setPriority(e.target.value as 'normal' | 'important' | 'urgent')}
                className="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden"
              >
                <option value="normal">عادی</option>
                <option value="important">مهم</option>
                <option value="urgent">فوری / اضطراری</option>
              </select>
            </div>
          </div>

          <div>
            <label htmlFor="ann-content-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              متن کامل اطلاعیه <span className="text-rose-500">*</span>
            </label>
            <textarea
              id="ann-content-input"
              rows={3}
              required
              value={content}
              onChange={(e) => setContent(e.target.value)}
              placeholder="شرح دقیق موضوع، بازه زمانی یا دستورالعمل مراجعه..."
              className="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden leading-relaxed"
            />
          </div>

          <div className="flex justify-end gap-2">
            <Button variant="secondary" size="sm" type="button" onClick={() => setShowAddForm(false)}>
              انصراف
            </Button>
            <Button variant="primary" size="sm" type="submit" disabled={isProcessing}>
              انتشار اطلاعیه
            </Button>
          </div>
        </form>
      )}

      {/* Announcements List */}
      <div className="space-y-3">
        {announcements.map((ann) => (
          <div
            key={ann.id}
            data-testid={`announcement-card-${ann.id}`}
            className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-2"
          >
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="text-xs font-bold text-slate-900 dark:text-white">
                  {ann.title}
                </span>
                {ann.priority === 'urgent' && (
                  <span className="text-[10px] bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 px-1.5 py-0.5 rounded-sm font-semibold flex items-center gap-0.5">
                    <AlertCircle className="w-3 h-3" aria-hidden="true" />
                    فوری
                  </span>
                )}
                {ann.priority === 'important' && (
                  <span className="text-[10px] bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 px-1.5 py-0.5 rounded-sm font-semibold">
                    مهم
                  </span>
                )}
              </div>

              <button
                type="button"
                onClick={() => onDeleteAnnouncement(ann.id)}
                disabled={isProcessing}
                data-testid={`delete-ann-${ann.id}`}
                className="text-slate-400 hover:text-rose-500 p-1 transition-colors"
                aria-label={`حذف اطلاعیه ${ann.title}`}
              >
                <Trash2 className="w-4 h-4" aria-hidden="true" />
              </button>
            </div>

            <p className="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
              {ann.content}
            </p>
          </div>
        ))}

        {announcements.length === 0 && (
          <div className="text-center py-12 bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl">
            <p className="text-xs text-slate-400">هیچ اطلاعیه‌ای در تابلوی اعلانات ثبت نشده است.</p>
          </div>
        )}
      </div>
    </section>
  );
};
