import React from 'react';
import { useTranslation } from 'react-i18next';
import { FileDropzone } from '@pishkhan/ui-kit';
import { FileText, ShieldCheck, MapPin, Truck, Mail } from 'lucide-react';
import type { DeliveryPreference, ServiceDetail, ServiceRequestFormData } from '../types';

interface StepInfoProps {
  service: ServiceDetail;
  formData: ServiceRequestFormData;
  onUpdateFormData: (updates: Partial<ServiceRequestFormData>) => void;
  onUploadDoc: (docTypeCode: string, title: string, file: File) => Promise<void>;
}

const DeliveryPreferenceSelector: React.FC<{
  selected: DeliveryPreference;
  onSelect: (pref: DeliveryPreference) => void;
}> = ({ selected, onSelect }) => {
  const { t } = useTranslation();
  const options: Array<{ id: DeliveryPreference; labelKey: string; icon: typeof MapPin }> = [
    { id: 'in_person', labelKey: 'request.delivery_in_person', icon: MapPin },
    { id: 'courier', labelKey: 'request.delivery_courier', icon: Truck },
    { id: 'post', labelKey: 'request.delivery_post', icon: Mail },
  ];

  return (
    <div className="space-y-3">
      <label className="text-sm font-bold text-slate-900 dark:text-slate-100 block">
        {t('request.delivery_preference')}
      </label>
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
        {options.map(({ id, labelKey, icon: Icon }) => (
          <button
            key={id}
            type="button"
            onClick={() => onSelect(id)}
            className={`flex items-center gap-2.5 p-3 rounded-xl border text-xs font-medium transition-all ${
              selected === id
                ? 'border-sky-500 bg-sky-50/60 dark:bg-sky-950/40 text-sky-900 dark:text-sky-200 ring-2 ring-sky-500/20'
                : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60'
            }`}
          >
            <Icon className="w-4 h-4 text-sky-600 shrink-0" aria-hidden="true" />
            <span>{t(labelKey)}</span>
          </button>
        ))}
      </div>
    </div>
  );
};

const CommitmentSignatureCard: React.FC<{
  signed: boolean;
  onToggle: (checked: boolean) => void;
}> = ({ signed, onToggle }) => {
  const { t } = useTranslation();
  return (
    <div className="p-4 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl">
      <label className="flex items-start gap-3 cursor-pointer select-none">
        <input
          type="checkbox"
          checked={signed}
          onChange={(e) => onToggle(e.target.checked)}
          id="commitment-checkbox"
          className="mt-1 w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500"
        />
        <div className="space-y-1">
          <span className="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
            <ShieldCheck className="w-4 h-4 text-emerald-600" aria-hidden="true" />
            <span>{t('request.commitment_title')}</span>
          </span>
          <p className="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
            {t('request.commitment_text')}
          </p>
        </div>
      </label>
    </div>
  );
};

const RequiredDocsSection: React.FC<{
  requiredDocs: NonNullable<ServiceDetail['required_docs']>;
  formData: ServiceRequestFormData;
  onUpdateFormData: (updates: Partial<ServiceRequestFormData>) => void;
  onUploadDoc: (docTypeCode: string, title: string, file: File) => Promise<void>;
}> = ({ requiredDocs, formData, onUpdateFormData, onUploadDoc }) => {
  const { t } = useTranslation();
  return (
    <div className="space-y-4">
      <h4 className="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
        <FileText className="w-4 h-4 text-sky-600" aria-hidden="true" />
        <span>{t('catalog.required_documents')}</span>
      </h4>
      {requiredDocs.map((doc) => {
        const uploaded = formData.documents[doc.document_type_code];
        return (
          <div key={doc.document_type_code} className="p-3 bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl">
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs font-semibold text-slate-800 dark:text-slate-200">{doc.title}</span>
              {doc.is_mandatory && (
                <span className="text-[10px] bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400 px-2 py-0.5 rounded-full font-medium">
                  {t('catalog.mandatory')}
                </span>
              )}
            </div>
            <FileDropzone
              label={`${t('request.select_doc_prefix')} ${doc.title}`}
              selectedFileName={uploaded?.fileName}
              isUploading={uploaded?.isUploading}
              uploadProgress={uploaded?.uploadProgress}
              error={uploaded?.error}
              onFileSelect={(file) => void onUploadDoc(doc.document_type_code, doc.title, file)}
              onClear={() => {
                const copy = { ...formData.documents };
                delete copy[doc.document_type_code];
                onUpdateFormData({ documents: copy });
              }}
            />
          </div>
        );
      })}
    </div>
  );
};

export const StepInfo: React.FC<StepInfoProps> = ({
  service,
  formData,
  onUpdateFormData,
  onUploadDoc,
}) => {
  const { t } = useTranslation();
  const requiredDocs = service.required_docs && service.required_docs.length > 0
    ? service.required_docs
    : [{ document_type_code: 'national_card', title: 'تصویر کارت ملی', is_mandatory: true }];

  return (
    <div className="space-y-6">
      <div className="p-4 bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl">
        <h3 className="text-base font-bold text-slate-900 dark:text-slate-100 mb-1">{service.title}</h3>
        <p className="text-xs text-slate-600 dark:text-slate-400 mb-2">{service.description}</p>
        <div className="text-xs font-semibold text-emerald-700 dark:text-emerald-400">
          {t('request.service_fee', { amount: service.fee_rials.toLocaleString('fa-IR') })}
        </div>
      </div>

      <RequiredDocsSection
        requiredDocs={requiredDocs}
        formData={formData}
        onUpdateFormData={onUpdateFormData}
        onUploadDoc={onUploadDoc}
      />

      <DeliveryPreferenceSelector
        selected={formData.deliveryPreference}
        onSelect={(pref) => onUpdateFormData({ deliveryPreference: pref })}
      />

      <CommitmentSignatureCard
        signed={formData.commitmentSigned}
        onToggle={(checked) => onUpdateFormData({ commitmentSigned: checked })}
      />
    </div>
  );
};
