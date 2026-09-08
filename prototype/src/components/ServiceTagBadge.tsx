import React from 'react';
import { ServiceTag } from '../types';
import { Globe, UserCheck, Clock, MapPin } from 'lucide-react';

interface ServiceTagBadgeProps {
  tag: ServiceTag;
  size?: 'sm' | 'md';
}

export const ServiceTagBadge: React.FC<ServiceTagBadgeProps> = ({ tag, size = 'md' }) => {
  if (tag === 'online') {
    return (
      <span className={`inline-flex items-center gap-1 font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 ${
        size === 'sm' ? 'px-1.5 py-0.5 text-[10px]' : 'px-2 py-1 text-xs'
      }`}>
        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <Globe className={size === 'sm' ? 'w-2.5 h-2.5' : 'w-3 h-3'} />
        <span>۱۰۰٪ آنلاین (بدون مراجعه)</span>
      </span>
    );
  }

  if (tag === 'semi-online') {
    return (
      <span className={`inline-flex items-center gap-1 font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 ${
        size === 'sm' ? 'px-1.5 py-0.5 text-[10px]' : 'px-2 py-1 text-xs'
      }`}>
        <UserCheck className={size === 'sm' ? 'w-2.5 h-2.5' : 'w-3 h-3'} />
        <span>نیمه‌آنلاین (ثبت آنلاین + تحویل)</span>
      </span>
    );
  }

  // in-person
  return (
    <span className={`inline-flex items-center gap-1 font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-200/80 ${
      size === 'sm' ? 'px-1.5 py-0.5 text-[10px]' : 'px-2 py-1 text-xs'
    }`}>
      <MapPin className={size === 'sm' ? 'w-2.5 h-2.5' : 'w-3 h-3'} />
      <span>تمام حضوری (با ثبت نوبت)</span>
    </span>
  );
};
