import React, { useState, useEffect, useRef, useId } from 'react';
import { useTranslation } from 'react-i18next';
import { Search, X } from 'lucide-react';
import { normalizePersianText } from '@pishkhan/domain';

export interface SearchBarProps {
  value: string;
  onChange: (value: string) => void;
  debounceMs?: number;
}

export const SearchBar: React.FC<SearchBarProps> = ({
  value,
  onChange,
  debounceMs = 300,
}) => {
  const { t } = useTranslation();
  const searchInputId = useId();
  const [localValue, setLocalValue] = useState(value);
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const rawValue = e.target.value;
    const normalized = normalizePersianText(rawValue);
    setLocalValue(normalized);

    if (timerRef.current) {
      clearTimeout(timerRef.current);
    }

    timerRef.current = setTimeout(() => {
      onChange(normalized.trim());
    }, debounceMs);
  };

  const handleClear = () => {
    setLocalValue('');
    if (timerRef.current) {
      clearTimeout(timerRef.current);
    }
    onChange('');
  };

  return (
    <div className="relative w-full">
      <label htmlFor={searchInputId} className="sr-only">
        {t('catalog.search_label')}
      </label>
      <div className="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-slate-400">
        <Search className="w-5 h-5" aria-hidden="true" />
      </div>
      <input
        id={searchInputId}
        type="search"
        value={localValue}
        onChange={handleChange}
        placeholder={t('catalog.search_placeholder')}
        className="w-full h-11 ps-10 pe-10 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-slate-100 placeholder-slate-400 rounded-xl border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all text-sm"
      />
      {localValue && (
        <button
          type="button"
          onClick={handleClear}
          aria-label={t('catalog.search_clear')}
          className="absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
        >
          <X className="w-4 h-4" />
        </button>
      )}
    </div>
  );
};
