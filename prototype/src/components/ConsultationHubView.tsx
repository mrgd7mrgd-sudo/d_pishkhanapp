import React, { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Search, 
  Star, 
  ShieldCheck, 
  Sparkles, 
  Phone, 
  MessageSquare, 
  FileText, 
  CheckCircle2, 
  ChevronLeft, 
  Filter, 
  UserPlus, 
  CreditCard, 
  Building2, 
  Briefcase, 
  Clock, 
  Award, 
  ArrowRight,
  Gavel,
  Calculator,
  ShieldAlert,
  FileCheck2,
  Copy,
  Check,
  Percent,
  SlidersHorizontal,
  Layers,
  History,
  Info
} from 'lucide-react';
import { 
  ConsultationAdvisor, 
  ConsultationCategory, 
  BusinessSubscriptionPlan, 
  ConsultationSession,
  ConsultationMode
} from '../types';
import { 
  CONSULTATION_SPECIALTIES, 
  MOCK_ADVISORS, 
  BUSINESS_SUBSCRIPTION_PLANS 
} from '../data/consultationData';
import { ConsultationDetailModal } from './ConsultationDetailModal';
import { ConsultationLiveSessionModal } from './ConsultationLiveSessionModal';
import { AdvisorRegistrationModal } from './AdvisorRegistrationModal';
import { BusinessSubscriptionModal } from './BusinessSubscriptionModal';

interface ConsultationHubViewProps {
  userWalletBalance: number;
  onExecuteLinkedService: (serviceId: string) => void;
  onBackToHome?: () => void;
}

