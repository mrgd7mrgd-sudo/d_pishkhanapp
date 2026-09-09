import React from 'react';
import { useTranslation } from 'react-i18next';
import { clsx } from 'clsx';
import type { ServiceCategory } from '../types';

export interface CategoryPillsProps {
  categories: ServiceCategory[];
  selectedCategoryId?: string | undefined;
  onSelectCategory: (categoryId?: string | undefined) => void;
}

interface PillButtonProps {
  id?: string | undefined;
  title: string;
  count?: number | undefined;
  isSelected: boolean;
  onSelect: () => void;
}

const PillButton: React.FC<PillButtonProps> = ({
  id,
  title,
  count,
  isSelected,
  onSelect,
}) => (
  <button
    type="button"
    data-testid={id ? `category-pill-${id}` : 'category-pill-all'}
    aria-label={title}
    onClick={onSelect}
    aria-pressed={isSelected}
    className={clsx(
      'shrink-0 flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl font-medium transition-all cursor-pointer border',
      isSelected
        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm shadow-emerald-600/20'
        : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750',
    )}
  >
    <span>{title}</span>
    {typeof count === 'number' && (
      <span
        className={clsx(
          'text-xs px-1.5 py-0.2 rounded-md font-mono',
          isSelected
            ? 'bg-emerald-700 text-emerald-100'
            : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400',
        )}
      >
        {count}
      </span>
    )}
  </button>
);

export const CategoryPills: React.FC<CategoryPillsProps> = ({
  categories,
  selectedCategoryId,
  onSelectCategory,
}) => {
  const { t } = useTranslation();

  return (
    <div
      role="group"
      aria-label={t('catalog.filter_category')}
      className="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar text-sm"
    >
      <PillButton
        title={t('catalog.all_categories')}
        isSelected={!selectedCategoryId}
        onSelect={() => onSelectCategory(undefined)}
      />
      {categories.map((cat) => (
        <PillButton
          key={cat.id}
          id={cat.id}
          title={cat.title}
          count={cat.services_count}
          isSelected={selectedCategoryId === cat.id}
          onSelect={() => onSelectCategory(selectedCategoryId === cat.id ? undefined : cat.id)}
        />
      ))}
    </div>
  );
};
