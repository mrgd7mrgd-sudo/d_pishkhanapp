import React, { useState, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';
import { Inbox, Loader2 } from 'lucide-react';
import { Button } from '@pishkhan/ui-kit';
import { SearchBar } from './SearchBar';
import { CategoryPills } from './CategoryPills';
import { TagFilterPills } from './TagFilterPills';
import { ServiceCard } from './ServiceCard';
import { ServiceDetailSheet } from './ServiceDetailSheet';
import { useCategories, useInfiniteServices } from '../model/useCatalogQueries';
import type { ServiceItem, ServiceTag, ServiceCategory } from '../types';

interface CatalogHeaderProps {
  search: string;
  onSearchChange: (q: string) => void;
  categories: ServiceCategory[];
  isCategoriesLoading: boolean;
  selectedCategoryId?: string | undefined;
  onSelectCategory: (id?: string | undefined) => void;
  selectedTag?: ServiceTag | undefined;
  onSelectTag: (tag?: ServiceTag | undefined) => void;
}

const CatalogHeader: React.FC<CatalogHeaderProps> = ({
  search,
  onSearchChange,
  categories,
  isCategoriesLoading,
  selectedCategoryId,
  onSelectCategory,
  selectedTag,
  onSelectTag,
}) => {
  const { t } = useTranslation();

  return (
    <header className="sticky top-0 z-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 px-4 py-4 space-y-3.5">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-black text-slate-900 dark:text-slate-100">
            {t('catalog.title')}
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            {t('catalog.subtitle')}
          </p>
        </div>
      </div>

      <SearchBar value={search} onChange={onSearchChange} />

      {!isCategoriesLoading && (
        <CategoryPills
          categories={categories}
          selectedCategoryId={selectedCategoryId}
          onSelectCategory={onSelectCategory}
        />
      )}

      <TagFilterPills selectedTag={selectedTag} onSelectTag={onSelectTag} />
    </header>
  );
};

const CatalogEmptyState: React.FC<{ hasFilters: boolean; onClear: () => void }> = ({
  hasFilters,
  onClear,
}) => {
  const { t } = useTranslation();

  return (
    <div
      data-testid="catalog-empty-state"
      className="flex flex-col items-center justify-center py-16 text-center px-4"
    >
      <div className="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-4">
        <Inbox className="w-8 h-8" />
      </div>
      <h3 className="text-base font-bold text-slate-900 dark:text-slate-100 mb-1">
        {t('catalog.no_results_title')}
      </h3>
      <p className="text-xs text-slate-500 max-w-sm mb-6">
        {t('catalog.no_results_description')}
      </p>
      {hasFilters && (
        <Button
          type="button"
          variant="secondary"
          onClick={onClear}
          className="text-xs font-semibold"
        >
          {t('catalog.clear_filters')}
        </Button>
      )}
    </div>
  );
};

const CatalogLoadingGrid: React.FC = () => (
  <div
    data-testid="catalog-loading"
    className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
  >
    {Array.from({ length: 6 }).map((_, i) => (
      <div
        key={i}
        className="h-64 rounded-2xl bg-slate-200/60 dark:bg-slate-800 animate-pulse"
      />
    ))}
  </div>
);

const CatalogLoadMoreFooter: React.FC<{
  hasNextPage: boolean;
  isFetchingNextPage: boolean;
  fetchNextPage: () => void;
}> = ({ hasNextPage, isFetchingNextPage, fetchNextPage }) => {
  const { t } = useTranslation();
  if (!hasNextPage) return null;

  return (
    <div className="flex justify-center pt-8 pb-4">
      <Button
        type="button"
        variant="secondary"
        disabled={isFetchingNextPage}
        onClick={fetchNextPage}
        className="px-6 py-2.5 text-xs font-semibold flex items-center gap-2"
      >
        {isFetchingNextPage ? (
          <>
            <Loader2 className="w-4 h-4 animate-spin" />
            <span>{t('catalog.loading_more')}</span>
          </>
        ) : (
          <span>{t('catalog.load_more')}</span>
        )}
      </Button>
    </div>
  );
};

const CatalogGrid: React.FC<{
  services: ServiceItem[];
  onSelect: (service: ServiceItem) => void;
}> = ({ services, onSelect }) => (
  <div
    data-testid="catalog-services-grid"
    className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
  >
    {services.map((service) => (
      <ServiceCard key={service.id} service={service} onSelect={onSelect} />
    ))}
  </div>
);

const useCatalogFilters = () => {
  const [searchParams, setSearchParams] = useSearchParams();

  const [search, setSearch] = useState(searchParams.get('q') ?? '');
  const [selectedCategoryId, setSelectedCategoryId] = useState<string | undefined>(
    searchParams.get('category') ?? undefined,
  );
  const [selectedTag, setSelectedTag] = useState<ServiceTag | undefined>(
    (searchParams.get('tag') as ServiceTag) ?? undefined,
  );

  const updateParam = (key: string, val?: string) => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (val) next.set(key, val);
      else next.delete(key);
      return next;
    });
  };

  return {
    search,
    selectedCategoryId,
    selectedTag,
    onSearchChange: (q: string) => {
      setSearch(q);
      updateParam('q', q);
    },
    onSelectCategory: (id?: string) => {
      setSelectedCategoryId(id);
      updateParam('category', id);
    },
    onSelectTag: (tag?: ServiceTag) => {
      setSelectedTag(tag);
      updateParam('tag', tag);
    },
    onClearFilters: () => {
      setSearch('');
      setSelectedCategoryId(undefined);
      setSelectedTag(undefined);
      setSearchParams({});
    },
  };
};

