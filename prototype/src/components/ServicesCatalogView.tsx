import React, { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Sparkles, 
  Search, 
  BookOpen, 
  CreditCard, 
  FileCheck, 
  UserCheck, 
  ShieldCheck, 
  Activity, 
  Stethoscope, 
  Receipt, 
  RotateCw, 
  Fuel, 
  Users, 
  ShoppingBag, 
  TrendingUp, 
  Briefcase, 
  Award, 
  Scale, 
  Calculator, 
  Building, 
  MapPin, 
  FileText, 
  BarChart3, 
  Navigation, 
  X,
  Globe,
  Building2,
  QrCode,
  Shield,
  CheckCircle2,
  Lock,
  Gavel,
  UserPlus,
  CalendarCheck,
  MailCheck,
  ShieldAlert,
  FileSpreadsheet,
  Key,
  FileCheck2,
  AlertCircle,
  KeyRound,
  AlertTriangle,
  UserX,
  Clock,
  Car,
  HeartPulse,
  Gift,
  Home,
  Mail,
  Fingerprint,
  ChevronLeft,
  LayoutGrid
} from 'lucide-react';
import { CitizenService, ServiceCategory } from '../types';
import { CATEGORIES } from '../data/mockData';

interface ServicesCatalogViewProps {
  services: CitizenService[];
  initialCategoryId?: string | null;
  searchQuery?: string;
  onSelectService: (service: CitizenService) => void;
}

// Visual theme configurations per category
const CATEGORY_THEMES: Record<string, {
  text: string;
  accent: string;
  lightBg: string;
  border: string;
  badge: string;
  iconContainer: string;
  iconColor: string;
}> = {
  new: {
    text: 'text-amber-600',
    accent: '#f59e0b',
    lightBg: 'bg-amber-50/60',
    border: 'border-amber-200/60',
    badge: 'bg-amber-100 text-amber-800 border-amber-200',
    iconContainer: 'bg-gradient-to-br from-amber-50 to-amber-100/90 border-amber-200/80 text-amber-600 group-hover:from-amber-100 group-hover:to-amber-200/80 group-hover:border-amber-300',
    iconColor: 'text-amber-600'
  },
  consultation: {
    text: 'text-emerald-700',
    accent: '#059669',
    lightBg: 'bg-emerald-50/50',
    border: 'border-emerald-200/60',
    badge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    iconContainer: 'bg-gradient-to-br from-emerald-50 to-teal-100/80 border-emerald-200/80 text-emerald-600 group-hover:from-emerald-100 group-hover:to-teal-200/80 group-hover:border-emerald-300',
    iconColor: 'text-emerald-600'
  },
  identity: {
    text: 'text-teal-700',
    accent: '#0d9488',
    lightBg: 'bg-teal-50/50',
    border: 'border-teal-200/60',
    badge: 'bg-teal-100 text-teal-800 border-teal-200',
    iconContainer: 'bg-gradient-to-br from-teal-50 to-cyan-100/80 border-teal-200/80 text-teal-600 group-hover:from-teal-100 group-hover:to-cyan-200/80 group-hover:border-teal-300',
    iconColor: 'text-teal-600'
  },
  vehicle: {
    text: 'text-blue-700',
    accent: '#2563eb',
    lightBg: 'bg-blue-50/50',
    border: 'border-blue-200/60',
    badge: 'bg-blue-100 text-blue-800 border-blue-200',
    iconContainer: 'bg-gradient-to-br from-blue-50 to-sky-100/80 border-blue-200/80 text-blue-600 group-hover:from-blue-100 group-hover:to-sky-200/80 group-hover:border-blue-300',
    iconColor: 'text-blue-600'
  },
  health: {
    text: 'text-rose-700',
    accent: '#e11d48',
    lightBg: 'bg-rose-50/50',
    border: 'border-rose-200/60',
    badge: 'bg-rose-100 text-rose-800 border-rose-200',
    iconContainer: 'bg-gradient-to-br from-rose-50 to-pink-100/80 border-rose-200/80 text-rose-600 group-hover:from-rose-100 group-hover:to-pink-200/80 group-hover:border-rose-300',
    iconColor: 'text-rose-600'
  },
  welfare: {
    text: 'text-amber-700',
    accent: '#d97706',
    lightBg: 'bg-amber-50/50',
    border: 'border-amber-200/60',
    badge: 'bg-amber-100 text-amber-800 border-amber-200',
    iconContainer: 'bg-gradient-to-br from-amber-50 to-orange-100/80 border-amber-200/80 text-amber-600 group-hover:from-amber-100 group-hover:to-orange-200/80 group-hover:border-amber-300',
    iconColor: 'text-amber-600'
  },
  government: {
    text: 'text-indigo-700',
    accent: '#4f46e5',
    lightBg: 'bg-indigo-50/50',
    border: 'border-indigo-200/60',
    badge: 'bg-indigo-100 text-indigo-800 border-indigo-200',
    iconContainer: 'bg-gradient-to-br from-indigo-50 to-purple-100/80 border-indigo-200/80 text-indigo-600 group-hover:from-indigo-100 group-hover:to-purple-200/80 group-hover:border-indigo-300',
    iconColor: 'text-indigo-600'
  },
  housing: {
    text: 'text-emerald-800',
    accent: '#047857',
    lightBg: 'bg-emerald-50/50',
    border: 'border-emerald-200/60',
    badge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    iconContainer: 'bg-gradient-to-br from-emerald-50 to-green-100/80 border-emerald-200/80 text-emerald-700 group-hover:from-emerald-100 group-hover:to-green-200/80 group-hover:border-emerald-300',
    iconColor: 'text-emerald-700'
  },
  banking: {
    text: 'text-sky-700',
    accent: '#0284c7',
    lightBg: 'bg-sky-50/50',
    border: 'border-sky-200/60',
    badge: 'bg-sky-100 text-sky-800 border-sky-200',
    iconContainer: 'bg-gradient-to-br from-sky-50 to-cyan-100/80 border-sky-200/80 text-sky-600 group-hover:from-sky-100 group-hover:to-cyan-200/80 group-hover:border-sky-300',
    iconColor: 'text-sky-600'
  },
  postal: {
    text: 'text-orange-700',
    accent: '#ea580c',
    lightBg: 'bg-orange-50/50',
    border: 'border-orange-200/60',
    badge: 'bg-orange-100 text-orange-800 border-orange-200',
    iconContainer: 'bg-gradient-to-br from-orange-50 to-amber-100/80 border-orange-200/80 text-orange-600 group-hover:from-orange-100 group-hover:to-amber-200/80 group-hover:border-orange-300',
    iconColor: 'text-orange-600'
  },
  internet: {
    text: 'text-violet-700',
    accent: '#7c3aed',
    lightBg: 'bg-violet-50/50',
    border: 'border-violet-200/60',
    badge: 'bg-violet-100 text-violet-800 border-violet-200',
    iconContainer: 'bg-gradient-to-br from-violet-50 to-purple-100/80 border-violet-200/80 text-violet-600 group-hover:from-violet-100 group-hover:to-purple-200/80 group-hover:border-violet-300',
    iconColor: 'text-violet-600'
  }
};

