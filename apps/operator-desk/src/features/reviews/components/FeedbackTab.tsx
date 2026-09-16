import React, { useState } from 'react';
import { Search } from 'lucide-react';
import type { DeskReview, ReviewFilter } from '../types';
import { ReviewItemCard } from './ReviewItemCard';

export interface FeedbackTabProps {
  reviews: DeskReview[];
  isLoading: boolean;
  isManager: boolean;
  filter: ReviewFilter;
  onFilterChange: (filter: ReviewFilter) => void;
  onSearchChange: (query: string) => void;
  onOpenReply: (review: DeskReview) => void;
}

const FILTER_PILLS: Array<{ key: ReviewFilter; label: string }> = [
  { key: 'all', label: 'همه نظرات' },
  { key: '5star', label: '۵ ستاره' },
  { key: 'needReply', label: 'نیازمند پاسخ' },
  { key: 'withReply', label: 'پاسخ‌داده‌شده' },
];

export const FeedbackTab: React.FC<FeedbackTabProps> = ({
  reviews,
  isLoading,
  isManager,
  filter,
  onFilterChange,
  onSearchChange,
  onOpenReply,
}) => {
  const [searchTerm, setSearchTerm] = useState('');

  const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
    const val = e.target.value;
    setSearchTerm(val);
    onSearchChange(val);
  };

  return (
    <section aria-labelledby="feedback-tab-heading" className="space-y-4">
      <h2 id="feedback-tab-heading" className="sr-only">
        نظرات و تجربیات ثبت‌شده مراجعین
      </h2>

      <div className="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
        <div className="flex flex-wrap gap-1.5" role="tablist" aria-label="فیلترهای نظرات">
          {FILTER_PILLS.map((pill) => (
            <button
              key={pill.key}
              type="button"
              role="tab"
              aria-selected={filter === pill.key}
              data-testid={`filter-${pill.key}`}
              onClick={() => onFilterChange(pill.key)}
              className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ${
                filter === pill.key
                  ? 'bg-blue-600 text-white shadow-xs'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
              }`}
            >
              {pill.label}
            </button>
          ))}
        </div>

        <div className="relative min-w-[220px]">
          <Search className="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <input
            type="search"
            aria-label="جستجو در متن نظرات شهروندان"
            placeholder="جستجو در نظرات..."
            value={searchTerm}
            onChange={handleSearch}
            className="w-full text-xs ps-9 pe-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-hidden"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12 text-sm text-slate-500">در حال بارگذاری نظرات...</div>
      ) : reviews.length === 0 ? (
        <div className="text-center py-12 bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl">
          <p className="text-sm text-slate-500">هیچ نظری با معیارهای انتخابی یافت نشد.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {reviews.map((rev) => (
            <ReviewItemCard
              key={rev.id}
              review={rev}
              isManager={isManager}
              onOpenReply={onOpenReply}
            />
          ))}
        </div>
      )}
    </section>
  );
};
