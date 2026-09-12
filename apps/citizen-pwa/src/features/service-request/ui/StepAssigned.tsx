import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { CheckCircle2, Copy, Check, ArrowLeft } from 'lucide-react';
import { Button } from '@pishkhan/ui-kit';
import type { ServiceRequestFormData } from '../types';

interface StepAssignedProps {
  formData: ServiceRequestFormData;
}

const TrackingCodeBox: React.FC<{ code: string }> = ({ code }) => {
  const { t } = useTranslation();
  const [copied, setCopied] = useState(false);

  const handleCopy = async (): Promise<void> => {
    try {
      await navigator.clipboard.writeText(code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      // Fallback
    }
  };

  return (
    <div className="w-full max-w-xs p-4 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-2">
      <span className="text-xs text-slate-500 block">{t('request.tracking_code_label')}</span>
      <div className="flex items-center justify-center gap-2">
        <span dir="ltr" className="text-lg font-mono font-bold tracking-wider text-slate-900 dark:text-slate-100" data-testid="tracking-code-value">
          {code}
        </span>
        <button
          type="button"
          onClick={() => void handleCopy()}
          aria-label={t('request.copy_tracking_code')}
          className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-200/60 dark:hover:bg-slate-800"
        >
          {copied ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
        </button>
      </div>
    </div>
  );
};

export const StepAssigned: React.FC<StepAssignedProps> = ({ formData }) => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const trackingCode = formData.createdCase?.trackingCode || 'CR-1405-DEMO';

  return (
    <div className="flex flex-col items-center justify-center py-8 px-4 text-center space-y-6">
      <div className="flex items-center justify-center w-20 h-20 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 shadow-xl shadow-emerald-600/20">
        <CheckCircle2 className="w-10 h-10" aria-hidden="true" />
      </div>
      <div className="space-y-2 max-w-sm">
        <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">{t('request.assigned_title')}</h3>
        <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{t('request.assigned_desc')}</p>
      </div>
      <TrackingCodeBox code={trackingCode} />
      <div className="w-full max-w-xs pt-4">
        <Button
          type="button"
          onClick={() => navigate(`/cases/${trackingCode}`)}
          className="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-emerald-600/20"
        >
          <span>{t('request.track_case')}</span>
          <ArrowLeft className="w-4 h-4" aria-hidden="true" />
        </Button>
      </div>
    </div>
  );
};
