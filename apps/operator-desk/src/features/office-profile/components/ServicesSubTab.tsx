import React, { useState } from 'react';
import { Button } from '@pishkhan/ui-kit';
import { CheckSquare, Plus, X, Save, Tag } from 'lucide-react';
import type {
  OfficeSpecialtyItem,
  OfficeCoverageItem,
  ServiceCategoryCoverage,
} from '../types';

export interface ServicesSubTabProps {
  specialties: OfficeSpecialtyItem[];
  coverages: OfficeCoverageItem[];
  availableCategories: ServiceCategoryCoverage[];
  onUpdateSpecialties: (specialties: string[]) => Promise<void>;
  onUpdateCoverages: (
    coverages: Array<{ category_id: string; is_active: boolean; daily_capacity: number }>
  ) => Promise<void>;
  isUpdating: boolean;
}

export const ServicesSubTab: React.FC<ServicesSubTabProps> = ({
  specialties: initialSpecialties,
  coverages: initialCoverages,
  availableCategories,
  onUpdateSpecialties,
  onUpdateCoverages,
  isUpdating,
}) => {
  const [specialtyTags, setSpecialtyTags] = useState<string[]>(
    initialSpecialties.map((s) => s.title)
  );
  const [newTag, setNewTag] = useState('');
  const [coveragesMap, setCoveragesMap] = useState<
    Record<string, { is_active: boolean; daily_capacity: number }>
  >(() => {
    const map: Record<string, { is_active: boolean; daily_capacity: number }> = {};
    availableCategories.forEach((cat) => {
      const existing = initialCoverages.find((c) => c.category_id === cat.id);
      map[cat.id] = {
        is_active: existing ? existing.is_active : false,
        daily_capacity: existing ? existing.daily_capacity : 100,
      };
    });
    return map;
  });

  const [feedback, setFeedback] = useState<string | null>(null);

  const handleAddTag = (e: React.FormEvent) => {
    e.preventDefault();
    const clean = newTag.trim();
    if (clean && !specialtyTags.includes(clean)) {
      setSpecialtyTags([...specialtyTags, clean]);
      setNewTag('');
    }
  };

  const handleRemoveTag = (tag: string) => {
    setSpecialtyTags(specialtyTags.filter((t) => t !== tag));
  };

  const handleToggleCategory = (catId: string) => {
    setCoveragesMap((prev) => ({
      ...prev,
      [catId]: {
        is_active: !prev[catId]?.is_active,
        daily_capacity: prev[catId]?.daily_capacity ?? 100,
      },
    }));
  };

  const handleSaveAll = async () => {
    setFeedback(null);
    const coveragesPayload = Object.entries(coveragesMap).map(([catId, data]) => ({
      category_id: catId,
      is_active: data.is_active,
      daily_capacity: data.daily_capacity,
    }));

    await Promise.all([
      onUpdateSpecialties(specialtyTags),
      onUpdateCoverages(coveragesPayload),
    ]);

    setFeedback('پوشش دسته‌ها و تخصص‌های دفتر بلافاصله در سیستم جستجو و توزیع اعمال شد.');
    setTimeout(() => setFeedback(null), 4000);
  };

  return (
    <section aria-labelledby="services-subtab-title" className="space-y-6">
      <h2 id="services-subtab-title" className="sr-only">پوشش خدمات و تخصص‌ها</h2>

      {feedback && (
        <div role="status" aria-live="polite" className="p-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs">
          {feedback}
        </div>
      )}

      {/* Category Coverages */}
      <div className="p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-4">
        <div>
          <h3 className="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <CheckSquare className="w-4 h-4 text-blue-600" aria-hidden="true" />
            <span>پوشش دسته‌بندی‌های خدمات (فعال/غیرفعال برای توزیع و جستجو)</span>
          </h3>
          <p className="text-xs text-slate-500 mt-1">
            با غیرفعال‌سازی هر دسته، این دفتر در موتور جستجوی دفتر هوشمند شهروند برای خدمات آن دسته پیشنهاد نخواهد شد.
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
          {availableCategories.map((cat) => {
            const isChecked = coveragesMap[cat.id]?.is_active ?? false;
            return (
              <div
                key={cat.id}
                onClick={() => handleToggleCategory(cat.id)}
                className={`p-3 rounded-lg border cursor-pointer transition-all flex items-center justify-between ${
                  isChecked
                    ? 'border-blue-500 bg-blue-50/40 dark:bg-blue-950/20'
                    : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 opacity-70'
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <input
                    type="checkbox"
                    checked={isChecked}
                    onChange={() => handleToggleCategory(cat.id)}
                    aria-label={`فعال بودن پوشش ${cat.title}`}
                    className="w-4 h-4 text-blue-600 rounded-sm border-slate-300 focus:ring-blue-500"
                  />
                  <div>
                    <span className="text-xs font-semibold text-slate-900 dark:text-white block">
                      {cat.title}
                    </span>
                    {cat.short_title && (
                      <span className="text-[10px] text-slate-500">{cat.short_title}</span>
                    )}
                  </div>
                </div>
                <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${
                  isChecked
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                    : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-400'
                }`}>
                  {isChecked ? 'پوشش فعال' : 'غیرفعال'}
                </span>
              </div>
            );
          })}
        </div>
      </div>

      {/* Specialties Tags */}
      <div className="p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-4">
        <div>
          <h3 className="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <Tag className="w-4 h-4 text-blue-600" aria-hidden="true" />
            <span>تخصص‌ها و برچسب‌های مهارتی دفتر</span>
          </h3>
          <p className="text-xs text-slate-500 mt-1">
            مهارت‌های ویژه پرسنل یا سرویس‌های شاخص باجه‌ها که به شهروندان نمایش داده می‌شود.
          </p>
        </div>

        <form onSubmit={handleAddTag} className="flex gap-2">
          <input
            type="text"
            placeholder="عنوان تخصص جدید (مثال: صدور فوری شناسنامه نوزاد)..."
            value={newTag}
            onChange={(e) => setNewTag(e.target.value)}
            aria-label="عنوان تخصص جدید"
            className="flex-1 text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-hidden"
          />
          <Button variant="secondary" size="sm" type="submit">
            <Plus className="w-3.5 h-3.5 me-1" aria-hidden="true" />
            <span>افزودن</span>
          </Button>
        </form>

        <div className="flex flex-wrap gap-2 pt-2">
          {specialtyTags.map((tag) => (
            <span
              key={tag}
              className="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-medium"
            >
              <span>{tag}</span>
              <button
                type="button"
                onClick={() => handleRemoveTag(tag)}
                className="text-slate-400 hover:text-rose-500"
                aria-label={`حذف تخصص ${tag}`}
              >
                <X className="w-3.5 h-3.5" aria-hidden="true" />
              </button>
            </span>
          ))}
          {specialtyTags.length === 0 && (
            <p className="text-xs text-slate-400 italic">هیچ تخصصی هنوز ثبت نشده است.</p>
          )}
        </div>
      </div>

      <div className="flex justify-end">
        <Button variant="primary" size="sm" onClick={handleSaveAll} disabled={isUpdating}>
          <Save className="w-4 h-4 me-1.5" aria-hidden="true" />
          <span>{isUpdating ? 'در حال اعمال...' : 'ذخیره پوشش و تخصص‌ها'}</span>
        </Button>
      </div>
    </section>
  );
};
