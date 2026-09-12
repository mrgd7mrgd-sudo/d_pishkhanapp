import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, AlertCircle } from 'lucide-react';
import { Button } from '@pishkhan/ui-kit';
import type { ServiceDetail, ServiceRequestFormData, ServiceRequestStep } from '../types';
import { useServiceRequestFlow } from '../model/useServiceRequestFlow';
import { StepProgressBar } from './StepProgressBar';
import { StepInfo } from './StepInfo';
import { StepDispatchType } from './StepDispatchType';
import { StepPayment } from './StepPayment';
import { StepSearching } from './StepSearching';
import { StepAssigned } from './StepAssigned';

interface StepContentProps {
  step: ServiceRequestStep;
  service: ServiceDetail;
  formData: ServiceRequestFormData;
  updateFormData: (u: Partial<ServiceRequestFormData>) => void;
  handleUploadDoc: (code: string, title: string, file: File) => Promise<void>;
}

const ActiveStepContent: React.FC<StepContentProps> = ({
  step,
  service,
  formData,
  updateFormData,
  handleUploadDoc,
}) => {
  switch (step) {
    case 'info':
      return <StepInfo service={service} formData={formData} onUpdateFormData={updateFormData} onUploadDoc={handleUploadDoc} />;
    case 'dispatch_type':
      return <StepDispatchType formData={formData} onUpdateFormData={updateFormData} />;
    case 'payment':
      return <StepPayment service={service} formData={formData} onUpdateFormData={updateFormData} />;
    case 'searching':
      return <StepSearching />;
    case 'assigned':
      return <StepAssigned formData={formData} />;
  }
};

const FlowHeader: React.FC<{ showBack: boolean; onBack: () => void }> = ({ showBack, onBack }) => {
  const { t } = useTranslation();
  return (
    <div className="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800 mb-4">
      <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">{t('request.title')}</h2>
      {showBack && (
        <button
          type="button"
          data-testid="header-back-button"
          onClick={onBack}
          className="flex items-center gap-1 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"
        >
          <span>{t('request.back')}</span>
          <ArrowLeft className="w-3.5 h-3.5" aria-hidden="true" />
        </button>
      )}
    </div>
  );
};

const StepFooter: React.FC<{
  step: ServiceRequestStep;
  onBack: () => void;
  onNext: () => void;
  onSubmit: () => void;
}> = ({ step, onBack, onNext, onSubmit }) => {
  const { t } = useTranslation();
  if (step === 'searching' || step === 'assigned') return null;

  return (
    <div className="flex items-center justify-between gap-3 pt-6 mt-6 border-t border-slate-100 dark:border-slate-800">
      <Button type="button" variant="secondary" data-testid="bottom-back-button" onClick={onBack} className="px-5 py-2.5 text-xs font-medium">
        {t('request.back')}
      </Button>
      {step === 'payment' ? (
        <Button
          type="button"
          onClick={onSubmit}
          className="flex-1 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 text-xs rounded-xl shadow-lg shadow-emerald-600/20"
        >
          <span>{t('request.submit')}</span>
        </Button>
      ) : (
        <Button
          type="button"
          onClick={onNext}
          className="flex-1 flex items-center justify-center gap-2 bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 text-xs rounded-xl shadow-lg shadow-sky-600/20"
        >
          <span>{t('request.next')}</span>
          <ArrowLeft className="w-4 h-4" aria-hidden="true" />
        </Button>
      )}
    </div>
  );
};

export const ServiceRequestFlow: React.FC = () => {
  const { serviceId } = useParams<{ serviceId: string }>();
  const navigate = useNavigate();
  const { t } = useTranslation();

  const {
    step,
    setStep,
    service,
    isLoadingService,
    errorMessage,
    setErrorMessage,
    formData,
    updateFormData,
    handleUploadDoc,
    handleNext,
    handleSubmitPayment,
  } = useServiceRequestFlow(serviceId);

  const handleBack = (): void => {
    setErrorMessage(null);
    if (step === 'dispatch_type') setStep('info');
    else if (step === 'payment') setStep('dispatch_type');
    else if (step === 'info') navigate('/services');
  };

  if (isLoadingService) {
    return <div className="p-8 text-center text-xs text-slate-500">{t('request.loading_service')}</div>;
  }
  if (!service) {
    return <div className="p-8 text-center text-sm font-bold text-rose-600">{t('request.service_not_found')}</div>;
  }

  return (
    <div className="max-w-xl mx-auto px-4 py-6">
      <FlowHeader showBack={step !== 'assigned'} onBack={handleBack} />
      <StepProgressBar currentStep={step} />

      {errorMessage && (
        <div role="alert" className="flex items-center gap-2 p-3 mb-4 text-xs font-medium text-rose-700 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl">
          <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" aria-hidden="true" />
          <span>{errorMessage}</span>
        </div>
      )}

      <ActiveStepContent
        step={step}
        service={service}
        formData={formData}
        updateFormData={updateFormData}
        handleUploadDoc={handleUploadDoc}
      />

      <StepFooter
        step={step}
        onBack={handleBack}
        onNext={handleNext}
        onSubmit={() => void handleSubmitPayment()}
      />
    </div>
  );
};
