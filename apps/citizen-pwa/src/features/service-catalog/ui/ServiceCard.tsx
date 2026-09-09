import React from 'react';
import { useTranslation } from 'react-i18next';
import { Clock, Building2, Flame, Sparkles } from 'lucide-react';
import { ServiceTagBadge, ResponsiveImage } from '@pishkhan/ui-kit';
import type { ServiceItem } from '../types';

export interface ServiceCardProps {
  service: ServiceItem;
  onSelect: (service: ServiceItem) => void;
}

const ServiceCardCover: React.FC<{ service: ServiceItem }> = ({ service }) => {
  const { t } = useTranslation();

  return (
    <div className="relative w-full aspect-16/9 bg-slate-100 dark:bg-slate-800 overflow-hidden">
      <ResponsiveImage
        src={service.image.webp}
        alt={service.title}
        width={service.image.width}
        height={service.image.height}
        avifSrcSet={service.image.avif}
        webpSrcSet={service.image.webp}
        blurhash={service.image.blurhash}
        loading="lazy"
        decoding="async"
        imageClassName="group-hover:scale-105 transition-transform duration-300"
      />

      <div className="absolute top-2.5 start-2.5 flex flex-wrap gap-1.5 pointer-events-none">
        {service.is_popular && (
          <span className="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-500 text-white shadow-sm">
            <Flame className="w-3 h-3" aria-hidden="true" />
            <span>{t('catalog.popular_badge')}</span>
          </span>
        )}
        {service.is_new && (
          <span className="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-500 text-white shadow-sm">
            <Sparkles className="w-3 h-3" aria-hidden="true" />
            <span>{t('catalog.new_badge')}</span>
          </span>
        )}
      </div>
    </div>
  );
};

const ServiceCardFooter: React.FC<{ service: ServiceItem }> = ({ service }) => {
  const { t } = useTranslation();

  const formattedFee =
    service.fee_rials > 0
      ? `${service.fee_rials.toLocaleString('fa-IR')} ${t('catalog.rials')}`
      : t('catalog.free_service');

  return (
    <>
      <div className="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
        <div className="flex items-center gap-1">
          <Building2 className="w-3.5 h-3.5 text-slate-400" aria-hidden="true" />
          <span className="truncate max-w-[120px]">{service.department}</span>
        </div>
        <div className="flex items-center gap-1">
          <Clock className="w-3.5 h-3.5 text-slate-400" aria-hidden="true" />
          <span>{service.estimated_days.label}</span>
        </div>
      </div>

      <div className="mt-3 flex items-center justify-between pt-2">
        <span className="text-xs text-slate-400">{t('catalog.fee')}</span>
        <span className="text-sm font-bold text-emerald-600 dark:text-emerald-400 font-mono">
          {formattedFee}
        </span>
      </div>
    </>
  );
};

export const ServiceCard: React.FC<ServiceCardProps> = ({ service, onSelect }) => {
  return (
    <div
      data-testid={`service-card-${service.id}`}
      onClick={() => onSelect(service)}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onSelect(service);
        }
      }}
      tabIndex={0}
      role="button"
      className="group flex flex-col bg-white dark:bg-slate-850 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-emerald-500/30 transition-all overflow-hidden cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
    >
      <ServiceCardCover service={service} />

      <div className="flex flex-col flex-1 p-4">
        <div className="flex items-center justify-between gap-2 mb-2">
          <span className="text-xs font-medium text-slate-500 dark:text-slate-400 truncate">
            {service.category.title}
          </span>
          <div className="flex flex-wrap gap-1">
            {service.tags.map((tag) => (
              <ServiceTagBadge key={tag} tag={tag} size="sm" />
            ))}
          </div>
        </div>

        <h2 className="text-base font-bold text-slate-900 dark:text-slate-100 mb-2 line-clamp-1 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
          {service.title}
        </h2>

        <p className="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 mb-4 flex-1">
          {service.description}
        </p>

        <ServiceCardFooter service={service} />
      </div>
    </div>
  );
};