export const ServiceCatalogView: React.FC = () => {
  const [selectedService, setSelectedService] = useState<ServiceItem | null>(null);
  const filters = useCatalogFilters();
  const { data: categories = [], isLoading: isCategoriesLoading } = useCategories();

  const queryFilters = useMemo(
    () => ({
      categoryId: filters.selectedCategoryId,
      tag: filters.selectedTag,
      search: filters.search || undefined,
    }),
    [filters.selectedCategoryId, filters.selectedTag, filters.search],
  );

  const {
    data,
    isLoading: isServicesLoading,
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage,
  } = useInfiniteServices(queryFilters);

  const allServices = useMemo(() => {
    return data?.pages.flatMap((page) => page.data) ?? [];
  }, [data]);

  return (
    <div className="flex flex-col min-h-screen bg-slate-50 dark:bg-slate-900 pb-20">
      <CatalogHeader
        search={filters.search}
        onSearchChange={filters.onSearchChange}
        categories={categories}
        isCategoriesLoading={isCategoriesLoading}
        selectedCategoryId={filters.selectedCategoryId}
        onSelectCategory={filters.onSelectCategory}
        selectedTag={filters.selectedTag}
        onSelectTag={filters.onSelectTag}
      />

      <main className="flex-1 px-4 py-5 max-w-5xl mx-auto w-full">
        {isServicesLoading && <CatalogLoadingGrid />}

        {!isServicesLoading && allServices.length === 0 && (
          <CatalogEmptyState
            hasFilters={Boolean(
              filters.selectedCategoryId || filters.selectedTag || filters.search,
            )}
            onClear={filters.onClearFilters}
          />
        )}

        {!isServicesLoading && allServices.length > 0 && (
          <CatalogGrid services={allServices} onSelect={(s) => setSelectedService(s)} />
        )}

        <CatalogLoadMoreFooter
          hasNextPage={Boolean(hasNextPage)}
          isFetchingNextPage={isFetchingNextPage}
          fetchNextPage={() => fetchNextPage()}
        />
      </main>

      <ServiceDetailSheet
        service={selectedService}
        onClose={() => setSelectedService(null)}
      />
    </div>
  );
};
