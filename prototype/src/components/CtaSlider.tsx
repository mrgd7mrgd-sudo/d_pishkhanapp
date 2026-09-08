import React, { useState, useEffect, useRef } from 'react';
import { 
  Sparkles, 
  Mic, 
  Users, 
  MapPin, 
  ShieldCheck, 
  Grid, 
  ChevronLeft, 
  ChevronRight,
  ArrowLeft,
  Clock,
  CheckCircle2
} from 'lucide-react';
import illusHeroBanner from '../assets/images/illus_hero_banner_1788033276617.jpg';
import illusPost from '../assets/images/illus_post_3d_1788033261489.jpg';
import illusIdentity from '../assets/images/illus_identity_3d_1788033162106.jpg';
import illusGov from '../assets/images/illus_gov_3d_1788033220023.jpg';
import illusConsult from '../assets/images/illus_consult_3d_1788285048758.jpg';

interface CtaSliderProps {
  onOpenVoiceAssistant: () => void;
  onOpenDelegations: () => void;
  onOpenMap: () => void;
  onOpenServices: () => void;
  onOpenProfile: () => void;
  onOpenConsultation?: () => void;
}

export const CtaSlider: React.FC<CtaSliderProps> = ({
  onOpenVoiceAssistant,
  onOpenDelegations,
  onOpenMap,
  onOpenServices,
  onOpenProfile,
  onOpenConsultation
}) => {
  const [currentSlide, setCurrentSlide] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const touchStartXRef = useRef<number | null>(null);

  const slides = [
    {
      id: 'consultation-hub',
      tag: 'سامانه مشاوره آنلاین • گفتگو در لحظه',
      title: 'مشاوره آنلاین با کارشناسان مالیاتی و وکلا',
      description: 'گفتگوی فوری، بررسی سامانه مودیان، بیمه و ماده ۱۰۰ متصل به اقدام در پیشخوان',
      image: illusConsult,
      gradient: 'from-slate-950 via-indigo-950 to-slate-900',
      badgeColor: 'bg-emerald-500/30 text-emerald-100 border-emerald-400/30',
      actions: (
        <div className="flex items-center gap-2 pt-1 flex-wrap">
          {onOpenConsultation && (
            <button
              onClick={onOpenConsultation}
              className="flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-400 active:scale-95 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow-sm transition-all cursor-pointer"
            >
              <Sparkles className="w-3.5 h-3.5 text-slate-950" />
              <span>ورود به سامانه مشاوره تخصصی</span>
            </button>
          )}
          <button
            onClick={onOpenVoiceAssistant}
            className="flex items-center gap-1.5 bg-white/15 hover:bg-white/25 active:scale-95 text-white font-bold text-xs px-3 py-2 rounded-xl border border-white/20 transition-all cursor-pointer backdrop-blur-xs"
          >
            <Mic className="w-3.5 h-3.5 text-emerald-300" />
            <span>دستیار صوتی</span>
          </button>
        </div>
      )
    },
    {
      id: 'voice-smart',
      tag: 'سامانه هوشمند پیشخوان دولت',
      title: 'انجام ۲۴ خدمت دولتی آنلاین و سریع',
      description: 'بدون معطلی در صف با استعلام هوشمند، دستیار صوتی و دریافت تاییدیه فوری',
      image: illusHeroBanner,
      gradient: 'from-emerald-700 via-teal-700 to-cyan-800',
      badgeColor: 'bg-emerald-500/30 text-emerald-100 border-emerald-400/30',
      actions: (
        <div className="flex items-center gap-2 pt-1 flex-wrap">
          <button
            onClick={onOpenVoiceAssistant}
            className="flex items-center gap-1.5 bg-white text-emerald-900 hover:bg-emerald-50 active:scale-95 font-black text-xs px-3.5 py-2 rounded-xl shadow-sm transition-all cursor-pointer"
          >
            <Mic className="w-3.5 h-3.5 text-emerald-600 animate-pulse" />
            <span>دستیار صوتی</span>
          </button>
          <button
            onClick={onOpenDelegations}
            className="flex items-center gap-1.5 bg-white/15 hover:bg-white/25 active:scale-95 text-white font-bold text-xs px-3 py-2 rounded-xl border border-white/20 transition-all cursor-pointer backdrop-blur-xs"
          >
            <Users className="w-3.5 h-3.5 text-amber-300" />
            <span>نیابت خانواده</span>
          </button>
        </div>
      )
    },
    {
      id: 'office-booking',
      tag: 'نوبت‌دهی آنلاین باجه‌ها',
      title: 'رزرو نوبت نزدیک‌ترین دفتر پیشخوان',
      description: 'انتخاب دفتر بر روی نقشه آنلاین، تعیین ساعت مراجعه و دریافت بارکد اختصاصی',
      image: illusPost,
      gradient: 'from-[#2e4368] via-indigo-800 to-slate-900',
      badgeColor: 'bg-blue-500/30 text-blue-100 border-blue-400/30',
      actions: (
        <div className="flex items-center gap-2 pt-1 flex-wrap">
          <button
            onClick={onOpenMap}
            className="flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-400 active:scale-95 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow-sm transition-all cursor-pointer"
          >
            <MapPin className="w-3.5 h-3.5 text-slate-950" />
            <span>مشاهده نقشه و رزرو</span>
          </button>
          <button
            onClick={onOpenServices}
            className="flex items-center gap-1 bg-white/15 hover:bg-white/25 active:scale-95 text-white font-bold text-xs px-3 py-2 rounded-xl border border-white/20 transition-all cursor-pointer backdrop-blur-xs"
          >
            <span>لیست خدمات</span>
            <ArrowLeft className="w-3.5 h-3.5 text-white/80" />
          </button>
        </div>
      )
    },
    {
      id: 'vault-reminder',
      tag: 'گاوصندوق اسناد و مدارک',
      title: 'نگهداری امن مدارک هویتی و یادآور انقضا',
      description: 'ثبت دیجیتال کارت ملی، شناسنامه و گواهینامه همراه با هشدار خودکار انقضا',
      image: illusIdentity,
      gradient: 'from-slate-900 via-slate-800 to-teal-950',
      badgeColor: 'bg-teal-500/30 text-teal-100 border-teal-400/30',
      actions: (
        <div className="flex items-center gap-2 pt-1 flex-wrap">
          <button
            onClick={onOpenProfile}
            className="flex items-center gap-1.5 bg-teal-400 hover:bg-teal-300 active:scale-95 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow-sm transition-all cursor-pointer"
          >
            <ShieldCheck className="w-3.5 h-3.5 text-slate-950" />
            <span>ورود به مخزن اسناد</span>
          </button>
        </div>
      )
    },
    {
      id: 'legal-delegation',
      tag: 'تفویض اختیار قانونی',
      title: 'انجام امور اداری سالمندان و اعضای خانواده',
      description: 'ثبت وکالت‌نامه معتبر الکترونیک جهت پیگیری و درخواست خدمات به نیابت از بستگان',
      image: illusGov,
      gradient: 'from-amber-700 via-orange-800 to-slate-900',
      badgeColor: 'bg-amber-500/30 text-amber-100 border-amber-400/30',
      actions: (
        <div className="flex items-center gap-2 pt-1 flex-wrap">
          <button
            onClick={onOpenDelegations}
            className="flex items-center gap-1.5 bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow-sm transition-all cursor-pointer"
          >
            <Users className="w-3.5 h-3.5 text-slate-950" />
            <span>ثبت نیابت جدید</span>
          </button>
          <button
            onClick={onOpenServices}
            className="flex items-center gap-1 bg-white/15 hover:bg-white/25 active:scale-95 text-white font-bold text-xs px-3 py-2 rounded-xl border border-white/20 transition-all cursor-pointer backdrop-blur-xs"
          >
            <span>فهرست خدمات</span>
          </button>
        </div>
      )
    }
  ];

  // Auto-play timer
  useEffect(() => {
    if (isPaused) return;
    const interval = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, 4500);

    return () => clearInterval(interval);
  }, [isPaused, slides.length]);

  const handlePrev = () => {
    setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
  };

  const handleNext = () => {
    setCurrentSlide((prev) => (prev + 1) % slides.length);
  };

  const handleTouchStart = (e: React.TouchEvent) => {
    touchStartXRef.current = e.touches[0].clientX;
  };

  const handleTouchEnd = (e: React.TouchEvent) => {
    if (touchStartXRef.current === null) return;
    const touchEndX = e.changedTouches[0].clientX;
    const diff = touchStartXRef.current - touchEndX;

    // RTL gesture: swipe right to left = next, swipe left to right = prev
    if (diff > 40) {
      handleNext();
    } else if (diff < -40) {
      handlePrev();
    }
    touchStartXRef.current = null;
  };

  const slide = slides[currentSlide];

  return (
    <div 
      className="relative rounded-3xl overflow-hidden shadow-lg border border-slate-200/80 transition-all select-none"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
      dir="rtl"
    >
      {/* Background Gradient with Animated Transition */}
      <div className={`bg-gradient-to-r ${slide.gradient} text-white p-4 sm:p-5 transition-all duration-500 relative overflow-hidden min-h-[170px] flex items-center justify-between gap-3`}>
        
        {/* Subtle Ambient Light Orbs */}
        <div className="absolute -left-12 -top-12 w-44 h-44 bg-white/10 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute -right-12 -bottom-12 w-44 h-44 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none" />

        {/* Content Side */}
        <div className="flex-1 z-10 space-y-2 text-right">
          <div className={`inline-flex items-center gap-1.5 backdrop-blur-md px-2.5 py-0.5 rounded-full text-[10px] font-black border ${slide.badgeColor}`}>
            <Sparkles className="w-3 h-3 text-amber-300" />
            <span>{slide.tag}</span>
          </div>

          <h2 className="text-sm sm:text-base font-black leading-tight text-white transition-all">
            {slide.title}
          </h2>

          <p className="text-[11px] sm:text-xs text-white/85 leading-relaxed max-w-sm line-clamp-2">
            {slide.description}
          </p>

          {/* Action Buttons */}
          {slide.actions}
        </div>

        {/* Navigation Buttons (Subtle Left/Right) */}
        <button
          onClick={handlePrev}
          aria-label="Previous Slide"
          className="absolute left-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-black/25 hover:bg-black/45 text-white/90 flex items-center justify-center backdrop-blur-xs transition-all z-20 cursor-pointer hidden sm:flex"
        >
          <ChevronLeft className="w-4 h-4" />
        </button>
        <button
          onClick={handleNext}
          aria-label="Next Slide"
          className="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-black/25 hover:bg-black/45 text-white/90 flex items-center justify-center backdrop-blur-xs transition-all z-20 cursor-pointer hidden sm:flex"
        >
          <ChevronRight className="w-4 h-4" />
        </button>

      </div>
    </div>
  );
};
