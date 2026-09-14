import { create } from 'zustand';
import { DeskCaseListItem, DeskCaseDetail, DeskCasesQueryFilter } from '../types';
import { workspaceApi } from '../api/workspaceApi';

interface WorkspaceState {
  cases: DeskCaseListItem[];
  selectedCaseId: string | null;
  selectedCaseDetail: DeskCaseDetail | null;
  loadingList: boolean;
  loadingDetail: boolean;
  actionLoading: boolean;
  filter: DeskCasesQueryFilter;
  error: string | null;

  // Actions
  setFilter: (filter: Partial<DeskCasesQueryFilter>) => void;
  fetchCases: () => Promise<void>;
  selectCase: (caseId: string | null) => Promise<void>;
  updateCaseInList: (updated: Partial<DeskCaseListItem> & { id: string }) => void;
  reset: () => void;
}

export const useWorkspaceStore = create<WorkspaceState>((set, get) => ({
  cases: [],
  selectedCaseId: null,
  selectedCaseDetail: null,
  loadingList: false,
  loadingDetail: false,
  actionLoading: false,
  filter: { status: 'all', search: '', per_page: 50 },
  error: null,

  setFilter: (newFilter) => {
    set((state) => ({
      filter: { ...state.filter, ...newFilter },
    }));
  },

  fetchCases: async () => {
    set({ loadingList: true, error: null });
    try {
      const res = await workspaceApi.fetchCases(get().filter);
      set({ cases: Array.isArray(res.data) ? res.data : [], loadingList: false });
    } catch (err) {
      const msg = err instanceof Error ? err.message : 'خطا در بارگذاری پرونده‌ها';
      set({ cases: [], loadingList: false, error: msg });
    }
  },

  selectCase: async (caseId) => {
    if (!caseId) {
      set({ selectedCaseId: null, selectedCaseDetail: null });
      return;
    }

    set({ selectedCaseId: caseId, loadingDetail: true, error: null });
    try {
      const detail = await workspaceApi.fetchCaseDetail(caseId);
      set({ selectedCaseDetail: detail, loadingDetail: false });
    } catch (err) {
      const msg = err instanceof Error ? err.message : 'خطا در بارگذاری جزئیات پرونده';
      set({ selectedCaseDetail: null, loadingDetail: false, error: msg });
    }
  },

  updateCaseInList: (updated) => {
    set((state) => ({
      cases: state.cases.map((c) => (c.id === updated.id ? { ...c, ...updated } : c)),
      selectedCaseDetail:
        state.selectedCaseDetail?.id === updated.id
          ? ({ ...state.selectedCaseDetail, ...updated } as DeskCaseDetail)
          : state.selectedCaseDetail,
    }));
  },

  reset: () => {
    set({
      cases: [],
      selectedCaseId: null,
      selectedCaseDetail: null,
      loadingList: false,
      loadingDetail: false,
      actionLoading: false,
      error: null,
    });
  },
}));
