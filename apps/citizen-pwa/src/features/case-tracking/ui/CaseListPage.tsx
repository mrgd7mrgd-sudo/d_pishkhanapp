import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Skeleton, Input } from '@pishkhan/ui-kit';
import { type CaseSummary } from '../types';
import { caseTrackingApi } from '../api/caseTrackingApi';
import { CaseCard } from './CaseCard';

interface CaseFilterTabsProps {
  filterActiveOnly: boolean;
  onSelectFilter: (activeOnly: boolean) => void;
}

function CaseFilterTabs({ filterActiveOnly, onSelectFilter }: CaseFilterTabsProps): React.JSX.Element {
  const { t } = useTranslation();
  return (
    <div className="flex items-center gap-2">
      <button
        type="button"
        onClick={() => onSelectFilter(false)}
        className={`text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors ${
          !filterActiveOnly
            ? 'bg-slate-900 text-white border-slate-900 dark:bg-slate-100 dark:text-slate-900'
            : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'
        }`}
      >
        {t('cases.filter_all')}
      </button>
      <button
        type="button"
        onClick={() => onSelectFilter(true)}
        className={`text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors ${
          filterActiveOnly
            ? 'bg-slate-900 text-white border-slate-900 dark:bg-slate-100 dark:text-slate-900'
            : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'
        }`}
      >
        {t('cases.filter_active')}
      </button>
    </div>
  );
}

function CaseListContent({
  isLoading,
  items,
}: {
  isLoading: boolean;
  items: CaseSummary[];
}): React.JSX.Element {
  const { t } = useTranslation();
  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-28 w-full rounded-2xl" />
        <Skeleton className="h-28 w-full rounded-2xl" />
        <Skeleton className="h-28 w-full rounded-2xl" />
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="text-center py-12 p-6 rounded-2xl bg-white/50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-800 space-y-2">
        <p className="text-sm font-semibold text-slate-700 dark:text-slate-300">
          {t('cases.empty_list_title')}
        </p>
        <p className="text-xs text-slate-500">{t('cases.empty_list_desc')}</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {items.map((item) => (
        <CaseCard key={item.id} item={item} />
      ))}
    </div>
  );
}

export function CaseListPage(): React.JSX.Element {
  const { t } = useTranslation();
  const [cases, setCases] = useState<CaseSummary[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [filterActiveOnly, setFilterActiveOnly] = useState<boolean>(false);

  useEffect(() => {
    let mounted = true;
    caseTrackingApi
      .getCases()
      .then((data) => {
        if (mounted) {
          setCases(data);
          setIsLoading(false);
        }
      })
      .catch(() => {
        if (mounted) setIsLoading(false);
      });
    return () => {
      mounted = false;
    };
  }, []);

  const filteredCases = cases.filter((c) => {
    const matchesSearch =
      c.tracking_code.toLowerCase().includes(searchTerm.toLowerCase()) ||
      c.service.title.includes(searchTerm);
    if (!matchesSearch) return false;
    return filterActiveOnly ? !['completed', 'rejected', 'cancelled'].includes(c.status) : true;
  });

  return (
    <div className="max-w-4xl mx-auto px-4 py-6 space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-slate-900 dark:text-slate-100">{t('cases.page_title')}</h1>
          <p className="text-xs text-slate-500 mt-1">{t('cases.page_subtitle')}</p>
        </div>
        <CaseFilterTabs filterActiveOnly={filterActiveOnly} onSelectFilter={setFilterActiveOnly} />
      </div>

      <div>
        <Input
          placeholder={t('cases.search_placeholder')}
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          aria-label={t('cases.search_aria_label')}
        />
      </div>

      <CaseListContent isLoading={isLoading} items={filteredCases} />
    </div>
  );
}
