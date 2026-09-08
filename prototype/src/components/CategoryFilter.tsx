import React from 'react';
import { motion } from 'motion/react';
import { 
  Grid,
  ChevronLeft,
  Scale,
  Sparkles
} from 'lucide-react';
import { CATEGORIES } from '../data/mockData';
import { ServiceCategory } from '../types';

interface CategoryFilterProps {
  selectedCategoryId: string | null;
  onSelectCategory: (categoryId: string | null) => void;
  onOpenConsultation?: () => void;
}

export const CategoryFilter: React.FC<CategoryFilterProps> = ({
  selectedCategoryId,
  onSelectCategory,
  onOpenConsultation
}) => {
  return (
    <div className="space-y-3.5">
      <div className="flex items-center justify-between px-1">
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
            <Grid className="w-4 h-4" />
          </div>
          <h2 className="text-sm sm:text-base font-black text-slate-900">
            دسته‌بندی خدمات پیشخوان
          </h2>
        </div>

        <button
          onClick={() => onSelectCategory(null)}
          className="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-0.5 bg-emerald-50 hover:bg-emerald-100/80 px-2.5 py-1 rounded-xl cursor-pointer transition-colors"
        >
          <span>کاتالوگ کامل</span>
          <ChevronLeft className="w-3.5 h-3.5" />
        </button>
      </div>

      {/* Grid of categories with rich 3D colorful illustrations */}
      <div className="grid grid-cols-4 sm:grid-cols-4 gap-2.5 sm:gap-3.5">
        {CATEGORIES.map((cat, idx) => {
          const isSelected = selectedCategoryId === cat.id;
          const isConsultation = cat.id === 'consultation';

          return (
            <motion.button
              key={cat.id}
              whileHover={{ y: -2 }}
              whileTap={{ scale: 0.94 }}
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{
                delay: idx * 0.025,
                duration: 0.25,
                ease: 'easeOut'
              }}
              onClick={() => {
                if (isConsultation && onOpenConsultation) {
                  onOpenConsultation();
                } else {
                  onSelectCategory(isSelected ? null : cat.id);
                }
              }}
              className={`flex flex-col items-center text-center p-2 sm:p-3 rounded-2xl sm:rounded-3xl transition-all cursor-pointer relative group border ${
                isSelected
                  ? 'bg-gradient-to-b from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/25 border-emerald-400 ring-2 ring-emerald-400'
                  : isConsultation
                  ? 'bg-gradient-to-b from-emerald-50/90 to-white text-slate-800 border-emerald-300 hover:border-emerald-400 shadow-sm hover:shadow-md ring-1 ring-emerald-400/30'
                  : 'bg-white hover:bg-slate-50/80 text-slate-800 border-slate-100 hover:border-slate-200 shadow-sm hover:shadow-md'
              }`}
            >
              {/* Badge (e.g. آنلاین / پرکاربرد / جدید) */}
              {cat.badge && !isSelected && (
                <span className={`absolute -top-1.5 -right-1 z-10 text-[9px] font-black px-2 py-0.5 rounded-full shadow-sm ring-2 ring-white ${
                  isConsultation
                    ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white'
                    : 'bg-gradient-to-r from-rose-500 to-red-600 text-white'
                }`}>
                  {cat.badge}
                </span>
              )}

              {/* 3D Illustrated Icon Container */}
              <div className={`w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center mb-2 transition-transform duration-300 group-hover:scale-105 overflow-hidden relative p-1 ${
                isSelected 
                  ? 'bg-white/20 shadow-inner' 
                  : isConsultation
                  ? 'bg-gradient-to-b from-emerald-100/60 to-emerald-50 shadow-inner border border-emerald-200/60'
                  : 'bg-gradient-to-b from-slate-50 to-slate-100/90 shadow-inner border border-slate-100'
              }`}>
                {cat.image ? (
                  <img
                    src={cat.image}
                    alt={cat.title}
                    referrerPolicy="no-referrer"
                    className="w-full h-full object-cover rounded-xl drop-shadow-md transform transition-transform duration-300 group-hover:scale-110"
                  />
                ) : (
                  <div className={`w-10 h-10 rounded-xl ${cat.color} text-white flex items-center justify-center shadow-md`}>
                    {isConsultation ? <Scale className="w-5 h-5" /> : <Grid className="w-5 h-5" />}
                  </div>
                )}
              </div>

              {/* Title & count */}
              <span className={`text-[11px] sm:text-xs font-bold leading-tight line-clamp-1 ${
                isSelected ? 'text-white' : 'text-slate-900'
              }`}>
                {cat.shortTitle || cat.title}
              </span>
              <span className={`text-[10px] font-medium leading-tight mt-0.5 ${
                isSelected 
                  ? 'text-emerald-100' 
                  : isConsultation 
                  ? 'text-emerald-700 font-bold' 
                  : 'text-slate-500'
              }`}>
                {isConsultation ? 'وکیل و مشاور' : `${cat.serviceCount || 3} خدمت`}
              </span>
            </motion.button>
          );
        })}
      </div>
    </div>
  );
};