const DEFAULT_THEME = CATEGORY_THEMES.government;

// Dynamic icon mapper for categories
const CategoryIcon: React.FC<{ categoryId: string; className?: string }> = ({ categoryId, className = "w-4 h-4" }) => {
  switch (categoryId) {
    case 'consultation': return <Scale className={className} />;
    case 'identity': return <Fingerprint className={className} />;
    case 'vehicle': return <Car className={className} />;
    case 'health': return <HeartPulse className={className} />;
    case 'welfare': return <Gift className={className} />;
    case 'government': return <Building2 className={className} />;
    case 'housing': return <Home className={className} />;
    case 'banking': return <CreditCard className={className} />;
    case 'postal': return <Mail className={className} />;
    case 'internet': return <Globe className={className} />;
    default: return <Sparkles className={className} />;
  }
};

// Dynamic icon mapper for individual services
const ServiceIcon: React.FC<{ iconName: string; className?: string }> = ({ iconName, className = "w-5 h-5 stroke-[1.8]" }) => {
  switch (iconName) {
    case 'Fingerprint': return <Fingerprint className={className} />;
    case 'Car': return <Car className={className} />;
    case 'HeartPulse': return <HeartPulse className={className} />;
    case 'Gift': return <Gift className={className} />;
    case 'Building2': return <Building2 className={className} />;
    case 'Home': return <Home className={className} />;
    case 'Mail': return <Mail className={className} />;
    case 'Globe': return <Globe className={className} />;
    case 'QrCode': return <QrCode className={className} />;
    case 'Shield': return <Shield className={className} />;
    case 'CheckCircle2': return <CheckCircle2 className={className} />;
    case 'BookLock': return <Lock className={className} />;
    case 'Gavel': return <Gavel className={className} />;
    case 'UserPlus': return <UserPlus className={className} />;
    case 'CalendarCheck': return <CalendarCheck className={className} />;
    case 'MailCheck': return <MailCheck className={className} />;
    case 'ShieldAlert': return <ShieldAlert className={className} />;
    case 'FileSpreadsheet': return <FileSpreadsheet className={className} />;
    case 'Key': return <Key className={className} />;
    case 'FileCheck2': return <FileCheck2 className={className} />;
    case 'AlertCircle': return <AlertCircle className={className} />;
    case 'KeyRound': return <KeyRound className={className} />;
    case 'AlertTriangle': return <AlertTriangle className={className} />;
    case 'UserX': return <UserX className={className} />;
    case 'Clock': return <Clock className={className} />;
    case 'BookOpen': return <BookOpen className={className} />;
    case 'IdCard': return <CreditCard className={className} />;
    case 'FileCheck': return <FileCheck className={className} />;
    case 'UserCheck': return <UserCheck className={className} />;
    case 'ShieldCheck': return <ShieldCheck className={className} />;
    case 'Activity': return <Activity className={className} />;
    case 'Stethoscope': return <Stethoscope className={className} />;
    case 'Receipt': return <Receipt className={className} />;
    case 'CreditCard': return <CreditCard className={className} />;
    case 'RotateCw': return <RotateCw className={className} />;
    case 'Fuel': return <Fuel className={className} />;
    case 'Users': return <Users className={className} />;
    case 'ShoppingBag': return <ShoppingBag className={className} />;
    case 'TrendingUp': return <TrendingUp className={className} />;
    case 'Briefcase': return <Briefcase className={className} />;
    case 'Award': return <Award className={className} />;
    case 'Scale': return <Scale className={className} />;
    case 'Calculator': return <Calculator className={className} />;
    case 'Building': return <Building className={className} />;
    case 'MapPin': return <MapPin className={className} />;
    case 'FileText': return <FileText className={className} />;
    case 'BarChart3': return <BarChart3 className={className} />;
    case 'Navigation': return <Navigation className={className} />;
    default: return <FileText className={className} />;
  }
};

