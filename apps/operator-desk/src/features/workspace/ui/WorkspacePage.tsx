import React, { useEffect, useMemo, useState } from 'react';
import { useWorkspaceStore } from '../model/useWorkspaceStore';
import { DeskCaseListItem } from '../types';
import { CaseDetailWorkspace } from './CaseDetailWorkspace';
import { DataGrid, DataGridColumn, StatusPill, TurnOwnerChip, Button } from '@pishkhan/ui-kit';
import { Search, Filter, RefreshCw, Layers } from 'lucide-react';
import type { CaseStatus } from '@pishkhan/domain';

export interface WorkspacePageProps {
  autoFetch?: boolean;
}

export const WorkspacePage: React.FC<WorkspacePageProps> = ({ autoFetch = true }) => {
  const {
    cases,
    selectedCaseId,
    selectedCaseDetail,
    loadingList,
    loadingDetail,
    filter,
    setFilter,
    fetchCases,
    selectCase,
  } = useWorkspaceStore();

  const [searchInput, setSearchInput] = useState(filter.search || '');

  useEffect(() => {
    if (autoFetch) {
      void fetchCases();
    }
  }, [autoFetch, fetchCases]);

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setFilter({ search: searchInput });
    void fetchCases();
  };

  const handleStatusChange = (status: CaseStatus | 'all') => {
    setFilter({ status });
    void fetchCases();
  };

  const columns: DataGridColumn<DeskCaseListItem>[] = useMemo(
    () => [
      {
        id: 'tracking_code',
        header: 'کد رهگیری',
        width: 150,
        cell: (item) => (
          <span className="font-mono font-bold text-blue-700 hover:underline">
            {item.tracking_code}
          </span>
        ),
      },
      {
        id: 'service',
        header: 'عنوان خدمت',
        cell: (item) => (
          <div>
            <div className="font-medium text-slate-900">{item.service?.title}</div>
            <div className="text-[10px] text-slate-400">
              گام {item.current_step} از {item.total_steps}
            </div>
          </div>
        ),
      },
      {
        id: 'status',
        header: 'وضعیت',
        width: 160,
        cell: (item) => <StatusPill status={item.status} />,
      },
      {
        id: 'turn_owner',
        header: 'نوبت اقدام',
        width: 140,
        cell: (item) => <TurnOwnerChip owner={item.turn_owner} />,
      },
      {
        id: 'created_at',
        header: 'تاریخ ثبت',
        width: 120,
        cell: (item) => (
          <span className="text-xs text-slate-500 font-mono">
            {new Date(item.created_at).toLocaleDateString('fa-IR')}
          </span>
        ),
      },
    ],
    []
  );

  return (
    <div className="p-6 space-y-6 max-w-7xl mx-auto text-start">
      {/* Page Title & Stats */}
      <div className="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200">
        <div>
          <h1 className="text-lg font-bold text-slate-900 flex items-center gap-2">
            <Layers className="w-5 h-5 text-blue-600" />
            <span>میز کار بررسی و مدیریت پرونده‌ها</span>
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            بررسی پرونده‌های ارجاع‌شده، اصالت‌سنجی مدارک، استعلام دولتی و گردش کار دفاتر پیشخوان
          </p>
        </div>

        <div className="flex items-center gap-2">
          <Button
            variant="secondary"
            size="sm"
            onClick={() => void fetchCases()}
            disabled={loadingList}
          >
            <RefreshCw className={`w-4 h-4 ml-1 ${loadingList ? 'animate-spin' : ''}`} />
            به‌روزرسانی
          </Button>
        </div>
      </div>

      {/* Selected Case Workspace / Inspector */}
      {selectedCaseDetail && (
        <section aria-label="جزئیات پرونده انتخابی" className="mb-6">
          <CaseDetailWorkspace
            caseDetail={selectedCaseDetail}
            onRefresh={() => selectCase(selectedCaseDetail.id)}
            onClose={() => void selectCase(null)}
          />
        </section>
      )}

      {/* Filters and Search Bar */}
      <div className="bg-white border border-slate-200 rounded-xl p-3 flex flex-wrap items-center justify-between gap-3 shadow-2xs">
        <form onSubmit={handleSearchSubmit} className="flex items-center gap-2 flex-1 min-w-[260px]">
          <div className="relative flex-1">
            <Search className="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder="جستجو با کد رهگیری پرونده..."
              className="w-full bg-slate-50 border border-slate-300 rounded-lg pr-9 pl-3 py-1.5 text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-hidden font-mono"
            />
          </div>
          <Button type="submit" size="sm" variant="secondary">
            جستجو
          </Button>
        </form>

        {/* Status Filter Tabs */}
        <div className="flex items-center gap-1 overflow-x-auto text-xs">
          <span className="text-slate-400 text-xs ml-1 flex items-center gap-1">
            <Filter className="w-3.5 h-3.5" /> وضعیت:
          </span>
          {(
            [
              { id: 'all', label: 'همه' },
              { id: 'assigned_to_office', label: 'ارجاع‌شده' },
              { id: 'expert_review', label: 'در حال بررسی' },
              { id: 'action_required', label: 'نیازمند اصلاح' },
              { id: 'government_inquiry', label: 'استعلام دولتی' },
              { id: 'completed', label: 'تکمیل‌شده' },
            ] as const
          ).map((tab) => (
            <button
              key={tab.id}
              type="button"
              onClick={() => handleStatusChange(tab.id as CaseStatus | 'all')}
              className={`px-2.5 py-1 rounded-lg transition-colors whitespace-nowrap ${
                (filter.status || 'all') === tab.id
                  ? 'bg-blue-600 text-white font-semibold shadow-2xs'
                  : 'text-slate-600 hover:bg-slate-100'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      {/* Virtualized Cases DataGrid */}
      <div>
        <DataGrid<DeskCaseListItem>
          data={cases}
          columns={columns}
          keyExtractor={(item) => item.id}
          rowHeight={64}
          maxHeight={520}
          isLoading={loadingList}
          selectedRowKey={selectedCaseId}
          onRowClick={(item) => void selectCase(item.id)}
          emptyMessage="هیچ پرونده‌ای مطابق با فیلترهای انتخابی یافت نشد."
          ariaLabel="فهرست پرونده‌های دفتر پیشخوان"
        />
      </div>
    </div>
  );
};
