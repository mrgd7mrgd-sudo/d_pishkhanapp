import React from 'react';
import { useTranslation } from 'react-i18next';
import { clsx } from 'clsx';
import { SERVICE_TAGS, type ServiceTag } from '@pishkhan/domain';

export interface TagFilterPillsProps {
  selectedTag?: ServiceTag | undefined;
  onSelectTag: (tag?: ServiceTag | undefined) => void;
}

const TAG_TRANSLATION_KEYS: Record<ServiceTag, string> = {
  online: 'catalog.tag_online',
  'semi-online': 'catalog.tag_semi_online',
  'in-person': 'catalog.tag_in_person',
};

export const TagFilterPills: React.FC<TagFilterPillsProps> = ({
  selectedTag,
  onSelectTag,
}) => {
  const { t } = useTranslation();

  return (
    <div
      role="group"
      aria-label={t('catalog.filter_tag')}
      className="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs"
    >
      <button
        type="button"
        onClick={() => onSelectTag(undefined)}
        aria-pressed={!selectedTag}
        className={clsx(
          'shrink-0 px-2.5 py-1 rounded-lg font-medium transition-all cursor-pointer border',
          !selectedTag
            ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 border-transparent'
            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:bg-slate-200',
        )}
      >
        {t('catalog.tag_all')}
      </button>

      {SERVICE_TAGS.map((tag) => {
        const isSelected = selectedTag === tag;
        const labelKey = TAG_TRANSLATION_KEYS[tag];

        return (
          <button
            key={tag}
            type="button"
            onClick={() => onSelectTag(isSelected ? undefined : tag)}
            aria-pressed={isSelected}
            className={clsx(
              'shrink-0 px-2.5 py-1 rounded-lg font-medium transition-all cursor-pointer border',
              isSelected
                ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 border-transparent'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:bg-slate-200',
            )}
          >
            {t(labelKey)}
          </button>
        );
      })}
    </div>
  );
};