export const ServicesCatalogView: React.FC<ServicesCatalogViewProps> = ({
  services,
  initialCategoryId = null,
  searchQuery: externalSearchQuery = '',
  onSelectService
}) => {
  const [selectedCategoryId, setSelectedCategoryId] = useState<string | null>(initialCategoryId);
  const [searchQuery, setSearchQuery] = useState<string>(externalSearchQuery);
  const categoryScrollRef = useRef<HTMLDivElement>(null);
  const [isDragging, setIsDragging] = useState(false);
  const [startX, setStartX] = useState(0);
  const [scrollStart, setScrollStart] = useState(0);
  const [hasMoved, setHasMoved] = useState(false);

  // Sync if initialCategoryId changes
  useEffect(() => {
    if (initialCategoryId !== undefined) {
      setSelectedCategoryId(initialCategoryId);
    }
  }, [initialCategoryId]);

  // Sync if external search changes
  useEffect(() => {
    if (externalSearchQuery !== undefined) {
      setSearchQuery(externalSearchQuery);
    }
  }, [externalSearchQuery]);

  // Auto-scroll selected tab into view
  useEffect(() => {
    if (selectedCategoryId && categoryScrollRef.current) {
      const activeEl = categoryScrollRef.current.querySelector(`[data-cat-id="${selectedCategoryId}"]`) as HTMLElement;
      if (activeEl) {
        activeEl.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
      }
    }
  }, [selectedCategoryId]);

  // Horizontal wheel scrolling
  useEffect(() => {
    const el = categoryScrollRef.current;
    if (!el) return;

    const handleWheel = (e: WheelEvent) => {
      if (e.deltaY !== 0 && Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
        e.preventDefault();
        el.scrollLeft += e.deltaY * 0.8;
      }
    };

    el.addEventListener('wheel', handleWheel, { passive: false });
    return () => el.removeEventListener('wheel', handleWheel);
  }, []);

  const handleMouseDown = (e: React.MouseEvent) => {
    if (!categoryScrollRef.current) return;
    setIsDragging(true);
    setHasMoved(false);
    setStartX(e.pageX - categoryScrollRef.current.offsetLeft);
    setScrollStart(categoryScrollRef.current.scrollLeft);
  };

  const handleMouseMove = (e: React.MouseEvent) => {
    if (!isDragging || !categoryScrollRef.current) return;
    e.preventDefault();
    const x = e.pageX - categoryScrollRef.current.offsetLeft;
    const walk = (x - startX) * 1.5;
    if (Math.abs(walk) > 4) {
      setHasMoved(true);
    }
    categoryScrollRef.current.scrollLeft = scrollStart - walk;
  };

  const handleMouseUpOrLeave = () => {
    setIsDragging(false);
  };

  const handleTabClick = (catId: string | null) => {
    if (hasMoved) return;
    setSelectedCategoryId(prev => (prev === catId ? null : catId));
  };

  const newServices = services.filter(s => s.isNew);

  const allCategoryTabs = [
    { id: 'new', title: 'خدمات جدید', iconId: 'new', isNewBadge: true },
    ...CATEGORIES.map(c => ({ id: c.id, title: c.shortTitle || c.title, iconId: c.id, isNewBadge: false }))
  ];

  const isSearching = searchQuery.trim().length > 0;
  const searchedServices = isSearching
    ? services.filter(s => 
        s.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        s.department.toLowerCase().includes(searchQuery.toLowerCase()) ||
        s.description.toLowerCase().includes(searchQuery.toLowerCase())
      )
    : [];

  return (
    <div className="space-y-6 animate-in fade-in duration-200 pb-16">
      
      {/* Search Bar - Modern, refined with subtle glow */}
      <div className="relative">
        <div className="relative flex items-center bg-white rounded-2xl border border-slate-200/80 shadow-xs focus-within:shadow-md focus-within:border-emerald-500/50 px-3.5 py-3 transition-all duration-200">
          <Search className="w-4 h-4 text-emerald-600 shrink-0 ml-2.5 stroke-[2.2]" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="جستجوی خدمات، مدارک، استعلامات و سازمان‌ها..."
            className="w-full bg-transparent text-[13px] text-slate-900 placeholder-slate-400 focus:outline-none text-right font-medium"
          />
          {searchQuery && (
            <button 
              onClick={() => setSearchQuery('')}
              className="p-1 text-slate-400 hover:text-slate-600 rounded-full transition-colors cursor-pointer"
            >
              <X className="w-4 h-4" />
            </button>
          )}
        </div>
      </div>

      {/* Horizontal Category Carousel with Icons and Color Accents */}
      <div className="relative -mx-3.5 px-3.5 sm:-mx-4 sm:px-4 sticky top-14 z-20">
        <div 
          ref={categoryScrollRef}
          onMouseDown={handleMouseDown}
          onMouseMove={handleMouseMove}
          onMouseUp={handleMouseUpOrLeave}
          onMouseLeave={handleMouseUpOrLeave}
          className={`flex items-center gap-2 overflow-x-auto no-scrollbar py-1.5 bg-slate-50/95 backdrop-blur-md touch-pan-x select-none scroll-smooth cursor-grab active:cursor-grabbing ${
            isDragging ? 'cursor-grabbing' : ''
          }`}
          style={{ WebkitOverflowScrolling: 'touch' }}
        >
          {/* 'All' button */}
          <button
            onClick={() => setSelectedCategoryId(null)}
            className={`px-3.5 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 cursor-pointer shrink-0 active:scale-95 ${
              selectedCategoryId === null
                ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/25 ring-1 ring-emerald-500'
                : 'bg-white text-slate-700 border border-slate-200/80 hover:bg-slate-100/80 hover:text-slate-900 shadow-xs'
            }`}
          >
            <LayoutGrid className="w-3.5 h-3.5" />
            <span>همه دسته‌ها</span>
          </button>

          {allCategoryTabs.map((tab) => {
            const isActive = selectedCategoryId === tab.id;
            const theme = CATEGORY_THEMES[tab.id] || DEFAULT_THEME;

            return (
              <button
                key={tab.id}
                data-cat-id={tab.id}
                onClick={() => handleTabClick(tab.id)}
                className={`px-3.5 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 cursor-pointer shrink-0 active:scale-95 select-none ${
                  isActive
                    ? 'bg-slate-900 text-white shadow-md ring-1 ring-slate-800'
                    : 'bg-white text-slate-700 border border-slate-200/80 hover:bg-slate-50 hover:text-slate-950 shadow-xs'
                }`}
              >
                <div className={`w-5 h-5 rounded-lg flex items-center justify-center transition-colors ${
                  isActive 
                    ? 'bg-white/20 text-white' 
                    : `${theme.lightBg} ${theme.text}`
                }`}>
                  <CategoryIcon categoryId={tab.iconId} className="w-3.5 h-3.5 stroke-[2]" />
                </div>
                <span>{tab.title}</span>
                {tab.isNewBadge && !isActive && (
                  <span className="w-2 h-2 rounded-full bg-amber-500" />
                )}
              </button>
            );
          })}
        </div>
      </div>

      {/* SEARCH MODE */}
      {isSearching ? (
        <div className="space-y-4 pt-1">
          <div className="flex items-center justify-between text-xs text-slate-500 px-1">
            <span>نتایج برای «{searchQuery}»</span>
            <span className="font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2.5 py-0.5 rounded-full">{searchedServices.length} خدمت</span>
          </div>

          {searchedServices.length === 0 ? (
            <div className="bg-white rounded-3xl p-10 text-center border border-slate-200/80 text-slate-400 text-xs shadow-xs space-y-2">
              <Search className="w-8 h-8 text-slate-300 mx-auto stroke-[1.5]" />
              <p className="font-medium text-slate-600">خدمتی با این عنوان یافت نشد</p>
              <p className="text-[11px] text-slate-400">می‌توانید با عبارات دیگری مانند «پلاک»، «مالیات»، «بیمه» یا «کارت ملی» جستجو کنید.</p>
            </div>
          ) : (
            <div className="grid grid-cols-3 sm:grid-cols-4 gap-3 sm:gap-4 py-2">
              {searchedServices.map((service) => {
                const theme = CATEGORY_THEMES[service.categoryId] || DEFAULT_THEME;
                return (
                  <motion.div
                    key={service.id}
                    whileHover={{ y: -3 }}
                    whileTap={{ scale: 0.95 }}
                    onClick={() => onSelectService(service)}
                    className="flex flex-col items-center text-center cursor-pointer group bg-white hover:bg-slate-50/80 p-3 sm:p-3.5 rounded-2xl border border-slate-200/70 hover:border-slate-300 shadow-xs hover:shadow-sm transition-all duration-200 relative"
                  >
                    <div className={`w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center transition-all duration-200 shadow-xs border relative ${theme.iconContainer}`}>
                      <ServiceIcon iconName={service.icon} className="w-5.5 h-5.5 sm:w-6 sm:h-6 stroke-[1.8]" />
                      {service.isNew && (
                        <span className="absolute -top-1.5 -right-1.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[8px] font-black px-1.5 py-0.5 rounded-full shadow-xs">
                          جدید
                        </span>
                      )}
                    </div>
                    <span className="text-[11.5px] sm:text-xs font-bold text-slate-800 group-hover:text-emerald-700 leading-snug line-clamp-2 mt-2.5 px-0.5 transition-colors">
                      {service.title}
                    </span>
                    <span className="text-[9.5px] text-slate-400 mt-1 font-medium truncate max-w-full">
                      {service.department}
                    </span>
                  </motion.div>
                );
              })}
            </div>
          )}
        </div>
      ) : (
        /* VIBRANT, BEAUTIFULLY STYLED CATEGORIZED VIEW */
        <div className="space-y-6 pt-1">
          
          {/* SECTION: NEW SERVICES */}
          {(!selectedCategoryId || selectedCategoryId === 'new') && newServices.length > 0 && (
            <div id="cat-section-new" className="bg-white rounded-3xl p-4 sm:p-5 border border-amber-200/60 shadow-xs space-y-4 text-right relative overflow-hidden">
              <div className="absolute top-0 right-0 w-32 h-32 bg-amber-100/30 rounded-full blur-2xl pointer-events-none" />
              
              {/* Header */}
              <div className="flex items-center justify-between relative z-10">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center shadow-sm shadow-amber-500/20">
                    <Sparkles className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="font-extrabold text-sm text-slate-900">
                      خدمات جدید و پرکاربرد
                    </h3>
                    <p className="text-[11px] text-slate-500 mt-0.5">
                      تازه‌ترین خدمات الکترونیک اضافه شده به پیشخوان
                    </p>
                  </div>
                </div>
                <span className="text-[11px] font-bold text-amber-800 bg-amber-100/80 px-2.5 py-1 rounded-xl border border-amber-200/70">
                  {newServices.length} خدمت
                </span>
              </div>

              {/* Grid of New Services */}
              <div className="grid grid-cols-3 sm:grid-cols-4 gap-2.5 sm:gap-3.5 pt-1">
                {newServices.map((service) => {
                  const theme = CATEGORY_THEMES[service.categoryId] || CATEGORY_THEMES.new;
                  return (
                    <motion.div
                      key={`new-${service.id}`}
                      whileHover={{ y: -2 }}
                      whileTap={{ scale: 0.95 }}
                      onClick={() => onSelectService(service)}
                      className="flex flex-col items-center text-center cursor-pointer group bg-amber-50/30 hover:bg-amber-50/80 p-2.5 sm:p-3 rounded-2xl border border-amber-100/80 hover:border-amber-200 transition-all duration-200"
                    >
                      <div className={`w-12 h-12 sm:w-13 sm:h-13 rounded-2xl flex items-center justify-center transition-all duration-200 shadow-xs border relative ${theme.iconContainer}`}>
                        <ServiceIcon iconName={service.icon} className="w-5.5 h-5.5 stroke-[1.8]" />
                        <span className="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-amber-500 ring-2 ring-white" />
                      </div>
                      <span className="text-[11.5px] sm:text-xs font-bold text-slate-800 group-hover:text-amber-800 leading-snug line-clamp-2 mt-2 px-0.5 transition-colors">
                        {service.title}
                      </span>
                    </motion.div>
                  );
                })}
              </div>
            </div>
          )}

          {/* STANDARD CATEGORIES AS RICH CARDS */}
          {CATEGORIES.map((category) => {
            if (selectedCategoryId && selectedCategoryId !== category.id) {
              return null;
            }

            const categoryServices = services.filter(s => s.categoryId === category.id);
            if (categoryServices.length === 0) return null;

            const theme = CATEGORY_THEMES[category.id] || DEFAULT_THEME;

            return (
              <div 
                key={category.id} 
                id={`cat-section-${category.id}`} 
                className="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/70 shadow-xs hover:border-slate-300 transition-all duration-200 space-y-4 text-right relative overflow-hidden"
              >
                {/* Header */}
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2.5">
                    <div className={`w-8 h-8 rounded-xl flex items-center justify-center shadow-xs border ${theme.iconContainer}`}>
                      <CategoryIcon categoryId={category.id} className="w-4 h-4 stroke-[2]" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h3 className="font-extrabold text-sm text-slate-900">
                          {category.title}
                        </h3>
                        {category.badge && (
                          <span className={`text-[9px] font-black px-2 py-0.5 rounded-full border ${theme.badge}`}>
                            {category.badge}
                          </span>
                        )}
                      </div>
                      {category.description && (
                        <p className="text-[11px] text-slate-500 mt-0.5 line-clamp-1">
                          {category.description}
                        </p>
                      )}
                    </div>
                  </div>

                  <span className="text-[11px] font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-xl">
                    {categoryServices.length} خدمت
                  </span>
                </div>

                {/* Grid of Category Services */}
                <div className="grid grid-cols-3 sm:grid-cols-4 gap-2.5 sm:gap-3.5 pt-1">
                  {categoryServices.map((service) => (
                    <motion.div
                      key={service.id}
                      whileHover={{ y: -2 }}
                      whileTap={{ scale: 0.95 }}
                      onClick={() => onSelectService(service)}
                      className="flex flex-col items-center text-center cursor-pointer group bg-slate-50/50 hover:bg-slate-50 p-2.5 sm:p-3 rounded-2xl border border-slate-100 hover:border-slate-200/90 transition-all duration-200"
                    >
                      <div className={`w-12 h-12 sm:w-13 sm:h-13 rounded-2xl flex items-center justify-center transition-all duration-200 shadow-xs border relative ${theme.iconContainer}`}>
                        <ServiceIcon iconName={service.icon} className="w-5.5 h-5.5 stroke-[1.8]" />
                        {service.isNew && (
                          <span className="absolute -top-1 -right-1 bg-amber-500 text-white text-[8px] font-black px-1.5 py-0.2 rounded-full shadow-xs">
                            جدید
                          </span>
                        )}
                      </div>
                      <span className="text-[11.5px] sm:text-xs font-bold text-slate-800 group-hover:text-slate-950 leading-snug line-clamp-2 mt-2 px-0.5 transition-colors">
                        {service.title}
                      </span>
                    </motion.div>
                  ))}
                </div>
              </div>
            );
          })}

        </div>
      )}

    </div>
  );
};

export default ServicesCatalogView;


