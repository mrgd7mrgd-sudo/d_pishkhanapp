import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Sparkles, Building, CheckCircle } from 'lucide-react';
import type { DispatchMode, OfficeOption, ServiceRequestFormData } from '../types';
import { serviceRequestApi } from '../api/serviceRequestApi';

interface StepDispatchTypeProps {
  formData: ServiceRequestFormData;
  onUpdateFormData: (updates: Partial<ServiceRequestFormData>) => void;
}

const DispatchCards: React.FC<{
  selected: DispatchMode;
  onSelect: (mode: DispatchMode) => void;
}> = ({ selected, onSelect }) => {
  const { t } = useTranslation();
  return (
    <div className="grid grid-cols-1 gap-3.5">
      <button
        type="button"
        onClick={() => onSelect('auto')}
        className={`p-4 rounded-2xl border text-start transition-all ${
          selected === 'auto'
            ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30 ring-2 ring-emerald-500/20'
            : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50'
        }`}
      >
        <div className="flex items-start gap-3">
          <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 shrink-0">
            <Sparkles className="w-5 h-5" aria-hidden="true" />
          </div>
          <div className="flex-1 space-y-1">
            <div className="flex items-center justify-between">
              <span className="text-sm font-bold text-slate-900 dark:text-slate-100">{t('request.dispatch_auto')}</span>
              {selected === 'auto' && <CheckCircle className="w-5 h-5 text-emerald-600" aria-hidden="true" />}
            </div>
            <p className="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{t('request.dispatch_auto_desc')}</p>
          </div>
        </div>
      </button>

      <button
        type="button"
        onClick={() => onSelect('manual')}
        className={`p-4 rounded-2xl border text-start transition-all ${
          selected === 'manual'
            ? 'border-sky-500 bg-sky-50/50 dark:bg-sky-950/30 ring-2 ring-sky-500/20'
            : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50'
        }`}
      >
        <div className="flex items-start gap-3">
          <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-900/50 text-sky-600 shrink-0">
            <Building className="w-5 h-5" aria-hidden="true" />
          </div>
          <div className="flex-1 space-y-1">
            <div className="flex items-center justify-between">
              <span className="text-sm font-bold text-slate-900 dark:text-slate-100">{t('request.dispatch_manual')}</span>
              {selected === 'manual' && <CheckCircle className="w-5 h-5 text-sky-600" aria-hidden="true" />}
            </div>
            <p className="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{t('request.dispatch_manual_desc')}</p>
          </div>
        </div>
      </button>
    </div>
  );
};

const OfficeSelector: React.FC<{
  offices: OfficeOption[];
  isLoading: boolean;
  selectedOfficeId?: string | undefined;
  onSelectOffice: (id: string) => void;
}> = ({ offices, isLoading, selectedOfficeId, onSelectOffice }) => {
  const { t } = useTranslation();
  return (
    <div className="p-4 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3">
      <label htmlFor="office-select" className="text-xs font-bold text-slate-800 dark:text-slate-200 block">
        {t('request.select_office')}
      </label>
      {isLoading ? (
        <div className="text-xs text-slate-500 py-2">{t('request.loading_offices')}</div>
      ) : offices.length > 0 ? (
        <select
          id="office-select"
          value={selectedOfficeId || ''}
          onChange={(e) => onSelectOffice(e.target.value)}
          className="w-full p-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500 focus:outline-none"
        >
          <option value="">{t('request.choose_office_placeholder')}</option>
          {offices.map((off) => (
            <option key={off.id} value={off.id}>
              {off.name} ({off.code}) - {t('request.rating')}: {off.rating}
            </option>
          ))}
        </select>
      ) : (
        <div className="text-xs text-slate-500">{t('request.no_offices')}</div>
      )}
    </div>
  );
};

export const StepDispatchType: React.FC<StepDispatchTypeProps> = ({ formData, onUpdateFormData }) => {
  const { t } = useTranslation();
  const [offices, setOffices] = useState<OfficeOption[]>([]);
  const [isLoadingOffices, setIsLoadingOffices] = useState(false);

  useEffect(() => {
    let isMounted = true;
    const load = async (): Promise<void> => {
      setIsLoadingOffices(true);
      try {
        const list = await serviceRequestApi.getOffices();
        if (isMounted) setOffices(list);
      } finally {
        if (isMounted) setIsLoadingOffices(false);
      }
    };
    void load();
    return () => {
      isMounted = false;
    };
  }, []);

  return (
    <div className="space-y-5">
      <div>
        <h3 className="text-base font-bold text-slate-900 dark:text-slate-100 mb-1">{t('request.step_dispatch')}</h3>
        <p className="text-xs text-slate-500 dark:text-slate-400">{t('request.dispatch_subtitle')}</p>
      </div>
      <DispatchCards
        selected={formData.dispatchMode}
        onSelect={(mode) => onUpdateFormData({ dispatchMode: mode, officeId: mode === 'auto' ? undefined : formData.officeId })}
      />
      {formData.dispatchMode === 'manual' && (
        <OfficeSelector
          offices={offices}
          isLoading={isLoadingOffices}
          selectedOfficeId={formData.officeId}
          onSelectOffice={(id) => onUpdateFormData({ officeId: id })}
        />
      )}
    </div>
  );
};
