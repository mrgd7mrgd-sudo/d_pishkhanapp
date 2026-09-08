import React from 'react';
import { motion } from 'motion/react';
import { 
  Sparkles,
  ChevronLeft,
  Clock,
  Building2
} from 'lucide-react';
import { CitizenService } from '../types';
import { ServiceTagBadge } from './ServiceTagBadge';
import { CATEGORIES } from '../data/mockData';

interface ServiceCardProps {
  service: CitizenService;
  onSelectService: (service: CitizenService) => void;
  index?: number;
}

export const ServiceCard: React.FC<ServiceCardProps> = ({ service, onSelectService, index = 0 }) => {
  const category = CATEGORIES.find(c => c.id === service.categoryId);

  return (
    <motion.div 
      layout
      initial={{ opacity: 0, y: 18, scale: 0.95 }}
      animate={{ opacity: 1, y: 0, scale: 1 }}
      exit={{ opacity: 0, scale: 0.92, transition: { duration: 0.15 } }}
      transition={{
        duration: 0.32,
        delay: Math.min(index * 0.035, 0.25),
        ease: [0.22, 1, 0.36, 1],
        layout: { duration: 0.28, ease: 'easeOut' }
      }}
      whileHover={{ y: -4, transition: { duration: 0.2 } }}
      whileTap={{ scale: 0.985 }}
      onClick={() => onSelectService(service)}
      className="bg-white rounded-3xl p-4 sm:p-5 border border-slate-100/90 hover:border-emerald-300 shadow-sm hover:shadow-xl hover:shadow-emerald-950/5 transition-colors duration-200 cursor-pointer group flex flex-col justify-between relative overflow-hidden will-change-transform"
    >
      {/* Background subtle tint on hover */}
      <div className="absolute inset-0 bg-gradient-to-br from-transparent via-transparent to-emerald-50/25 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />

      <div>
        {/* Top: 3D Illustration Thumbnail + Title + Department */}
        <div className="flex items-start gap-3.5 relative z-10">
          <div className="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-b from-slate-50 to-slate-100 border border-slate-100 flex items-center justify-center shrink-0 p-1 shadow-inner group-hover:scale-105 transition-transform duration-300">
            {category?.image ? (
              <img
                src={category.image}
                alt={service.title}
                referrerPolicy="no-referrer"
                className="w-full h-full object-contain drop-shadow-sm"
              />
            ) : (
              <div className={`w-9 h-9 rounded-xl ${category?.color || 'bg-emerald-500'} text-white flex items-center justify-center shadow-xs`}>
                <Sparkles className="w-4 h-4" />
              </div>
            )}
          </div>

          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-1.5 flex-wrap">
              <h3 className="font-extrabold text-xs sm:text-sm text-slate-900 group-hover:text-emerald-700 transition-colors">
                {service.title}
              </h3>
              {service.isPopular && (
                <span className="inline-flex items-center gap-0.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-xs">
                  <Sparkles className="w-2.5 h-2.5" />
                  محبوب
                </span>
              )}
            </div>
            <div className="flex items-center gap-1 text-[11px] text-slate-400 font-medium mt-1">
              <Building2 className="w-3 h-3 text-slate-400 shrink-0" />
              <span className="truncate">{service.department}</span>
            </div>
          </div>
        </div>

        {/* Description */}
        <p className="text-xs text-slate-600 mt-3 leading-relaxed line-clamp-2 relative z-10">
          {service.description}
        </p>

        {/* Tags row: online, semi-online, in-person + Vault Readiness */}
        <div className="flex flex-wrap items-center gap-1.5 mt-3 relative z-10">
          {service.tags.map((tag) => (
            <ServiceTagBadge key={tag} tag={tag} size="sm" />
          ))}

          <span className="text-[10px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full font-medium">
            📋 ۲ از ۳ مدرک در مخزن
          </span>
        </div>
      </div>

      {/* Bottom meta: Time, Fee, Action button */}
      <div className="mt-4 pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs relative z-10">
        <div className="flex items-center gap-1.5 text-slate-500 bg-slate-50 px-2.5 py-1 rounded-xl">
          <Clock className="w-3.5 h-3.5 text-slate-400" />
          <span className="text-[11px] font-semibold">{service.estimatedDays}</span>
        </div>

        <div className="flex items-center gap-2">
          <div className="text-left">
            <span className="font-black text-slate-900 text-xs sm:text-sm">
              {service.fee === 0 ? 'رایگان' : `${service.fee.toLocaleString('fa-IR')} تومان`}
            </span>
          </div>

          <button className="flex items-center gap-1 bg-emerald-600 group-hover:bg-emerald-500 text-white px-3 py-1.5 rounded-xl font-black text-xs shadow-sm transition-all group-hover:shadow-emerald-500/20">
            <span>درخواست</span>
            <ChevronLeft className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </motion.div>
  );
};
