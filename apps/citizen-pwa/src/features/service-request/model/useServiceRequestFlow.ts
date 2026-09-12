import { useEffect, useState, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import type { ServiceDetail, ServiceRequestFormData, ServiceRequestStep } from '../types';
import { serviceRequestApi } from '../api/serviceRequestApi';

export const useServiceDataLoader = (serviceId: string | undefined) => {
  const { t } = useTranslation();
  const [service, setService] = useState<ServiceDetail | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;
    if (!serviceId) return;
    const load = async (): Promise<void> => {
      setIsLoading(true);
      try {
        const data = await serviceRequestApi.getService(serviceId);
        if (isMounted) setService(data);
      } catch (err: unknown) {
        if (isMounted) setLoadError(err instanceof Error ? err.message : t('request.service_not_found'));
      } finally {
        if (isMounted) setIsLoading(false);
      }
    };
    void load();
    return () => {
      isMounted = false;
    };
  }, [serviceId, t]);

  return { service, isLoading, loadError };
};

export const validateStepTransition = (
  step: ServiceRequestStep,
  service: ServiceDetail | null,
  formData: ServiceRequestFormData,
  t: (k: string) => string
): { nextStep?: ServiceRequestStep; error?: string } => {
  if (step === 'info') {
    const requiredDocs = service?.required_docs?.filter((d) => d.is_mandatory) || [];
    const missingDoc = requiredDocs.find((d) => !formData.documents[d.document_type_code]?.uploadId);
    if (missingDoc) return { error: `بارگذاری مدرک "${missingDoc.title}" الزامی است.` };
    if (!formData.commitmentSigned) return { error: t('request.commitment_required') };
    return { nextStep: 'dispatch_type' };
  }
  if (step === 'dispatch_type') {
    if (formData.dispatchMode === 'manual' && !formData.officeId) {
      return { error: t('request.select_office') };
    }
    return { nextStep: 'payment' };
  }
  return {};
};

export const useServiceDocUploader = (
  setFormData: React.Dispatch<React.SetStateAction<ServiceRequestFormData>>,
  t: (k: string) => string
) => {
  const uploadDoc = async (docTypeCode: string, title: string, file: File): Promise<void> => {
    setFormData((prev) => ({
      ...prev,
      documents: {
        ...prev.documents,
        [docTypeCode]: { docTypeCode, docTitle: title, uploadId: '', fileName: file.name, isUploading: true, uploadProgress: 10 },
      },
    }));
    try {
      const uploadId = await serviceRequestApi.uploadDocument(file, (progress) => {
        setFormData((prev) => ({
          ...prev,
          documents: { ...prev.documents, [docTypeCode]: { ...prev.documents[docTypeCode]!, uploadProgress: progress } },
        }));
      });
      setFormData((prev) => ({
        ...prev,
        documents: { ...prev.documents, [docTypeCode]: { ...prev.documents[docTypeCode]!, uploadId, isUploading: false, uploadProgress: 100 } },
      }));
    } catch (err: unknown) {
      setFormData((prev) => ({
        ...prev,
        documents: { ...prev.documents, [docTypeCode]: { ...prev.documents[docTypeCode]!, isUploading: false, error: err instanceof Error ? err.message : t('request.upload_failed') } },
      }));
    }
  };
  return { uploadDoc };
};

export const useServiceRequestFlow = (serviceId: string | undefined) => {
  const { t } = useTranslation();
  const { service, isLoading: isLoadingService, loadError } = useServiceDataLoader(serviceId);
  const [step, setStep] = useState<ServiceRequestStep>('info');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [formData, setFormData] = useState<ServiceRequestFormData>({
    serviceId: serviceId || '',
    dispatchMode: 'auto',
    paymentMethod: 'wallet',
    deliveryPreference: 'in_person',
    commitmentSigned: false,
    documents: {},
  });

  const updateFormData = useCallback((updates: Partial<ServiceRequestFormData>): void => {
    setFormData((prev) => ({ ...prev, ...updates }));
  }, []);

  const { uploadDoc } = useServiceDocUploader(setFormData, t);

  const handleNext = (): void => {
    setErrorMessage(null);
    const result = validateStepTransition(step, service, formData, t);
    if (result.error) setErrorMessage(result.error);
    else if (result.nextStep) setStep(result.nextStep);
  };

  const handleSubmitPayment = async (): Promise<void> => {
    setErrorMessage(null);
    setStep('searching');
    try {
      const res = await serviceRequestApi.submitCase(formData);
      updateFormData({ createdCase: { id: res.id, trackingCode: res.tracking_code, status: res.status } });
      setStep('assigned');
    } catch (err: unknown) {
      setStep('payment');
      setErrorMessage(err instanceof Error ? err.message : t('request.error_submitting'));
    }
  };

  return {
    step,
    setStep,
    service,
    isLoadingService,
    errorMessage: errorMessage || loadError,
    setErrorMessage,
    formData,
    updateFormData,
    handleUploadDoc: uploadDoc,
    handleNext,
    handleSubmitPayment,
  };
};
