import React from 'react';
import { useTranslation } from 'react-i18next';
import type { VaultCategory } from '../types';

export interface VaultCategoryFilterProps {
  activeCategory: string;
  onSelectCategory: (cat: string) => void;
}

const CATEGORIES: Array<{ code: VaultCategory | ''; labelKey: string }> = [
  { code: '', labelKey: 'vault.cat_all' },
  { code: 'identity', labelKey: 'vault.cat_identity' },
  { code: 'education', labelKey: 'vault.cat_education' },
  { code: 'finance', labelKey: 'vault.cat_finance' },
  { code: 'legal', labelKey: 'vault.cat_legal' },
  { code: 'medical', labelKey: 'vault.cat_medical' },
  { code: 'general', labelKey: 'vault.cat_general' },
];

export function VaultCategoryFilter({
  activeCategory,
  onSelectCategory,
}: VaultCategoryFilterProps): React.JSX.Element {
  const { t } = useTranslation();

  return (
    <div
      role="tablist"
      aria-label={t('vault.categories_aria_label')}
      className="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar"
    >
      {CATEGORIES.map((cat) => {
        const isSelected = activeCategory === cat.code;
        return (
          <button
            key={cat.code || 'all'}
            type="button"
            role="tab"
            aria-selected={isSelected}
            onClick={() => onSelectCategory(cat.code)}
            className={`text-xs px-3.5 py-2 rounded-xl font-medium shrink-0 transition-all ${
              isSelected
                ? 'bg-slate-900 text-white shadow-xs dark:bg-slate-100 dark:text-slate-900'
                : 'bg-white/80 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100'
            }`}
          >
            {t(cat.labelKey)}
          </button>
        );
      })}
    </div>
  );
}
