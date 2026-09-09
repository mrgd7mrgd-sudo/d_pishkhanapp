import React from 'react';
import { useTranslation } from 'react-i18next';
import { Search, X, Star } from 'lucide-react';
import { clsx } from 'clsx';
import type { OfficeMembershipStatus } from '@pishkhan/domain';

interface OfficesFilterBarProps {
  searchQuery: string;
  onSearchChange: (query: string) => void;
  selectedStatus: OfficeMembershipStatus | 'all';
  onStatusChange: (status: OfficeMembershipStatus | 'all') => void;
  minRating: number;
  onMinRatingChange: (rating: number) => void;
}

const STATUS_FILTERS: readonly {
  readonly id: OfficeMembershipStatus | 'all';
  readonly labelKey: string;
}[] = [
  { id: 'all', labelKey: 'offices.status_all' },
  { id: 'registered_online', labelKey: 'offices.status_registered_online' },
  { id: 'registered_offline', labelKey: 'offices.status_registered_offline' },
  { id: 'unregistered', labelKey: 'offices.status_unregistered' },
] as const;

const OfficeSearchInput: React.FC<{
  value: string;
  onChange: (val: string) => void;
}> = ({ value, onChange }) => {
  const { t } = useTranslation();
  return (
    <div className="relative">
      <label htmlFor="office-search-input" className="sr-only">
        {t('offices.search_label')}
      </label>
      <div className="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-slate-400">
        <Search className="w-4 h-4" aria-hidden="true" />
      </div>
      <input
        id="office-search-input"
        type="text"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={t('offices.search_placeholder')}
        className="w-full ps-9 pe-9 py-2 bg-white/95 backdrop-blur-xs text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-xs"
      />
      {value && (
        <button
          type="button"
          onClick={() => onChange('')}
          aria-label={t('offices.search_clear')}
          className="absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400 hover:text-slate-600"
        >
          <X className="w-4 h-4" aria-hidden="true" />
        </button>
      )}
    </div>
  );
};

const OfficeStatusChipsRow: React.FC<{
  selectedStatus: OfficeMembershipStatus | 'all';
  onStatusChange: (status: OfficeMembershipStatus | 'all') => void;
  minRating: number;
  onMinRatingChange: (rating: number) => void;
}> = ({ selectedStatus, onStatusChange, minRating, onMinRatingChange }) => {
  const { t } = useTranslation();
  return (
    <div
      className="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs"
      role="group"
      aria-label={t('offices.filter_status')}
    >
      {STATUS_FILTERS.map((f) => {
        const isSelected = selectedStatus === f.id;
        return (
          <button
            key={f.id}
            type="button"
            onClick={() => onStatusChange(f.id)}
            className={clsx(
              'px-2.5 py-1 rounded-lg font-medium whitespace-nowrap transition-colors shrink-0',
              isSelected
                ? 'bg-blue-600 text-white shadow-xs'
                : 'bg-white/90 text-slate-600 border border-slate-200 hover:bg-slate-50',
            )}
          >
            {t(f.labelKey)}
          </button>
        );
      })}

      <button
        type="button"
        onClick={() => onMinRatingChange(minRating >= 4.5 ? 0 : 4.5)}
        className={clsx(
          'flex items-center gap-1 px-2.5 py-1 rounded-lg font-medium whitespace-nowrap transition-colors shrink-0',
          minRating >= 4.5
            ? 'bg-amber-500 text-white shadow-xs'
            : 'bg-white/90 text-slate-600 border border-slate-200 hover:bg-slate-50',
        )}
      >
        <Star className="w-3 h-3 fill-current" aria-hidden="true" />
        <span>{t('offices.top_rated')}</span>
      </button>
    </div>
  );
};

export const OfficesFilterBar: React.FC<OfficesFilterBarProps> = ({
  searchQuery,
  onSearchChange,
  selectedStatus,
  onStatusChange,
  minRating,
  onMinRatingChange,
}) => {
  return (
    <div className="space-y-2">
      <OfficeSearchInput value={searchQuery} onChange={onSearchChange} />
      <OfficeStatusChipsRow
        selectedStatus={selectedStatus}
        onStatusChange={onStatusChange}
        minRating={minRating}
        onMinRatingChange={onMinRatingChange}
      />
    </div>
  );
};
