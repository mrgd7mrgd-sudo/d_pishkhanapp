import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { Building2, Info, Layers, Users, Bell, ShieldAlert, RefreshCw } from 'lucide-react';
import { Button } from '@pishkhan/ui-kit';
import { useOperatorAuthStore } from '@/features/auth/model/useOperatorAuthStore';
import { officeProfileApi } from '../api/officeProfileApi';
import { InfoSubTab } from '../components/InfoSubTab';
import { ServicesSubTab } from '../components/ServicesSubTab';
import { ReceptionSubTab } from '../components/ReceptionSubTab';
import { AnnouncementsSubTab } from '../components/AnnouncementsSubTab';
import type { ProfileSubTab } from '../types';

export const OfficeProfilePage: React.FC = () => {
  const [activeTab, setActiveTab] = useState<ProfileSubTab>('info');
  const [feedback, setFeedback] = useState<string | null>(null);

  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const operator = useOperatorAuthStore((state) => state.operator);
  const isManager = operator?.role === 'manager';

  const { data: profile, isLoading, refetch } = useQuery({
    queryKey: ['desk', 'profile'],
    queryFn: () => officeProfileApi.fetchProfile(),
    enabled: isManager,
  });

  const updateInfoMutation = useMutation({
    mutationFn: officeProfileApi.updateInfo,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'profile'] });
    },
  });

  const updateSpecialtiesMutation = useMutation({
    mutationFn: officeProfileApi.updateSpecialties,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'profile'] });
    },
  });

  const updateCoveragesMutation = useMutation({
    mutationFn: officeProfileApi.updateCoverages,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'profile'] });
    },
  });

  const createAnnouncementMutation = useMutation({
    mutationFn: officeProfileApi.createAnnouncement,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'profile'] });
    },
  });

  const deleteAnnouncementMutation = useMutation({
    mutationFn: officeProfileApi.deleteAnnouncement,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'profile'] });
    },
  });

  const createOperatorMutation = useMutation({
    mutationFn: officeProfileApi.createOperator,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'profile'] });
    },
  });

  const toggleOperatorMutation = useMutation({
    mutationFn: officeProfileApi.toggleOperator,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'profile'] });
    },
  });

  if (!isManager) {
    return (
      <main
        className="min-h-[70vh] flex flex-col items-center justify-center p-6 text-center"
        data-testid="profile-access-denied"
        role="alert"
      >
        <div className="w-16 h-16 rounded-full bg-rose-100 dark:bg-rose-950/50 text-rose-600 flex items-center justify-center mb-4">
          <ShieldAlert className="w-8 h-8" aria-hidden="true" />
        </div>
        <h1 className="text-xl font-bold text-slate-900 dark:text-white mb-2">
          دسترسی غیرمجاز
        </h1>
        <p className="text-sm text-slate-600 dark:text-slate-400 max-w-md mb-6">
          این بخش فقط مختص مدیر دفتر است و اپراتور عادی به آن دسترسی ندارد.
        </p>
        <Button variant="secondary" onClick={() => navigate('/workspace')} data-testid="return-to-workspace-btn">
          بازگشت به میز کار
        </Button>
      </main>
    );
  }

  return (
    <div className="space-y-6 max-w-6xl mx-auto p-4 sm:p-6" data-testid="office-profile-page">
      <header className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <Building2 className="w-6 h-6 text-blue-600" aria-hidden="true" />
            <span>پروفایل دفتر، خدمات و ظرفیت پذیرش</span>
          </h1>
          <p className="text-xs sm:text-sm text-slate-500 mt-1">
            {profile ? `${profile.office.name} (کد: ${profile.office.code}) — ` : ''}
            مدیریت اطلاعات عمومی، تخصص‌ها، پوشش دسته‌ها، تابلوی اعلانات و باجه‌های پذیرش
          </p>
        </div>

        <Button
          variant="secondary"
          size="sm"
          onClick={() => refetch()}
          disabled={isLoading}
          data-testid="profile-refresh-btn"
        >
          <RefreshCw className={`w-4 h-4 me-1.5 ${isLoading ? 'animate-spin' : ''}`} aria-hidden="true" />
          <span>به‌روزرسانی</span>
        </Button>
      </header>

      {feedback && (
        <div role="status" aria-live="polite" className="p-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs">
          {feedback}
        </div>
      )}

      {/* Subtabs Bar */}
      <nav role="tablist" aria-label="بخش‌های پروفایل دفتر" className="flex border-b border-slate-200 dark:border-slate-800 overflow-x-auto">
        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'info'}
          data-testid="subtab-info"
          onClick={() => setActiveTab('info')}
          className={`flex items-center gap-2 px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-colors ${
            activeTab === 'info'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
          }`}
        >
          <Info className="w-4 h-4" aria-hidden="true" />
          <span>اطلاعات پایه دفتر</span>
        </button>

        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'services'}
          data-testid="subtab-services"
          onClick={() => setActiveTab('services')}
          className={`flex items-center gap-2 px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-colors ${
            activeTab === 'services'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
          }`}
        >
          <Layers className="w-4 h-4" aria-hidden="true" />
          <span>پوشش خدمات و تخصص‌ها</span>
        </button>

        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'reception'}
          data-testid="subtab-reception"
          onClick={() => setActiveTab('reception')}
          className={`flex items-center gap-2 px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-colors ${
            activeTab === 'reception'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
          }`}
        >
          <Users className="w-4 h-4" aria-hidden="true" />
          <span>پذیرش و باجه‌های اپراتور</span>
        </button>

        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'announcements'}
          data-testid="subtab-announcements"
          onClick={() => setActiveTab('announcements')}
          className={`flex items-center gap-2 px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-colors ${
            activeTab === 'announcements'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
          }`}
        >
          <Bell className="w-4 h-4" aria-hidden="true" />
          <span>تابلوی اعلانات</span>
        </button>
      </nav>

      {/* Content */}
      <main>
        {isLoading || !profile ? (
          <div className="text-center py-12 text-sm text-slate-500">
            در حال دریافت اطلاعات و تنظیمات پروفایل دفتر...
          </div>
        ) : (
          <>
            {activeTab === 'info' && (
              <InfoSubTab
                office={profile.office}
                onUpdate={async (payload) => {
                  await updateInfoMutation.mutateAsync(payload);
                }}
                isUpdating={updateInfoMutation.isPending}
              />
            )}
            {activeTab === 'services' && (
              <ServicesSubTab
                specialties={profile.specialties}
                coverages={profile.coverages}
                availableCategories={profile.available_categories}
                onUpdateSpecialties={async (specs) => {
                  await updateSpecialtiesMutation.mutateAsync(specs);
                }}
                onUpdateCoverages={async (covs) => {
                  await updateCoveragesMutation.mutateAsync(covs);
                }}
                isUpdating={
                  updateSpecialtiesMutation.isPending || updateCoveragesMutation.isPending
                }
              />
            )}
            {activeTab === 'reception' && (
              <ReceptionSubTab
                operators={profile.operators}
                onCreateOperator={async (payload) => {
                  await createOperatorMutation.mutateAsync(payload);
                }}
                onToggleOperator={async (id) => {
                  await toggleOperatorMutation.mutateAsync(id);
                }}
                isProcessing={
                  createOperatorMutation.isPending || toggleOperatorMutation.isPending
                }
              />
            )}
            {activeTab === 'announcements' && (
              <AnnouncementsSubTab
                announcements={profile.announcements}
                onCreateAnnouncement={async (payload) => {
                  await createAnnouncementMutation.mutateAsync(payload);
                }}
                onDeleteAnnouncement={async (id) => {
                  await deleteAnnouncementMutation.mutateAsync(id);
                }}
                isProcessing={
                  createAnnouncementMutation.isPending ||
                  deleteAnnouncementMutation.isPending
                }
              />
            )}
          </>
        )}
      </main>
    </div>
  );
};