export const ConsultationHubView: React.FC<ConsultationHubViewProps> = ({
  userWalletBalance,
  onExecuteLinkedService,
  onBackToHome
}) => {
  // State
  const [advisorsList, setAdvisorsList] = useState<ConsultationAdvisor[]>(MOCK_ADVISORS);
  const [selectedCategory, setSelectedCategory] = useState<ConsultationCategory | 'all'>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [onlyOnline, setOnlyOnline] = useState(false);
  const [onlyVerified, setOnlyVerified] = useState(false);
  const [sortBy, setSortBy] = useState<'rating' | 'experience' | 'consultations'>('rating');
  const [activeMainTab, setActiveMainTab] = useState<'advisors' | 'my_sessions' | 'plans'>('advisors');

  // Modals state
  const [selectedAdvisorForDetail, setSelectedAdvisorForDetail] = useState<ConsultationAdvisor | null>(null);
  const [activeLiveSession, setActiveLiveSession] = useState<{
    advisor: ConsultationAdvisor;
    mode: ConsultationMode;
  } | null>(null);
  const [showAdvisorRegistration, setShowAdvisorRegistration] = useState(false);
  const [showSubscriptionModal, setShowSubscriptionModal] = useState(false);
  const [completedSessions, setCompletedSessions] = useState<ConsultationSession[]>([]);
  const [couponCopied, setCouponCopied] = useState(false);

  // Filtered advisors
  const filteredAdvisors = useMemo(() => {
    return advisorsList.filter(adv => {
      // Category filter
      if (selectedCategory !== 'all' && adv.category !== selectedCategory) {
        return false;
      }
      // Online filter
      if (onlyOnline && !adv.isOnline) {
        return false;
      }
      // Verified filter
      if (onlyVerified && !adv.isVerified) {
        return false;
      }
      // Search query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase().trim();
        const matchName = adv.name.toLowerCase().includes(q);
        const matchTitle = adv.title.toLowerCase().includes(q);
        const matchBadge = adv.credentialsBadge.toLowerCase().includes(q);
        const matchSpecialties = adv.specialties.some(s => s.toLowerCase().includes(q));
        if (!matchName && !matchTitle && !matchBadge && !matchSpecialties) {
          return false;
        }
      }
      return true;
    }).sort((a, b) => {
      if (sortBy === 'rating') return b.rating - a.rating;
      if (sortBy === 'experience') return b.experienceYears - a.experienceYears;
      return b.consultationCount - a.consultationCount;
    });
  }, [advisorsList, selectedCategory, searchQuery, onlyOnline, onlyVerified, sortBy]);

  const onlineCount = useMemo(() => advisorsList.filter(a => a.isOnline).length, [advisorsList]);

  const handleCopyCoupon = () => {
    navigator.clipboard?.writeText('first70');
    setCouponCopied(true);
    setTimeout(() => setCouponCopied(false), 2200);
  };

  const handleStartSession = (advisor: ConsultationAdvisor, mode: ConsultationMode) => {
    setSelectedAdvisorForDetail(null);
    setActiveLiveSession({ advisor, mode });
  };

  const handleSessionComplete = (session: ConsultationSession) => {
    setCompletedSessions(prev => [session, ...prev]);
  };

  const handleRegisterAdvisor = (newAdv: ConsultationAdvisor) => {
    setAdvisorsList(prev => [newAdv, ...prev]);
  };

  // Helper icons for categories
  const getSpecialtyIcon = (iconName: string) => {
    switch (iconName) {
      case 'Calculator': return <Calculator className="w-4 h-4 text-emerald-600" />;
      case 'ShieldAlert': return <ShieldAlert className="w-4 h-4 text-rose-600" />;
      case 'Gavel': return <Gavel className="w-4 h-4 text-amber-600" />;
      case 'FileCheck2': return <FileCheck2 className="w-4 h-4 text-indigo-600" />;
      case 'Building2': return <Building2 className="w-4 h-4 text-cyan-600" />;
      case 'Briefcase': return <Briefcase className="w-4 h-4 text-violet-600" />;
      default: return <Sparkles className="w-4 h-4 text-emerald-600" />;
    }
  };

  return (
    <div className="w-full space-y-3.5 pb-28 select-none" dir="rtl">
      
      {/* ------------------------------------------------------------- */}
      {/* 1. MOBILE APP BAR (هدر ارگونومیک موبایل)                         */}
      {/* ------------------------------------------------------------- */}
      <header className="bg-white rounded-3xl p-3.5 sm:p-4 border border-slate-200/90 shadow-xs space-y-3">
        {/* Top bar with back button, live status, and registration */}
        <div className="flex items-center justify-between gap-2">
          <div className="flex items-center gap-2.5 min-w-0">
            {onBackToHome && (
              <button 
                onClick={onBackToHome}
                className="w-9 h-9 rounded-xl bg-slate-100 active:bg-slate-200 text-slate-700 flex items-center justify-center transition cursor-pointer shrink-0 border border-slate-200/60 shadow-2xs"
                title="بازگشت به پیشخوان"
                aria-label="بازگشت"
              >
                <ArrowRight className="w-4 h-4" />
              </button>
            )}
            <div className="min-w-0">
              <div className="flex items-center gap-1.5">
                <span className="relative flex h-2 w-2 shrink-0">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <h1 className="font-black text-sm sm:text-base text-slate-900 truncate">
                  مشاوره تخصصی و وکلا
                </h1>
              </div>
              <p className="text-[10px] text-slate-500 truncate mt-0.5">
                {onlineCount} مشاور آنلاین • پاسخگویی در لحظه
              </p>
            </div>
          </div>

          <div className="flex items-center gap-1.5 shrink-0">
            <button
              onClick={() => setShowSubscriptionModal(true)}
              className="inline-flex items-center gap-1 bg-amber-500/10 hover:bg-amber-500/20 active:scale-95 text-amber-800 border border-amber-300/60 text-[11px] font-black px-2.5 py-1.5 rounded-xl transition cursor-pointer"
            >
              <Sparkles className="w-3.5 h-3.5 text-amber-600" />
              <span className="hidden xs:inline">طرح اصناف</span>
            </button>

            <button
              onClick={() => setShowAdvisorRegistration(true)}
              className="inline-flex items-center gap-1 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white text-[11px] font-bold px-2.5 py-1.5 rounded-xl transition cursor-pointer shadow-2xs"
              title="ثبت‌نام به عنوان مشاور یا وکیل"
            >
              <UserPlus className="w-3.5 h-3.5 text-emerald-400" />
              <span className="hidden sm:inline">ثبت‌نام مشاور</span>
            </button>
          </div>
        </div>

        {/* Segmented Mobile Tab Switcher */}
        <div className="grid grid-cols-3 gap-1 bg-slate-100 p-1 rounded-2xl text-xs font-bold">
          <button
            onClick={() => setActiveMainTab('advisors')}
            className={`py-1.5 px-2 rounded-xl transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
              activeMainTab === 'advisors'
                ? 'bg-white text-slate-950 shadow-xs font-black'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <Layers className="w-3.5 h-3.5" />
            <span>مشاوران</span>
          </button>

          <button
            onClick={() => setActiveMainTab('my_sessions')}
            className={`py-1.5 px-2 rounded-xl transition-all cursor-pointer flex items-center justify-center gap-1.5 relative ${
              activeMainTab === 'my_sessions'
                ? 'bg-white text-slate-950 shadow-xs font-black'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <History className="w-3.5 h-3.5" />
            <span>جلسات من</span>
            {completedSessions.length > 0 && (
              <span className="w-4 h-4 bg-emerald-500 text-white rounded-full text-[9px] font-black flex items-center justify-center">
                {completedSessions.length}
              </span>
            )}
          </button>

          <button
            onClick={() => setShowSubscriptionModal(true)}
            className={`py-1.5 px-2 rounded-xl transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
              activeMainTab === 'plans'
                ? 'bg-white text-slate-950 shadow-xs font-black'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <CreditCard className="w-3.5 h-3.5 text-amber-600" />
            <span>اشتراک‌ها</span>
          </button>
        </div>

        {/* Compact Search Bar */}
        <div className="relative">
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="جستجوی نام مشاور یا تخصص (مالیات، بیمه، ماده ۱۰۰)..."
            className="w-full bg-slate-50 border border-slate-200 focus:border-emerald-500 rounded-2xl py-2.5 pr-10 pl-16 text-xs text-slate-800 focus:outline-none focus:ring-3 focus:ring-emerald-500/15 transition placeholder:text-slate-400"
          />
          <Search className="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          {searchQuery && (
            <button 
              onClick={() => setSearchQuery('')}
              className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-500 hover:text-slate-700 font-bold bg-slate-200 hover:bg-slate-300 px-2 py-0.5 rounded-lg transition"
            >
              پاک کردن
            </button>
          )}
        </div>

        {/* Touch Horizontal Specialties Carousel */}
        <div className="relative pt-0.5">
          <div 
            className="flex items-center gap-1.5 overflow-x-auto no-scrollbar scroll-smooth touch-pan-x py-1 -mx-1 px-1"
            tabIndex={0}
            aria-label="دسته‌بندی تخصص‌های مشاوره"
          >
            <button
              onClick={() => setSelectedCategory('all')}
              className={`px-3 py-1.5 rounded-xl text-[11px] font-black shrink-0 transition-all cursor-pointer flex items-center gap-1.5 border ${
                selectedCategory === 'all'
                  ? 'bg-slate-900 border-slate-900 text-white shadow-2xs'
                  : 'bg-slate-100/80 border-slate-200/80 text-slate-600 hover:bg-slate-200'
              }`}
            >
              <Sparkles className="w-3 h-3 text-amber-400" />
              <span>همه</span>
            </button>
            {CONSULTATION_SPECIALTIES.map(cat => {
              const isSelected = selectedCategory === cat.category;
              return (
                <button
                  key={cat.id}
                  onClick={() => setSelectedCategory(cat.category)}
                  className={`px-2.5 py-1.5 rounded-xl text-[11px] font-bold shrink-0 transition-all cursor-pointer flex items-center gap-1.5 border ${
                    isSelected
                      ? 'bg-emerald-50 border-emerald-500 text-emerald-950 font-black ring-1 ring-emerald-500/30'
                      : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'
                  }`}
                >
                  <span className="shrink-0">{getSpecialtyIcon(cat.iconName)}</span>
                  <span className="whitespace-nowrap">{cat.title}</span>
                  {cat.badge && (
                    <span className="text-[9px] bg-emerald-500 text-white px-1.5 py-0.2 rounded-full font-black tracking-tight shrink-0">
                      {cat.badge}
                    </span>
                  )}
                </button>
              );
            })}
          </div>
        </div>
      </header>

      {/* ------------------------------------------------------------- */}
      {/* 2. TAB 1: ADVISORS & EXPERTS VIEW                             */}
      {/* ------------------------------------------------------------- */}
      {activeMainTab === 'advisors' && (
        <div className="space-y-3.5">
          
          {/* Compact Mobile Promo Coupon Card */}
          <div className="bg-gradient-to-r from-rose-500 via-rose-600 to-amber-500 rounded-2xl p-3 text-white shadow-sm flex items-center justify-between gap-2.5">
            <div className="flex items-center gap-2.5 min-w-0">
              <div className="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 border border-white/30">
                <Percent className="w-5 h-5 text-white" />
              </div>
              <div className="min-w-0">
                <div className="flex items-center gap-1.5">
                  <span className="font-black text-xs sm:text-sm truncate">۷۰٪ تخفیف مشاوره اول</span>
                  <span className="bg-white/25 text-[9px] font-black px-1.5 py-0.2 rounded-full shrink-0">جدید</span>
                </div>
                <p className="text-[10px] text-rose-100 truncate">
                  پاسخ فوری به ابهامات مالیات، بیمه و ماده ۱۰۰
                </p>
              </div>
            </div>

            <div className="flex items-center gap-1.5 bg-white/20 backdrop-blur-md border border-white/30 px-2 py-1 rounded-xl shrink-0">
              <span className="font-mono font-black text-xs text-white">first70</span>
              <button
                onClick={handleCopyCoupon}
                className="p-1 rounded-md hover:bg-white/20 transition cursor-pointer text-white"
                title="کپی کد تخفیف"
                aria-label="کپی کد تخفیف"
              >
                {couponCopied ? <Check className="w-3.5 h-3.5 text-emerald-300" /> : <Copy className="w-3.5 h-3.5" />}
              </button>
            </div>
          </div>

          {/* Quick Filter & Sort Pills Bar */}
          <div className="flex items-center justify-between gap-2 bg-white rounded-2xl p-2.5 border border-slate-200/90 text-xs">
            {/* Online & Verified Toggles */}
            <div className="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
              <button
                onClick={() => setOnlyOnline(!onlyOnline)}
                className={`px-2.5 py-1.5 rounded-xl text-[11px] font-bold border transition cursor-pointer flex items-center gap-1 shrink-0 ${
                  onlyOnline
                    ? 'bg-emerald-500 border-emerald-600 text-white shadow-2xs'
                    : 'bg-slate-100 border-slate-200 text-slate-600'
                }`}
              >
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                <span>فقط آنلاین‌ها</span>
              </button>

              <button
                onClick={() => setOnlyVerified(!onlyVerified)}
                className={`px-2.5 py-1.5 rounded-xl text-[11px] font-bold border transition cursor-pointer flex items-center gap-1 shrink-0 ${
                  onlyVerified
                    ? 'bg-indigo-600 border-indigo-700 text-white shadow-2xs'
                    : 'bg-slate-100 border-slate-200 text-slate-600'
                }`}
              >
                <ShieldCheck className="w-3.5 h-3.5" />
                <span>تایید رسمی</span>
              </button>
            </div>

            {/* Sort Selector */}
            <div className="flex items-center gap-1 shrink-0 bg-slate-100 p-0.5 rounded-xl">
              <button
                onClick={() => setSortBy('rating')}
                className={`px-2 py-1 rounded-lg text-[10px] font-bold transition cursor-pointer ${
                  sortBy === 'rating' ? 'bg-white text-slate-900 shadow-2xs font-black' : 'text-slate-500'
                }`}
              >
                امتیاز
              </button>
              <button
                onClick={() => setSortBy('consultations')}
                className={`px-2 py-1 rounded-lg text-[10px] font-bold transition cursor-pointer ${
                  sortBy === 'consultations' ? 'bg-white text-slate-900 shadow-2xs font-black' : 'text-slate-500'
                }`}
              >
                مشاوره
              </button>
              <button
                onClick={() => setSortBy('experience')}
                className={`px-2 py-1 rounded-lg text-[10px] font-bold transition cursor-pointer ${
                  sortBy === 'experience' ? 'bg-white text-slate-900 shadow-2xs font-black' : 'text-slate-500'
                }`}
              >
                سابقه
              </button>
            </div>
          </div>

          {/* Advisors Feed Cards */}
          <div className="space-y-3">
            {filteredAdvisors.length > 0 ? (
              filteredAdvisors.map((adv) => (
                <div
                  key={adv.id}
                  className="bg-white rounded-3xl p-3.5 sm:p-4 border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all space-y-3"
                >
                  {/* Top: Avatar, Name, Badges, Rating */}
                  <div className="flex items-start gap-3">
                    <div className="relative shrink-0">
                      <img
                        src={adv.avatar}
                        alt={adv.name}
                        className="w-14 h-14 rounded-2xl object-cover ring-2 ring-slate-100 shadow-2xs"
                      />
                      {adv.isOnline ? (
                        <span className="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full flex items-center justify-center shadow-xs" title="آنلاین">
                          <span className="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
                        </span>
                      ) : (
                        <span className="absolute -bottom-1 -right-1 w-3 h-3 bg-slate-300 border-2 border-white rounded-full" title="آفلاین" />
                      )}
                    </div>

                    <div className="flex-1 min-w-0 space-y-0.5">
                      <div className="flex items-center justify-between gap-1">
                        <div className="flex items-center gap-1.5 min-w-0">
                          <h3 className="font-black text-xs sm:text-sm text-slate-900 truncate">{adv.name}</h3>
                          {adv.isVerified && (
                            <ShieldCheck className="w-4 h-4 text-emerald-600 shrink-0" title="دارای پروانه رسمی" />
                          )}
                        </div>
                        
                        <div className="flex items-center gap-1 bg-amber-50 text-amber-900 border border-amber-200/80 px-1.5 py-0.5 rounded-lg text-[10px] font-black shrink-0">
                          <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                          <span>{adv.rating}</span>
                        </div>
                      </div>

                      <p className="text-[11px] text-slate-600 line-clamp-1">{adv.title}</p>

                      <div className="flex items-center gap-2 pt-0.5 text-[10px]">
                        <span className="bg-indigo-50 text-indigo-800 border border-indigo-200/80 px-1.5 py-0.2 rounded-md font-bold truncate">
                          {adv.credentialsBadge}
                        </span>
                        <span className="text-slate-400 font-medium">
                          {adv.consultationCount}+ مشاوره
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Multi-criteria micro scores */}
                  <div className="grid grid-cols-3 gap-1 bg-slate-50/80 rounded-xl p-1.5 text-center text-[10px]">
                    <div>
                      <span className="text-slate-400 block text-[8.5px]">دقت راهکار</span>
                      <span className="font-black text-slate-800 font-mono">{adv.ratingBreakdown.accuracy}</span>
                    </div>
                    <div className="border-x border-slate-200">
                      <span className="text-slate-400 block text-[8.5px]">فن بیان</span>
                      <span className="font-black text-slate-800 font-mono">{adv.ratingBreakdown.eloquence}</span>
                    </div>
                    <div>
                      <span className="text-slate-400 block text-[8.5px]">صبوری</span>
                      <span className="font-black text-slate-800 font-mono">{adv.ratingBreakdown.patience}</span>
                    </div>
                  </div>

                  {/* Linked Action Service Badge if exists */}
                  {adv.linkedActionServices.length > 0 && (
                    <div className="bg-emerald-50/70 border border-emerald-200/70 rounded-xl p-2 flex items-center gap-1.5 text-[10px] text-emerald-900">
                      <Building2 className="w-3.5 h-3.5 text-emerald-700 shrink-0" />
                      <span className="truncate">متصل به اقدام رسمی در پیشخوان: {adv.linkedActionServices[0].title}</span>
                    </div>
                  )}

                  {/* Pricing & CTA Button */}
                  <div className="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div>
                      <span className="text-[9px] text-slate-400 block">تعرفه مشاوره:</span>
                      <span className="font-black text-xs text-slate-900 font-mono">
                        {adv.pricing.phonePerMinute.toLocaleString('fa-IR')} ت/دقیقه
                      </span>
                    </div>

                    <button
                      onClick={() => setSelectedAdvisorForDetail(adv)}
                      className="bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs px-3.5 py-2 rounded-xl transition cursor-pointer flex items-center gap-1 shadow-sm shadow-emerald-600/20"
                    >
                      <Phone className="w-3.5 h-3.5" />
                      <span>گفتگو و رزرو</span>
                      <ChevronLeft className="w-3.5 h-3.5" />
                    </button>
                  </div>

                </div>
              ))
            ) : (
              <div className="bg-white rounded-3xl p-6 text-center border border-slate-200/90 space-y-2.5">
                <Search className="w-8 h-8 text-slate-300 mx-auto" />
                <h4 className="font-black text-xs text-slate-800">مشاوری با این مشخصات یافت نشد</h4>
                <p className="text-[11px] text-slate-500">می‌توانید فیلترها را ریست کنید یا با تخصص دیگری جستجو فرمایید.</p>
                <button
                  onClick={() => {
                    setSelectedCategory('all');
                    setSearchQuery('');
                    setOnlyOnline(false);
                    setOnlyVerified(false);
                  }}
                  className="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
                >
                  نمایش همه مشاوران
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* 3. TAB 2: MY COMPLETED SESSIONS                               */}
      {/* ------------------------------------------------------------- */}
      {activeMainTab === 'my_sessions' && (
        <div className="bg-white rounded-3xl p-4 border border-slate-200/90 shadow-xs space-y-3.5">
          <div className="flex items-center justify-between">
            <h2 className="font-black text-xs sm:text-sm text-slate-900 flex items-center gap-1.5">
              <CheckCircle2 className="w-4 h-4 text-emerald-600" />
              <span>تاریخچه مشاوره‌ها و صورتجلسات شما</span>
            </h2>
            <span className="text-[11px] text-slate-400 font-mono">
              {completedSessions.length} جلسه
            </span>
          </div>

          {completedSessions.length > 0 ? (
            <div className="space-y-2.5">
              {completedSessions.map(session => (
                <div key={session.id} className="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-1.5 font-black text-slate-800">
                      <span>مشاور: {session.advisorName}</span>
                      <span className="text-[9px] bg-slate-200 text-slate-700 px-1.5 py-0.2 rounded-md font-bold">
                        {session.categoryTitle}
                      </span>
                    </div>
                    <span className="text-[10px] text-slate-400 font-mono">{session.createdAt}</span>
                  </div>

                  {session.advisorVerdict && (
                    <p className="text-[11px] text-slate-600 bg-white p-2.5 rounded-xl border border-slate-200/80 leading-relaxed">
                      <strong>خلاصه و راهکار:</strong> {session.advisorVerdict}
                    </p>
                  )}

                  {session.linkedServiceToExecute && (
                    <div className="flex items-center justify-between pt-1 border-t border-slate-200/50">
                      <span className="text-[10px] text-emerald-800 font-bold truncate">
                        اقدام در پیشخوان: {session.linkedServiceToExecute.title}
                      </span>
                      <button
                        onClick={() => onExecuteLinkedService(session.linkedServiceToExecute!.serviceId)}
                        className="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-[11px] px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-0.5 shrink-0"
                      >
                        <span>ثبت خدمت</span>
                        <ChevronLeft className="w-3 h-3" />
                      </button>
                    </div>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="py-8 text-center space-y-2">
              <History className="w-8 h-8 text-slate-300 mx-auto" />
              <p className="font-bold text-xs text-slate-700">هنوز هیچ جلسه مشاوره‌ای ثبت نکرده‌اید</p>
              <p className="text-[11px] text-slate-400">پس از پایان اولین گفتگوی متنی یا تلفنی، خلاصه صورتجلسه در این بخش نمایش داده می‌شود.</p>
              <button
                onClick={() => setActiveMainTab('advisors')}
                className="mt-2 px-3.5 py-1.5 bg-emerald-600 text-white font-black text-xs rounded-xl cursor-pointer"
              >
                مشاهده لیست مشاوران
              </button>
            </div>
          )}
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* 4. MODALS & SUB-VIEWS                                         */}
      {/* ------------------------------------------------------------- */}
      {selectedAdvisorForDetail && (
        <ConsultationDetailModal
          advisor={selectedAdvisorForDetail}
          onClose={() => setSelectedAdvisorForDetail(null)}
          onStartSession={handleStartSession}
          onExecuteLinkedService={onExecuteLinkedService}
        />
      )}

      {activeLiveSession && (
        <ConsultationLiveSessionModal
          advisor={activeLiveSession.advisor}
          mode={activeLiveSession.mode}
          onClose={() => setActiveLiveSession(null)}
          onSessionComplete={(session) => {
            handleSessionComplete(session);
            setActiveLiveSession(null);
          }}
          onExecuteLinkedService={onExecuteLinkedService}
        />
      )}

      {showAdvisorRegistration && (
        <AdvisorRegistrationModal
          onClose={() => setShowAdvisorRegistration(false)}
          onRegisterAdvisor={handleRegisterAdvisor}
        />
      )}

      {showSubscriptionModal && (
        <BusinessSubscriptionModal
          plans={BUSINESS_SUBSCRIPTION_PLANS}
          userWalletBalance={userWalletBalance}
          onClose={() => setShowSubscriptionModal(false)}
          onSubscribe={(plan) => {
            // Handled
          }}
        />
      )}

    </div>
  );
};

