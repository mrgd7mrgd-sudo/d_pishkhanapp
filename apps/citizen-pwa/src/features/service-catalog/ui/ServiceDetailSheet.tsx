import React from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { X, Clock, Building2, FileCheck2, AlertCircle, ArrowLeft } from 'lucide-react';
import { ServiceTagBadge, Button } from '@pishkhan/ui-kit';
import type { ServiceItem } from '../types';

export interface ServiceDetailSheetProps {
  service: ServiceItem | null;
  onClose: () => void;
  onRequest?: (service: ServiceItem) => void;
}

interface HeaderProps {
  service: ServiceItem;
  onClose: () => void;
}

const SheetHeader: React.FC<HeaderProps> = ({ service, onClose }) => {
  const { t } = useTranslation();

  return (
    <div className="flex items-start justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
      <div className="flex-1">
        <div className="flex items-center gap-2 mb-1.5">
          <span className="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
            {service.category.title}
          </span>
          <div className="flex gap-1">
            {service.tags.map((tag) => (
              <ServiceTagBadge key={tag} tag={tag} size="sm" />
            ))}
          </div>
        </div>
        <h2
          id="service-detail-title"
          className="text-lg font-bold text-slate-900 dark:text-slate-100"
        >
          {service.title}
        </h2>
        <div className="flex items-center gap-1 mt-1 text-xs text-slate-500">
          <Building2 className="w-3.5 h-3.5 text-slate-400" aria-hidden="true" />
          <span>{service.department}</span>
        </div>
      </div>
      <button
        type="button"
        onClick={onClose}
        aria-label={t('catalog.close')}
        className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800"
      >
        <X className="w-5 h-5" />
      </button>
    </div>
  );
};

const SheetSpecs: React.FC<{ service: ServiceItem }> = ({ service }) => {
  const { t } = useTranslation();
  const formattedFee =
    service.fee_rials > 0
      ? `${service.fee_rials.toLocaleString('fa-IR')} ${t('catalog.rials')}`
      : t('catalog.free_service');

  return (
    <div className="grid grid-cols-2 gap-3 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-700/60">
      <div>
        <span className="block text-xs text-slate-400 mb-0.5">{t('catalog.fee')}</span>
        <span className="text-sm font-bold text-emerald-600 dark:text-emerald-400 font-mono">
          {formattedFee}
        </span>
      </div>
      <div>
        <span className="block text-xs text-slate-400 mb-0.5">{t('catalog.estimated_time')}</span>
        <div className="flex items-center gap-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
          <Clock className="w-3.5 h-3.5 text-slate-400" aria-hidden="true" />
          <span>{service.estimated_days.label}</span>
        </div>
      </div>
    </div>
  );
};

const SheetDocList: React.FC<{ service: ServiceItem }> = ({ service }) => {
  const { t } = useTranslation();
  if (service.required_documents.length === 0) return null;

  return (
    <div>
      <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100 mb-2.5 flex items-center gap-1.5">
        <FileCheck2 className="w-4 h-4 text-emerald-500" aria-hidden="true" />
        <span>{t('catalog.required_documents')}</span>
      </h3>
      <ul className="space-y-2">
        {service.required_documents.map((doc) => (
          <li
            key={doc.code}
            className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs"
          >
            <span className="font-medium text-slate-800 dark:text-slate-200">{doc.title}</span>
            <span
              className={`px-2 py-0.5 rounded-md font-semibold ${
                doc.is_mandatory
                  ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400'
                  : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
              }`}
            >
              {doc.is_mandatory ? t('catalog.mandatory') : t('catalog.optional')}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
};

const SheetRequirements: React.FC<{ service: ServiceItem }> = ({ service }) => {
  const { t } = useTranslation();
  if (service.requirements.length === 0) return null;

  return (
    <div>
      <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100 mb-2 flex items-center gap-1.5">
        <AlertCircle className="w-4 h-4 text-amber-500" aria-hidden="true" />
        <span>{t('catalog.requirements')}</span>
      </h3>
      <ul className="list-disc list-inside space-y-1 text-xs text-slate-600 dark:text-slate-400">
        {service.requirements.map((req, idx) => (
          <li key={idx}>{req}</li>
        ))}
      </ul>
    </div>
  );
};

export const ServiceDetailSheet: React.FC<ServiceDetailSheetProps> = ({
  service,
  onClose,
  onRequest,
}) => {
  const { t } = useTranslation();
  const navigate = useNavigate();

  if (!service) return null;

  const handleRequestClick = () => {
    if (onRequest) {
      onRequest(service);
    } else {
      navigate(`/request/${service.id}`);
    }
  };

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="service-detail-title"
      className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200"
      onClick={onClose}
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className="relative w-full max-w-lg max-h-[85vh] overflow-y-auto bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 sm:p-6"
      >
        <SheetHeader service={service} onClose={onClose} />

        <div className="py-4 space-y-5">
          <p className="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
            {service.description}
          </p>
          <SheetSpecs service={service} />
          <SheetDocList service={service} />
          <SheetRequirements service={service} />
        </div>

        <div className="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center gap-3">
          <Button
            type="button"
            onClick={handleRequestClick}
            className="flex-1 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-emerald-600/20"
          >
            <span>{t('catalog.request_service')}</span>
            <ArrowLeft className="w-4 h-4" aria-hidden="true" />
          </Button>
        </div>
      </div>
    </div>
  );
};
