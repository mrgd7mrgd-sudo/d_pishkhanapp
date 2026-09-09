import { useQuery, useInfiniteQuery } from '@tanstack/react-query';
import { catalogApi } from '../api/catalogApi';
import type { ServiceFilters, ServiceCategory, ServiceItem, ServicesResponse } from '../types';
import { qk } from '@/shared/api/query-keys';

export const useCategories = () => {
  return useQuery<ServiceCategory[]>({
    queryKey: qk.categories.all,
    queryFn: () => catalogApi.getCategories(),
    staleTime: 60_000,
  });
};

export const useInfiniteServices = (filters: ServiceFilters = {}) => {
  return useInfiniteQuery<ServicesResponse>({
    queryKey: qk.services.list(filters as Record<string, unknown>),
    queryFn: ({ pageParam }) =>
      catalogApi.getServices({
        category_id: filters.categoryId,
        tag: filters.tag,
        search: filters.search,
        sort: filters.sort,
        cursor: pageParam as string | undefined,
      }),
    initialPageParam: undefined,
    getNextPageParam: (lastPage) => lastPage.meta.next_cursor ?? undefined,
    staleTime: 60_000,
  });
};

export const useServiceDetail = (slug: string) => {
  return useQuery<ServiceItem>({
    queryKey: qk.services.detail(slug),
    queryFn: () => catalogApi.getServiceBySlug(slug),
    enabled: Boolean(slug),
    staleTime: 60_000,
  });
};
