import React, { useState } from 'react';
import { 
  User, 
  ShieldCheck, 
  Award, 
  FileText, 
  PlusCircle, 
  Calendar, 
  QrCode, 
  CheckCircle2, 
  Lock, 
  PhoneCall, 
  Sparkles,
  IdCard,
  X,
  Eye,
  Check,
  Fingerprint,
  BellRing,
  Clock,
  Smartphone,
  MapPin,
  Volume2,
  Navigation,
  Sliders,
  AlertCircle,
  Bell,
  ArrowRight,
  ChevronLeft,
  ChevronRight,
  Pencil,
  Plane,
  Plus,
  Settings,
  HelpCircle,
  Info,
  LogOut,
  Save,
  Trash2,
  Share2,
  Download,
  CheckCheck,
  Send,
  Building2,
  CreditCard,
  BookOpen,
  FolderLock,
  FileCheck,
  Truck,
  MessageSquare
} from 'lucide-react';
import { CitizenProfile, DocumentItem, Appointment, ChatMessage, CaseRequest } from '../types';
import { CATEGORIES } from '../data/mockData';
import avatarImg from '../assets/images/avatar_user_profile_1788037610243.jpg';

export type ProfileSubPage = 
  | 'main'
  | 'personal_info'
  | 'appointments'
  | 'smart_reminder'
  | 'documents_vault'
  | 'messages_and_notices'
  | 'settings'
  | 'support'
  | 'about';

interface UserProfileViewProps {
  profile: CitizenProfile;
  appointments: Appointment[];
  onUpgradeTier: () => void;
  onAddDocument: (newDoc: DocumentItem) => void;
  onUpdateProfile?: (updated: Partial<CitizenProfile>) => void;
  onBackToHome?: () => void;
  onCancelAppointment?: (appId: string) => void;
  onSwitchToOfficeDesk?: () => void;
  onLogout?: () => void;
  messages?: ChatMessage[];
  cases?: CaseRequest[];
  onSendMessage?: (caseId: string, text: string) => void;
}

export const UserProfileView: React.FC<UserProfileViewProps> = ({
  profile,
  appointments,
  onUpgradeTier,
  onAddDocument,
  onUpdateProfile,
  onBackToHome,
  onCancelAppointment,
  onSwitchToOfficeDesk,
  onLogout,
  messages = [],
  cases = [],
  onSendMessage
}) => {
  // Navigation between Main Profile Screen and Menu Sub-Pages
  const [activeSubPage, setActiveSubPage] = useState<ProfileSubPage>('main');
  const [noticeTab, setNoticeTab] = useState<'notices' | 'sms' | 'office_chats'>('notices');

  // Document details & Add modals
  const [selectedDoc, setSelectedDoc] = useState<DocumentItem | null>(null);
  const [showAddDocModal, setShowAddDocModal] = useState(false);
  const [showUpgradeModal, setShowUpgradeModal] = useState(false);
  const [upgradeSuccess, setUpgradeSuccess] = useState(false);
  const [showLogoutModal, setShowLogoutModal] = useState(false);
  const [selectedCategoryFilter, setSelectedCategoryFilter] = useState<string>('all');

  // Smart 1-Hour Reminder States
  const [reminderLeadMinutes, setReminderLeadMinutes] = useState<number>(60); // 1 hour default
  const [isReminderActive, setIsReminderActive] = useState<boolean>(true);
  const [enableSms, setEnableSms] = useState<boolean>(true);
  const [enablePush, setEnablePush] = useState<boolean>(true);
  const [enableSound, setEnableSound] = useState<boolean>(true);
  const [enableTrafficCalc, setEnableTrafficCalc] = useState<boolean>(true);

  // Active Appointment Test & Simulation Modal
  const [activeAlertAppointment, setActiveAlertAppointment] = useState<Appointment | null>(null);
  const [showReminderSettingsModal, setShowReminderSettingsModal] = useState(false);
  const [showLiveNotificationBanner, setShowLiveNotificationBanner] = useState(false);
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  // Personal Info Form State (Editable)
  const [editFullName, setEditFullName] = useState(profile.fullName);
  const [editNationalId, setEditNationalId] = useState(profile.nationalId);
  const [editMobile, setEditMobile] = useState(profile.mobile);
  const [editFatherName, setEditFatherName] = useState(profile.fatherName);
  const [editBirthDate, setEditBirthDate] = useState(profile.birthDate);
  const [editPostalCode, setEditPostalCode] = useState(profile.postalCode);
  const [editAddress, setEditAddress] = useState(profile.address);

  // New doc form
  const [newDocTitle, setNewDocTitle] = useState('');
  const [newDocType, setNewDocType] = useState('ملکی و سکونت');
  const [newDocNumber, setNewDocNumber] = useState('');

  // Support inquiry form
  const [inquirySubject, setInquirySubject] = useState('');
  const [inquiryText, setInquiryText] = useState('');
  const [inquirySent, setInquirySent] = useState(false);

  // Play audio chime for 1-hour alert simulation
  const playAlertChime = () => {
    try {
      const AudioCtx = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      
      const osc1 = ctx.createOscillator();
      const osc2 = ctx.createOscillator();
      const gain = ctx.createGain();

      osc1.type = 'sine';
      osc1.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
      osc1.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5

      osc2.type = 'triangle';
      osc2.frequency.setValueAtTime(293.66, ctx.currentTime); // D4
      osc2.frequency.setValueAtTime(440, ctx.currentTime + 0.15); // A4

      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.9);

      osc1.connect(gain);
      osc2.connect(gain);
      gain.connect(ctx.destination);

      osc1.start();
      osc2.start();
      osc1.stop(ctx.currentTime + 0.9);
      osc2.stop(ctx.currentTime + 0.9);
    } catch {
      // Audio context fallback
    }
  };

  const handleTriggerTestReminder = (app: Appointment) => {
    setActiveAlertAppointment(app);
    setShowLiveNotificationBanner(true);
    if (enableSound) {
      playAlertChime();
    }
    setToastMessage('پیام هشدار یادآوری ۱ ساعت قبل با موفقیت شبیه‌سازی شد!');
    setTimeout(() => {
      setToastMessage(null);
    }, 4000);
  };

  const getDocIllustration = (categoryName: string) => {
    if (categoryName.includes('هویتی') || categoryName.includes('سجلی')) {
      return CATEGORIES.find(c => c.id === 'identity')?.image;
    }
    if (categoryName.includes('خودرو') || categoryName.includes('گواهینامه') || categoryName.includes('رانندگی')) {
      return CATEGORIES.find(c => c.id === 'vehicle')?.image;
    }
    if (categoryName.includes('ملک') || categoryName.includes('مسکن') || categoryName.includes('سکونت')) {
      return CATEGORIES.find(c => c.id === 'housing')?.image;
    }
    if (categoryName.includes('پزشکی') || categoryName.includes('سلامت') || categoryName.includes('بهداشت')) {
      return CATEGORIES.find(c => c.id === 'health')?.image;
    }
    if (categoryName.includes('مالی') || categoryName.includes('بانک') || categoryName.includes('سفته')) {
      return CATEGORIES.find(c => c.id === 'banking')?.image;
    }
    return CATEGORIES.find(c => c.id === 'government')?.image;
  };

  const handleCreateDocument = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newDocTitle || !newDocNumber) return;

    const newDoc: DocumentItem = {
      id: `doc-${Date.now()}`,
      title: newDocTitle,
      type: 'سند رسمی',
      docNumber: newDocNumber,
      issueDate: '۱۴۰۳/۰۶/۰۱',
      isVerified: true,
      category: newDocType,
      attributes: [
        { label: 'مرجع صادرکننده', value: 'سامانه ثبت اسناد و درگاه ملی' },
        { label: 'وضعیت در سامانه', value: 'تایید اصالت برخط' }
      ]
    };

    onAddDocument(newDoc);
    setNewDocTitle('');
    setNewDocNumber('');
    setShowAddDocModal(false);
    setToastMessage('سند جدید با موفقیت به مخزن اضافه شد.');
    setTimeout(() => setToastMessage(null), 3000);
  };

  const handleSavePersonalInfo = (e: React.FormEvent) => {
    e.preventDefault();
    if (onUpdateProfile) {
      onUpdateProfile({
        fullName: editFullName,
        nationalId: editNationalId,
        mobile: editMobile,
        fatherName: editFatherName,
        birthDate: editBirthDate,
        postalCode: editPostalCode,
        address: editAddress
      });
    }
    setToastMessage('اطلاعات هویتی با موفقیت ذخیره و بروزرسانی شد.');
    setTimeout(() => setToastMessage(null), 3000);
  };

  const handleExecuteUpgrade = () => {
    setUpgradeSuccess(true);
    setTimeout(() => {
      onUpgradeTier();
      setShowUpgradeModal(false);
      setUpgradeSuccess(false);
      setToastMessage('تبریک! ارتقای حساب به شهروند طلایی با موفقیت فعال شد.');
      setTimeout(() => setToastMessage(null), 3000);
    }, 1200);
  };

  const handleQuickDocClick = (docTypeKey: 'nid' | 'birth' | 'passport') => {
    if (docTypeKey === 'nid') {
      const doc = profile.documents.find(d => d.title.includes('ملی') || d.type.includes('national')) || profile.documents[0];
      if (doc) setSelectedDoc(doc);
      else {
        setActiveSubPage('documents_vault');
      }
    } else if (docTypeKey === 'birth') {
      const doc = profile.documents.find(d => d.title.includes('شناسنامه') || d.type.includes('birth')) || profile.documents[1] || profile.documents[0];
      if (doc) setSelectedDoc(doc);
      else {
        setActiveSubPage('documents_vault');
      }
    } else if (docTypeKey === 'passport') {
      const doc = profile.documents.find(d => d.title.includes('گذرنامه') || d.title.includes('گواهینامه')) || profile.documents[2] || profile.documents[0];
      if (doc) setSelectedDoc(doc);
      else {
        setActiveSubPage('documents_vault');
      }
    }
  };

  const activeApp = appointments.find(a => a.status === 'active') || appointments[0];

  const filteredDocs = selectedCategoryFilter === 'all' 
    ? profile.documents 
    : profile.documents.filter(d => d.category.includes(selectedCategoryFilter));

  return (
    <div className="space-y-4 text-right select-none" dir="rtl">
      
      {/* Toast Notification Alert */}
      {toastMessage && (
        <div className="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 bg-slate-900 text-white px-4 py-2.5 rounded-2xl shadow-2xl border border-indigo-500/40 text-xs font-bold flex items-center gap-2 animate-in slide-in-from-top duration-300">
          <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
          <span>{toastMessage}</span>
        </div>
      )}

      {/* Floating Incoming Notification Banner Simulator (Live Push) */}
      {showLiveNotificationBanner && activeAlertAppointment && (
        <div className="fixed top-4 right-4 left-4 sm:left-auto sm:right-6 sm:w-96 z-50 bg-slate-900/95 text-white p-4 rounded-3xl shadow-2xl border border-indigo-500/40 backdrop-blur-md animate-in slide-in-from-top-4 duration-300">
          <div className="flex items-start justify-between gap-3">
            <div className="flex items-start gap-2.5">
              <div className="w-9 h-9 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-md">
                <BellRing className="w-5 h-5 animate-bounce" />
              </div>
              <div className="space-y-1">
                <div className="flex items-center gap-1.5 text-[11px] font-black text-indigo-300">
                  <span>سامانه هوشمند پیشخوان دولت</span>
                  <span className="w-1 h-1 rounded-full bg-indigo-400" />
                  <span className="text-amber-300 font-bold">۱ ساعت مانده به نوبت</span>
                </div>
                <h5 className="text-xs font-black text-white leading-tight">
                  یادآوری مراجعه حضوری: {activeAlertAppointment.serviceTitle}
                </h5>
                <p className="text-[11px] text-slate-300 leading-relaxed">
                  نوبت شما ساعت {activeAlertAppointment.timeSlot.split(' ')[0]} در {activeAlertAppointment.officeName} است. لطفاً حرکت خود را آغاز فرمایید.
                </p>
              </div>
            </div>
            <button 
              onClick={() => setShowLiveNotificationBanner(false)}
              className="text-slate-400 hover:text-white p-1 cursor-pointer"
            >
              <X className="w-4 h-4" />
            </button>
          </div>

          <div className="flex items-center justify-end gap-2 mt-3 pt-2.5 border-t border-slate-800 text-xs">
            <button
              onClick={() => {
                setShowLiveNotificationBanner(false);
                setActiveAlertAppointment(activeAlertAppointment);
              }}
              className="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 rounded-xl font-bold text-[11px] transition-colors cursor-pointer"
            >
              مشاهده جزئیات و متن پیامک
            </button>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* 1. TOP HEADER                                                             */}
      {/* ========================================================================= */}
      <div className="flex items-center justify-between py-2 px-1">
        {/* Right Side in RTL: Back Button */}
        <button 
          onClick={() => {
            if (activeSubPage !== 'main') {
              setActiveSubPage('main');
            } else if (onBackToHome) {
              onBackToHome();
            }
          }}
          className="w-10 h-10 rounded-full flex items-center justify-center text-blue-900 hover:bg-blue-50 active:scale-95 transition-all cursor-pointer"
          title={activeSubPage === 'main' ? 'بازگشت به خانه' : 'بازگشت به منوی کاربری'}
        >
          <ArrowRight className="w-6 h-6" />
        </button>

        {/* Center Title */}
        <h1 className="text-lg sm:text-xl font-black text-blue-950 text-center tracking-tight">
          {activeSubPage === 'main' && 'حساب کاربری'}
          {activeSubPage === 'personal_info' && 'اطلاعات فردی و هویتی'}
          {activeSubPage === 'appointments' && 'نوبت‌های حضوری رزرو شده'}
          {activeSubPage === 'smart_reminder' && 'سامانه یادآور هوشمند نوبت'}
          {activeSubPage === 'documents_vault' && 'مخزن اسناد و مدارک'}
          {activeSubPage === 'settings' && 'تنظیمات حساب کاربری'}
          {activeSubPage === 'support' && 'پشتیبانی و راهنما'}
          {activeSubPage === 'about' && 'درباره درگاه پیشخوان'}
        </h1>

        {/* Left Side in RTL: Notification Bell */}
        <button 
          onClick={() => {
            if (activeApp) {
              handleTriggerTestReminder(activeApp);
            } else {
              setToastMessage('اعلان جدیدی وجود ندارد.');
              setTimeout(() => setToastMessage(null), 2500);
            }
          }}
          className="w-10 h-10 rounded-full flex items-center justify-center text-blue-900 hover:bg-blue-50 active:scale-95 transition-all cursor-pointer relative"
          title="اعلان‌ها و هشدارها"
        >
          <Bell className="w-6 h-6" />
          {appointments.length > 0 && (
            <span className="w-2.5 h-2.5 rounded-full bg-blue-600 absolute top-2 left-2 ring-2 ring-white" />
          )}
        </button>
      </div>

      {/* ========================================================================= */}
      {/* 2. MAIN VIEW                                                              */}
      {/* ========================================================================= */}
      {activeSubPage === 'main' && (
        <div className="space-y-4 animate-in fade-in duration-200">
          
          {/* Card 1: User Profile Header Card */}
          <div className="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-100 flex items-center justify-between">
            {/* Right side in RTL (First child): Avatar + Name + Phone */}
            <div className="flex items-center gap-3.5">
              {/* Avatar with Circular Blue Border & Golden Badge */}
              <div className="relative flex flex-col items-center shrink-0">
                <div className="w-15 h-15 sm:w-16 sm:h-16 rounded-full border-2 border-blue-500 overflow-hidden shadow-xs bg-slate-100 flex items-center justify-center">
                  <img
                    src={avatarImg}
                    alt={profile.fullName}
                    referrerPolicy="no-referrer"
                    className="w-full h-full object-cover"
                  />
                </div>
                {/* Badge underneath Avatar */}
                <div className="mt-[-9px] bg-slate-900 text-cyan-300 border border-slate-700 text-[9px] font-black px-2 py-0.5 rounded-full shadow-xs">
                  {profile.tier === 'gold' ? 'کاربر طلایی VIP' : 'کاربر طلایی'}
                </div>
              </div>

              <div className="text-right space-y-0.5">
                <h2 className="text-base sm:text-lg font-black text-slate-900">
                  {profile.fullName}
                </h2>
                <p className="text-xs font-mono font-medium text-slate-400">
                  {profile.mobile}
                </p>
              </div>
            </div>

            {/* Left side in RTL (Second child): Edit Pencil Button */}
            <button
              onClick={() => setActiveSubPage('personal_info')}
              className="w-10 h-10 rounded-2xl bg-blue-50/70 hover:bg-blue-100 flex items-center justify-center text-blue-800 active:scale-95 transition-all cursor-pointer shrink-0"
              title="ویرایش اطلاعات فردی"
            >
              <Pencil className="w-4.5 h-4.5" />
            </button>
          </div>

          {/* Card 2: Profile Completion Card (تکمیل پروفایل) */}
          <div 
            onClick={() => setActiveSubPage('personal_info')}
            className="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-100 space-y-2.5 cursor-pointer hover:border-blue-200 transition-all group"
          >
            <div className="flex items-center justify-between">
              <h3 className="text-xs sm:text-sm font-black text-slate-900 group-hover:text-blue-700 transition-colors">
                تکمیل پروفایل
              </h3>
              <span className="text-xs sm:text-sm font-black text-blue-800 font-mono">
                ۸۵٪
              </span>
            </div>

            {/* Progress Bar */}
            <div className="w-full h-2.5 bg-blue-100/70 rounded-full overflow-hidden">
              <div className="h-full bg-blue-800 rounded-full w-[85%] transition-all duration-500" />
            </div>

            <p className="text-[11px] sm:text-xs text-slate-500 font-medium text-right">
              برای ارتقا به سطح ویژه، اطلاعات تماس خود را تکمیل کنید.
            </p>
          </div>

          {/* Section: Comprehensive Clean Menu List */}
          <div className="bg-white rounded-3xl shadow-xs border border-slate-100 divide-y divide-slate-100 overflow-hidden">
            
            {/* Menu 1: اطلاعات فردی و هویتی */}
            <button
              onClick={() => setActiveSubPage('personal_info')}
              className="w-full p-4 flex items-center justify-between hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer text-right group"
            >
              {/* Right Side: Icon + Title & Subtitle */}
              <div className="flex items-center gap-3.5">
                <div className="w-9 h-9 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center shrink-0 group-hover:bg-slate-100 transition-colors">
                  <User className="w-4.5 h-4.5" />
                </div>
                <div className="text-right">
                  <span className="text-xs sm:text-sm font-black text-slate-900 group-hover:text-blue-700 transition-colors block">
                    اطلاعات فردی و هویتی
                  </span>
                  <span className="text-[11px] text-slate-400 block mt-0.5">
                    کد ملی، نشانی، احراز هویت و امضای دیجیتال
                  </span>
                </div>
              </div>

              {/* Left Side: Chevron Arrow */}
              <div className="flex items-center gap-2 shrink-0">
                <ChevronLeft className="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-colors" />
              </div>
            </button>

            {/* Menu 2: نوبت‌های حضوری رزرو شده */}
            <button
              onClick={() => setActiveSubPage('appointments')}
              className="w-full p-4 flex items-center justify-between hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer text-right group"
            >
              {/* Right Side: Icon + Title & Subtitle */}
              <div className="flex items-center gap-3.5">
                <div className="w-9 h-9 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center shrink-0 group-hover:bg-slate-100 transition-colors">
                  <Calendar className="w-4.5 h-4.5" />
                </div>
                <div className="text-right">
                  <span className="text-xs sm:text-sm font-black text-slate-900 group-hover:text-blue-700 transition-colors block">
                    نوبت‌های حضوری رزرو شده
                  </span>
                  <span className="text-[11px] text-slate-400 block mt-0.5">
                    کارت ورود، بارکد نوبت، باجه پذیرش و ساعت مراجعه
                  </span>
                </div>
              </div>

              {/* Left Side: Badge + Chevron */}
              <div className="flex items-center gap-2 shrink-0">
                <span className="bg-slate-100 text-slate-700 text-[10px] font-black px-2 py-0.5 rounded-full border border-slate-200">
                  {appointments.length} نوبت فعال
                </span>
                <ChevronLeft className="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-colors" />
              </div>
            </button>

            {/* Menu 3: سامانه یادآور هوشمند نوبت */}
            <button
              onClick={() => setActiveSubPage('smart_reminder')}
              className="w-full p-4 flex items-center justify-between hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer text-right group"
            >
              {/* Right Side: Icon + Title & Subtitle */}
              <div className="flex items-center gap-3.5">
                <div className="w-9 h-9 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center shrink-0 group-hover:bg-slate-100 transition-colors">
                  <BellRing className="w-4.5 h-4.5" />
                </div>
                <div className="text-right">
                  <span className="text-xs sm:text-sm font-black text-slate-900 group-hover:text-blue-700 transition-colors block">
                    سامانه یادآور هوشمند نوبت
                  </span>
                  <span className="text-[11px] text-slate-400 block mt-0.5">
                    ارسال خودکار پیامک ۱ ساعت قبل، اعلان و نقشه ترافیک
                  </span>
                </div>
              </div>

              {/* Left Side: Badge + Chevron */}
              <div className="flex items-center gap-2 shrink-0">
                <span className="bg-slate-100 text-slate-700 text-[10px] font-black px-2 py-0.5 rounded-full border border-slate-200 flex items-center gap-1">
                  <Clock className="w-2.5 h-2.5" />
                  {reminderLeadMinutes} دقیقه قبل
                </span>
                <ChevronLeft className="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-colors" />
              </div>
            </button>

            {/* Menu 4: مخزن کامل اسناد و مدارک */}
            <button
              onClick={() => setActiveSubPage('documents_vault')}
              className="w-full p-4 flex items-center justify-between hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer text-right group"
            >
              {/* Right Side: Icon + Title & Subtitle */}
              <div className="flex items-center gap-3.5">
                <div className="w-9 h-9 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center shrink-0 group-hover:bg-slate-100 transition-colors">
                  <FolderLock className="w-4.5 h-4.5" />
                </div>
                <div className="text-right">
                  <span className="text-xs sm:text-sm font-black text-slate-900 group-hover:text-blue-700 transition-colors block">
                    مخزن اسناد و مدارک
                  </span>
                  <span className="text-[11px] text-slate-400 block mt-0.5">
                    مشاهده، استعلام برخط، صدور رونوشت و افزودن مدرک
                  </span>
                </div>
              </div>

              {/* Left Side: Badge + Chevron */}
              <div className="flex items-center gap-2 shrink-0">
                <span className="bg-slate-100 text-slate-700 text-[10px] font-black px-2 py-0.5 rounded-full border border-slate-200">
                  {profile.documents.length} مدرک
                </span>
                <ChevronLeft className="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-colors" />
              </div>
            </button>

            {/* Menu: میز کار و پنل دفاتر پیشخوان */}
            {onSwitchToOfficeDesk && (
              <button
                onClick={onSwitchToOfficeDesk}
                className="w-full p-4 flex items-center justify-between bg-gradient-to-r from-slate-900/5 to-emerald-500/5 hover:from-slate-900/10 hover:to-emerald-500/10 active:bg-slate-100 transition-colors cursor-pointer text-right group"
              >
                {/* Right Side: Icon + Title & Subtitle */}
                <div className="flex items-center gap-3.5">
                  <div className="w-9 h-9 rounded-2xl bg-slate-900 text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-all shadow-xs">
                    <Building2 className="w-4.5 h-4.5" />
                  </div>
                  <div className="text-right">
                    <span className="text-xs sm:text-sm font-black text-slate-900 group-hover:text-emerald-700 transition-colors block">
                      میز کار و پنل دفاتر پیشخوان
                    </span>
                    <span className="text-[11px] text-slate-500 block mt-0.5">
                      ورود به کارتابل اپراتوری، باجه پذیرش و بررسی پرونده‌ها
                    </span>
                  </div>
                </div>

                {/* Left Side: Badge + Chevron */}
                <div className="flex items-center gap-2 shrink-0">
                  <span className="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2.5 py-0.5 rounded-full border border-emerald-200">
                    ویژه دفاتر
                  </span>
                  <ChevronLeft className="w-4 h-4 text-slate-400 group-hover:text-emerald-600 transition-colors" />
                </div>
              </button>
            )}

            {/* Menu: پیام‌ها و ابلاغیه‌ها */}
            <button
              onClick={() => setActiveSubPage('messages_and_notices')}
              className="w-full p-4 flex items-center justify-between hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer text-right group"
            >
              {/* Right Side: Icon + Title & Subtitle */}
              <div className="flex items-center gap-3.5">
                <div className="w-9 h-9 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0 group-hover:bg-indigo-100 transition-colors">
                  <Bell className="w-4.5 h-4.5" />
                </div>
                <div className="text-right">
                  <span className="text-xs sm:text-sm font-black text-slate-900 group-hover:text-indigo-700 transition-colors block">
                    پیام‌ها و ابلاغیه‌ها
                  </span>
                  <span className="text-[11px] text-slate-500 block mt-0.5">
                    ابلاغیه‌های رسمی قضایی/دولتی، پیامک‌ها و گفتگوهای باجه
                  </span>
                </div>
              </div>

              {/* Left Side: Badge + Chevron */}
              <div className="flex items-center gap-2 shrink-0">
                <span className="bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded-full border border-amber-200">
                  ۲ پیام جدید
                </span>
                <ChevronLeft className="w-4 h-4 text-slate-400 group-hover:text-indigo-600 transition-colors" />
              </div>
            </button>

            {/* Menu 5: تنظیمات */}
            <button
              onClick={() => setActiveSubPage('settings')}
              className="w-full p-4 flex items-center justify-between hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer text-right group"
            >
              {/* Right Side: Icon + Title & Subtitle */}
              <div className="flex items-center gap-3.5">
                <div className="w-9 h-9 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center shrink-0 group-hover:bg-slate-100 transition-colors">
                  <Settings className="w-4.5 h-4.5" />
                </div>
                <div className="text-right">
                  <span className="text-xs sm:text-sm font-black text-slate-900 block">
                    تنظیمات و امنیت
                  </span>
                  <span className="text-[11px] text-slate-400 block mt-0.5">
                    ورود بیومتریک، رمز عبور و تنظیمات پیامک
                  </span>
                </div>
              </div>

              {/* Left Side: Chevron */}
              <div className="flex items-center gap-2 shrink-0">
                <ChevronLeft className="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
              </div>
            </button>

            {/* Menu 6: پشتیبانی و راهنما */}
            <button
              onClick={() => setActiveSubPage('support')}
              className="w-full p-4 flex items-center justify-between hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer text-right group"
            >
              {/* Right Side: Icon + Title & Subtitle */}
              <div className="flex items-center gap-3.5">
                <div className="w-9 h-9 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center shrink-0 group-hover:bg-slate-100 transition-colors">
                  <HelpCircle className="w-4.5 h-4.5" />
                </div>
                <div className="text-right">
                  <span className="text-xs sm:text-sm font-black text-slate-900 block">
                    پشتیبانی و راهنما
                  </span>
                  <span className="text-[11px] text-slate-400 block mt-0.5">
                    تماس با کارشناسان و ثبت تیکت پشتیبانی
                  </span>
                </div>
              </div>

              {/* Left Side: Chevron */}
              <div className="flex items-center gap-2 shrink-0">
                <ChevronLeft className="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
              </div>
            </button>

            {/* Menu 7: درباره ما */}
            <button
              onClick={() => setActiveSubPage('about')}
              className="w-full p-4 flex items-center justify-between hover:bg-slate-50 active:bg-slate-100 transition-colors cursor-pointer text-right group"
            >
              {/* Right Side: Icon + Title & Subtitle */}
              <div className="flex items-center gap-3.5">
                <div className="w-9 h-9 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center shrink-0 group-hover:bg-slate-100 transition-colors">
                  <Info className="w-4.5 h-4.5" />
                </div>
                <div className="text-right">
                  <span className="text-xs sm:text-sm font-black text-slate-900 block">
                    درباره درگاه پیشخوان
                  </span>
                  <span className="text-[11px] text-slate-400 block mt-0.5">
                    سامانه هوشمند پیشخوان دولت الکترونیک
                  </span>
                </div>
              </div>

              {/* Left Side: Chevron */}
              <div className="flex items-center gap-2 shrink-0">
                <ChevronLeft className="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
              </div>
            </button>

            {/* Menu 8: خروج از حساب کاربری */}
            <button
              onClick={() => setShowLogoutModal(true)}
              className="w-full p-4 flex items-center justify-between hover:bg-rose-50/60 active:bg-rose-50 transition-colors cursor-pointer text-right group"
            >
              {/* Right Side: Icon + Title */}
              <div className="flex items-center gap-3.5">
                <div className="w-9 h-9 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 group-hover:bg-rose-100 transition-colors">
                  <LogOut className="w-4.5 h-4.5" />
                </div>
                <div className="text-right">
                  <span className="text-xs sm:text-sm font-black text-rose-600 block">
                    خروج از حساب کاربری
                  </span>
                  <span className="text-[11px] text-rose-400/80 block mt-0.5">
                    خروج امن از نشست جاری سامانه
                  </span>
                </div>
              </div>

              {/* Left Side: Chevron */}
              <div className="flex items-center gap-2 shrink-0">
                <ChevronLeft className="w-4 h-4 text-rose-300 group-hover:text-rose-600 transition-colors" />
              </div>
            </button>

          </div>

        </div>
      )}

      {/* ========================================================================= */}
      {/* 3. SUB-PAGE: اطلاعات فردی و هویتی (Personal Info)                          */}
      {/* ========================================================================= */}
      {activeSubPage === 'personal_info' && (
        <div className="space-y-4 animate-in fade-in duration-200">
          
          {/* Top Status Banner */}
          <div className="bg-gradient-to-r from-blue-900 to-indigo-950 text-white rounded-3xl p-5 shadow-sm space-y-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-5 h-5 text-emerald-400" />
                <h3 className="text-sm font-black">احراز هویت پایه شهروندی</h3>
              </div>
              <span className="bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 text-[10px] font-black px-2.5 py-0.5 rounded-full">
                تایید شده توسط ثبت احوال
              </span>
            </div>

            <div className="grid grid-cols-2 gap-2 text-xs pt-1">
              <div className="bg-white/10 rounded-2xl p-2.5">
                <span className="text-[10px] text-blue-200 block">امتیاز اعتباری:</span>
                <span className="font-black text-amber-300 font-mono text-sm">{profile.creditScore} / ۱۰۰۰</span>
              </div>
              <div className="bg-white/10 rounded-2xl p-2.5">
                <span className="text-[10px] text-blue-200 block">سطح کاربری:</span>
                <span className="font-black text-white text-xs">{profile.tierName}</span>
              </div>
            </div>
          </div>

          {/* Editable Form */}
          <form onSubmit={handleSavePersonalInfo} className="bg-white rounded-3xl p-5 shadow-sm border border-slate-100 space-y-3.5 text-xs">
            <div className="flex items-center justify-between pb-2 border-b border-slate-100">
              <h4 className="font-black text-slate-800 text-sm">مشخصات هویتی و ارتباطی</h4>
              <span className="text-[10px] text-slate-400">قابلیت ویرایش و ذخیره برخط</span>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="font-bold text-slate-700 block mb-1">نام و نام خانوادگی:</label>
                <input
                  type="text"
                  value={editFullName}
                  onChange={(e) => setEditFullName(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold outline-none focus:border-blue-500 focus:bg-white"
                  required
                />
              </div>

              <div>
                <label className="font-bold text-slate-700 block mb-1">کد ملی (۱۰ رقمی):</label>
                <input
                  type="text"
                  value={editNationalId}
                  onChange={(e) => setEditNationalId(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold outline-none focus:border-blue-500 focus:bg-white"
                  required
                />
              </div>

              <div>
                <label className="font-bold text-slate-700 block mb-1">شماره تلفن همراه:</label>
                <input
                  type="text"
                  value={editMobile}
                  onChange={(e) => setEditMobile(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold outline-none focus:border-blue-500 focus:bg-white"
                  required
                />
              </div>

              <div>
                <label className="font-bold text-slate-700 block mb-1">نام پدر:</label>
                <input
                  type="text"
                  value={editFatherName}
                  onChange={(e) => setEditFatherName(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold outline-none focus:border-blue-500 focus:bg-white"
                />
              </div>

              <div>
                <label className="font-bold text-slate-700 block mb-1">تاریخ تولد:</label>
                <input
                  type="text"
                  value={editBirthDate}
                  onChange={(e) => setEditBirthDate(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold outline-none focus:border-blue-500 focus:bg-white"
                />
              </div>

              <div>
                <label className="font-bold text-slate-700 block mb-1">کد پستی ۱۰ رقمی:</label>
                <input
                  type="text"
                  value={editPostalCode}
                  onChange={(e) => setEditPostalCode(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono font-bold outline-none focus:border-blue-500 focus:bg-white"
                />
              </div>
            </div>

            <div>
              <label className="font-bold text-slate-700 block mb-1">نشانی دقیق محل سکونت:</label>
              <textarea
                value={editAddress}
                onChange={(e) => setEditAddress(e.target.value)}
                rows={2}
                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-medium outline-none focus:border-blue-500 focus:bg-white"
              />
            </div>

            <div className="pt-2 flex items-center justify-between gap-3">
              <button
                type="button"
                onClick={() => setActiveSubPage('main')}
                className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors cursor-pointer"
              >
                انصراف و بازگشت
              </button>

              <button
                type="submit"
                className="flex-1 flex items-center justify-center gap-1.5 bg-blue-700 hover:bg-blue-600 text-white font-black py-2.5 rounded-xl shadow-md transition-all cursor-pointer"
              >
                <Save className="w-4 h-4" />
                <span>ذخیره تغییرات اطلاعات</span>
              </button>
            </div>
          </form>

        </div>
      )}

      {/* ========================================================================= */}
      {/* 4. SUB-PAGE: نوبت‌های حضوری رزرو شده (Appointments)                         */}
      {/* ========================================================================= */}
      {activeSubPage === 'appointments' && (
        <div className="space-y-4 animate-in fade-in duration-200">
          
          <div className="flex items-center justify-between bg-white rounded-3xl p-4 shadow-sm border border-slate-100">
            <div className="flex items-center gap-2">
              <div className="w-9 h-9 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <Calendar className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-sm font-black text-slate-900">نوبت‌های حضوری رزرو شده</h3>
                <span className="text-[11px] text-slate-400">کارت ورود، بارکد و هشدار یادآور</span>
              </div>
            </div>

            <span className="text-xs font-black text-emerald-800 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-xl">
              {appointments.length} نوبت معتبر
            </span>
          </div>

          {appointments.length === 0 ? (
            <div className="bg-white rounded-3xl p-8 text-center text-slate-400 text-xs border border-slate-100 space-y-2">
              <Calendar className="w-10 h-10 text-slate-300 mx-auto" />
              <p className="font-bold text-slate-600">در حال حاضر هیچ نوبت حضوری فعالی ندارید.</p>
              <p className="text-[11px]">جهت رزرو نوبت به نقشه دفاتر یا بخش خدمات مراجعه فرمایید.</p>
            </div>
          ) : (
            <div className="space-y-3">
              {appointments.map((app) => (
                <div 
                  key={app.id} 
                  className="bg-white border-2 border-indigo-100 hover:border-indigo-300 rounded-3xl p-4 sm:p-5 shadow-sm hover:shadow-md transition-all space-y-3 relative overflow-hidden"
                >
                  {/* Header of Appointment Card */}
                  <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <span className="font-black text-sm text-slate-900">{app.serviceTitle}</span>
                        <span className="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-[10px] font-black px-2 py-0.5 rounded-full border border-emerald-200">
                          <CheckCircle2 className="w-3 h-3" />
                          نوبت تایید شده
                        </span>
                      </div>
                      <p className="text-xs text-indigo-700 font-bold flex items-center gap-1">
                        <MapPin className="w-3 h-3 text-indigo-500" />
                        <span>{app.officeName}</span>
                      </p>
                      <div className="flex flex-wrap items-center gap-2 text-xs text-slate-600 pt-1 font-semibold">
                        <span className="bg-slate-100 px-2.5 py-1 rounded-xl">📅 تاریخ: {app.date}</span>
                        <span className="bg-slate-100 px-2.5 py-1 rounded-xl">⏰ بازه ساعت: {app.timeSlot}</span>
                      </div>
                    </div>

                    {/* QR & Tracking Code */}
                    <div className="flex items-center gap-2 self-end sm:self-center">
                      <div className="bg-slate-50 border border-slate-200 px-3.5 py-2 rounded-2xl text-center font-mono font-black text-xs text-indigo-950 flex items-center gap-2 shadow-inner">
                        <QrCode className="w-5 h-5 text-indigo-600" />
                        <div>
                          <span className="text-[9px] text-slate-400 block font-sans">کد رهگیری نوبت:</span>
                          <span>{app.trackingCode}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* 1-Hour Reminder Row Inside Appointment Card */}
                  <div className="bg-indigo-50/70 border border-indigo-200/80 rounded-2xl p-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5">
                    <div className="flex items-center gap-2">
                      <div className="w-7 h-7 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                        <BellRing className="w-3.5 h-3.5" />
                      </div>
                      <div>
                        <span className="text-xs font-black text-indigo-950 block">
                          یادآور ۱ ساعت قبل: {app.reminderTime || 'ساعت ۰۹:۳۰'}
                        </span>
                        <span className="text-[10px] text-indigo-700">
                          ارسال خودکار پیامک هشدار با مدارک لازم به شماره {profile.mobile}
                        </span>
                      </div>
                    </div>

                    <button
                      onClick={() => handleTriggerTestReminder(app)}
                      className="bg-white hover:bg-indigo-600 hover:text-white text-indigo-800 border border-indigo-200 px-3 py-1.5 rounded-xl font-bold text-xs transition-colors shadow-xs flex items-center gap-1 cursor-pointer self-end sm:self-center"
                    >
                      <Volume2 className="w-3.5 h-3.5" />
                      <span>تست پیام هشدار نوبت</span>
                    </button>
                  </div>

                  {/* Required Docs Checklist */}
                  {app.requiredDocs && app.requiredDocs.length > 0 && (
                    <div className="pt-2 border-t border-slate-100">
                      <span className="text-[11px] font-bold text-slate-500 block mb-1.5">
                        مدارک الزامی برای همراه داشتن در زمان مراجعه:
                      </span>
                      <div className="flex flex-wrap gap-1.5">
                        {app.requiredDocs.map((doc, idx) => (
                          <span key={idx} className="inline-flex items-center gap-1 text-[10px] font-semibold bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg">
                            <Check className="w-3 h-3 text-emerald-600" />
                            {doc}
                          </span>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Card Bottom Actions */}
                  <div className="pt-1 flex items-center justify-between text-xs">
                    {onCancelAppointment && (
                      <button
                        onClick={() => {
                          onCancelAppointment(app.id);
                          setToastMessage('نوبت با موفقیت لغو شد.');
                          setTimeout(() => setToastMessage(null), 3000);
                        }}
                        className="text-rose-600 hover:text-rose-700 font-bold text-[11px] flex items-center gap-1 cursor-pointer"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                        <span>لغو این نوبت</span>
                      </button>
                    )}

                    <button
                      onClick={() => {
                        setActiveSubPage('smart_reminder');
                      }}
                      className="text-blue-700 hover:text-blue-800 font-black text-[11px] flex items-center gap-1 cursor-pointer ms-auto"
                    >
                      <Sliders className="w-3.5 h-3.5" />
                      <span>مدیریت و تنظیمات یادآور هوشمند</span>
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}

        </div>
      )}

      {/* ========================================================================= */}
      {/* 5. SUB-PAGE: سامانه یادآور هوشمند نوبت (Smart Reminder System)              */}
      {/* ========================================================================= */}
      {activeSubPage === 'smart_reminder' && (
        <div className="space-y-4 animate-in fade-in duration-200">
          
          {/* Main Hero Reminder Hub */}
          <div className="bg-gradient-to-br from-indigo-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-5 sm:p-6 shadow-xl border border-indigo-500/30 relative overflow-hidden space-y-4">
            <div className="absolute top-0 right-0 w-56 h-56 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none" />

            <div className="relative z-10 space-y-4">
              {/* Header */}
              <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-3 border-b border-indigo-800/60">
                <div className="flex items-center gap-2.5">
                  <div className="w-10 h-10 rounded-2xl bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 flex items-center justify-center shrink-0">
                    <BellRing className="w-5 h-5 text-amber-300" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <h3 className="text-sm sm:text-base font-black text-white">
                        سامانه یادآور هوشمند نوبت حضوری
                      </h3>
                      <span className="bg-amber-400/20 text-amber-300 text-[10px] font-black px-2 py-0.5 rounded-full border border-amber-400/30 flex items-center gap-1">
                        <Clock className="w-2.5 h-2.5" />
                        هشدار ۶۰ دقیقه قبل
                      </span>
                    </div>
                    <p className="text-xs text-indigo-200/80 mt-0.5">
                      ارسال خودکار پیامک، اعلان گوشی و راهنمای ترافیک پیش از مراجعه
                    </p>
                  </div>
                </div>

                {/* Reminder Switch Toggle */}
                <button
                  onClick={() => {
                    setIsReminderActive(!isReminderActive);
                    setToastMessage(isReminderActive ? 'یادآور هوشمند غیرفعال شد.' : 'یادآور هوشمند ۱ ساعت قبل فعال شد.');
                    setTimeout(() => setToastMessage(null), 3000);
                  }}
                  className={`flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl font-black text-xs transition-all cursor-pointer ${
                    isReminderActive 
                      ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' 
                      : 'bg-slate-800 text-slate-400 border border-slate-700'
                  }`}
                >
                  <span className={`w-2 h-2 rounded-full ${isReminderActive ? 'bg-white animate-pulse' : 'bg-slate-500'}`} />
                  <span>{isReminderActive ? 'یادآور ۱ ساعت قبل فعال است' : 'یادآور غیرفعال'}</span>
                </button>
              </div>

              {/* Grid of Details */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                {/* Box 1: Scheduled Time */}
                <div className="bg-indigo-800/40 border border-indigo-700/50 rounded-2xl p-3.5 space-y-1.5">
                  <span className="text-[11px] text-indigo-300 font-bold flex items-center gap-1">
                    <Clock className="w-3.5 h-3.5 text-amber-400" />
                    <span>زمان دقیق ارسال هشدار:</span>
                  </span>
                  <p className="text-sm font-black text-white">
                    {activeApp?.reminderTime || 'ساعت ۰۹:۳۰ (۱ ساعت قبل)'}
                  </p>
                  <span className="text-[10px] text-indigo-200 block">
                    نوبت اصلی: {activeApp?.date || 'دوشنبه'} ساعت {activeApp?.timeSlot || '۱۰:۳۰'}
                  </span>
                </div>

                {/* Box 2: Channels */}
                <div className="bg-indigo-800/40 border border-indigo-700/50 rounded-2xl p-3.5 space-y-1.5">
                  <span className="text-[11px] text-indigo-300 font-bold flex items-center gap-1">
                    <Smartphone className="w-3.5 h-3.5 text-cyan-400" />
                    <span>کانال‌های دریافت پیام:</span>
                  </span>
                  <div className="flex flex-wrap gap-1.5 pt-0.5">
                    <span className="inline-flex items-center gap-1 text-[10px] font-bold bg-white/10 text-emerald-300 px-2 py-0.5 rounded-lg">
                      <Check className="w-2.5 h-2.5" />
                      پیامک به {profile.mobile.slice(-4)}***۰۹
                    </span>
                    <span className="inline-flex items-center gap-1 text-[10px] font-bold bg-white/10 text-cyan-300 px-2 py-0.5 rounded-lg">
                      <Check className="w-2.5 h-2.5" />
                      اعلان فوری (Push)
                    </span>
                  </div>
                </div>

                {/* Box 3: Traffic */}
                <div className="bg-indigo-800/40 border border-indigo-700/50 rounded-2xl p-3.5 space-y-1.5">
                  <span className="text-[11px] text-indigo-300 font-bold flex items-center gap-1">
                    <Navigation className="w-3.5 h-3.5 text-emerald-400" />
                    <span>تخمین زمان حرکت:</span>
                  </span>
                  <p className="text-xs font-black text-emerald-300">
                    ۲۰ الی ۳۰ دقیقه زمان مسیر با خودرو
                  </p>
                  <span className="text-[10px] text-indigo-200 block truncate">
                    مقصد: {activeApp?.officeName || 'دفتر ولی‌عصر'}
                  </span>
                </div>
              </div>

              {/* Actions */}
              <div className="flex flex-wrap items-center justify-between gap-2.5 pt-1">
                <button
                  onClick={() => activeApp && handleTriggerTestReminder(activeApp)}
                  className="flex items-center gap-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 active:scale-95 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow-md transition-all cursor-pointer"
                >
                  <BellRing className="w-3.5 h-3.5 text-slate-950" />
                  <span>تست زنده هشدار یادآوری (پخش زنگ + نوتیفیکیشن + پیامک)</span>
                </button>

                <button
                  onClick={() => setShowReminderSettingsModal(true)}
                  className="flex items-center gap-1 bg-white/10 hover:bg-white/20 active:scale-95 text-white font-bold text-xs px-3 py-2 rounded-xl border border-white/20 transition-all cursor-pointer"
                >
                  <Sliders className="w-3.5 h-3.5 text-indigo-300" />
                  <span>تنظیم زمان ارسال ({reminderLeadMinutes} دقیقه قبل)</span>
                </button>
              </div>
            </div>
          </div>

          {/* Quick FAQ / Guide for Reminder */}
          <div className="bg-white rounded-3xl p-5 shadow-sm border border-slate-100 space-y-2.5 text-xs">
            <h4 className="font-black text-slate-800 text-sm">چرا سامانه یادآور ۱ ساعت قبل ضروری است؟</h4>
            <p className="text-slate-600 leading-relaxed text-[11px]">
              با فعال بودن این سامانه، ۱ ساعت قبل از فرا رسیدن نوبت حضوری در دفتر پیشخوان، پیامک رسمی حاوی مشخصات نوبت، لیست مدارک الزامی و نقشه ترافیک برای شما ارسال می‌شود تا بدون استرس و معطلی در باجه حاضر شوید.
            </p>
          </div>

        </div>
      )}

      {/* ========================================================================= */}
      {/* 6. SUB-PAGE: مخزن کامل مدارک و اسناد (Documents Vault)                     */}
      {/* ========================================================================= */}
      {activeSubPage === 'documents_vault' && (
        <div className="space-y-4 animate-in fade-in duration-200">
          
          {/* Header Bar */}
          <div className="flex items-center justify-between bg-white rounded-3xl p-4 shadow-sm border border-slate-100">
            <div className="flex items-center gap-2">
              <div className="w-9 h-9 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
                <FolderLock className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-sm font-black text-slate-900">مخزن هوشمند مدارک شهروندی</h3>
                <span className="text-[11px] text-slate-400">اسناد تایید شده و گواهی‌های رسمی</span>
              </div>
            </div>

            <button
              onClick={() => setShowAddDocModal(true)}
              className="flex items-center gap-1 text-xs font-black text-indigo-700 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3.5 py-2 rounded-xl transition-all cursor-pointer"
            >
              <PlusCircle className="w-4 h-4" />
              <span>افزودن سند جدید</span>
            </button>
          </div>

          {/* Category Filter Pills */}
          <div className="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs font-bold scrollbar-none">
            {[
              { id: 'all', label: 'همه مدارک' },
              { id: 'هویتی', label: 'هویتی و سجلی' },
              { id: 'خودرو', label: 'خودرو و گواهینامه' },
              { id: 'ملک', label: 'سکونت و ملک' },
              { id: 'سلامت', label: 'سلامت و درمان' },
            ].map(tab => (
              <button
                key={tab.id}
                onClick={() => setSelectedCategoryFilter(tab.id)}
                className={`px-3 py-1.5 rounded-xl shrink-0 transition-all cursor-pointer ${
                  selectedCategoryFilter === tab.id
                    ? 'bg-indigo-600 text-white shadow-xs'
                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>

          {/* Documents Grid */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {filteredDocs.map((doc) => {
              const docImg = getDocIllustration(doc.category);
              return (
                <div
                  key={doc.id}
                  onClick={() => setSelectedDoc(doc)}
                  className="p-4 rounded-3xl bg-white border border-slate-200 hover:border-indigo-400 hover:shadow-md transition-all cursor-pointer group flex items-start justify-between"
                >
                  <div className="flex items-start gap-3">
                    <div className="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 group-hover:border-indigo-200 flex items-center justify-center shrink-0 p-1 shadow-inner transition-colors overflow-hidden">
                      {docImg ? (
                        <img
                          src={docImg}
                          alt={doc.title}
                          referrerPolicy="no-referrer"
                          className="w-full h-full object-contain drop-shadow-xs group-hover:scale-105 transition-transform"
                        />
                      ) : (
                        <IdCard className="w-6 h-6 text-indigo-600" />
                      )}
                    </div>
                    <div>
                      <h4 className="font-extrabold text-xs text-slate-900 group-hover:text-indigo-700 transition-colors">
                        {doc.title}
                      </h4>
                      <span className="text-[10px] text-slate-400 block mt-0.5 font-mono">
                        شماره: {doc.docNumber}
                      </span>
                      <span className="inline-block mt-1 text-[9px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-lg">
                        {doc.category}
                      </span>
                    </div>
                  </div>

                  <div className="text-left flex flex-col items-end">
                    <span className="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                      <Check className="w-3 h-3" />
                      معتبر
                    </span>
                    <span className="text-[10px] text-slate-400 mt-2 flex items-center gap-1 group-hover:text-indigo-600 font-bold">
                      <Eye className="w-3.5 h-3.5" />
                      مشاهده
                    </span>
                  </div>
                </div>
              );
            })}
          </div>

        </div>
      )}

      {/* ========================================================================= */}
      {/* 7. SUB-PAGE: تنظیمات (Settings)                                            */}
      {/* ========================================================================= */}
      {activeSubPage === 'settings' && (
        <div className="space-y-4 animate-in fade-in duration-200">
          
          <div className="bg-white rounded-3xl p-5 shadow-sm border border-slate-100 space-y-3.5 text-xs">
            <h4 className="font-black text-slate-800 text-sm pb-2 border-b border-slate-100">
              امنیت و دسترسی
            </h4>

            <div className="space-y-2">
              <div className="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                <div className="flex items-center gap-2.5">
                  <Fingerprint className="w-5 h-5 text-emerald-600" />
                  <div>
                    <span className="font-bold text-slate-800 block">ورود بیومتریک و اثر انگشت</span>
                    <span className="text-[10px] text-slate-400">ورود سریع بدون نیاز به دریافت پیامک OTP</span>
                  </div>
                </div>
                <span className="text-emerald-700 font-extrabold bg-emerald-50 px-2.5 py-1 rounded-lg text-[10px] border border-emerald-200">
                  فعال
                </span>
              </div>

              <div className="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                <div className="flex items-center gap-2.5">
                  <Lock className="w-5 h-5 text-blue-600" />
                  <div>
                    <span className="font-bold text-slate-800 block">رمز عبور یکبار مصرف امنیتی</span>
                    <span className="text-[10px] text-slate-400">تایید دو مرحله‌ای برای کلیه تراکنش‌ها</span>
                  </div>
                </div>
                <span className="text-blue-700 font-extrabold bg-blue-50 px-2.5 py-1 rounded-lg text-[10px] border border-blue-200">
                  فعال
                </span>
              </div>
            </div>

            <h4 className="font-black text-slate-800 text-sm pt-3 pb-2 border-b border-slate-100">
              اطلاع‌رسانی و پیامک
            </h4>

            <div className="space-y-2">
              <label className="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-100 cursor-pointer">
                <div>
                  <span className="font-bold text-slate-800 block">دریافت پیامک نوبت‌ها و استعلامات</span>
                  <span className="text-[10px] text-slate-400">ارسال به شماره {profile.mobile}</span>
                </div>
                <input
                  type="checkbox"
                  checked={enableSms}
                  onChange={(e) => {
                    setEnableSms(e.target.checked);
                    setToastMessage('تنظیمات پیامک بروز شد.');
                    setTimeout(() => setToastMessage(null), 2000);
                  }}
                  className="w-4 h-4 text-blue-600 rounded cursor-pointer"
                />
              </label>

              <label className="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-100 cursor-pointer">
                <div>
                  <span className="font-bold text-slate-800 block">اعلان‌های صوتی یادآور</span>
                  <span className="text-[10px] text-slate-400">پخش زنگ هشدار هنگام فرارسیدن زمان حرکت</span>
                </div>
                <input
                  type="checkbox"
                  checked={enableSound}
                  onChange={(e) => {
                    setEnableSound(e.target.checked);
                    setToastMessage('تنظیمات صوتی بروز شد.');
                    setTimeout(() => setToastMessage(null), 2000);
                  }}
                  className="w-4 h-4 text-blue-600 rounded cursor-pointer"
                />
              </label>
            </div>

            <div className="pt-3">
              <button
                onClick={() => {
                  setToastMessage('حافظه موقت با موفقیت پاکسازی و با سرور همگام شد.');
                  setTimeout(() => setToastMessage(null), 3000);
                }}
                className="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 rounded-xl transition-colors cursor-pointer"
              >
                همگام‌سازی اطلاعات و پاکسازی حافظه موقت (Sync Data)
              </button>
            </div>
          </div>

        </div>
      )}

      {/* ========================================================================= */}
      {/* 8. SUB-PAGE: پشتیبانی و راهنما (Support & Help)                             */}
      {/* ========================================================================= */}
      {activeSubPage === 'support' && (
        <div className="space-y-4 animate-in fade-in duration-200">
          
          {/* Support Phone Card */}
          <div className="bg-gradient-to-r from-blue-700 to-indigo-800 text-white rounded-3xl p-5 shadow-sm space-y-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <PhoneCall className="w-6 h-6 text-cyan-300" />
                <div>
                  <h3 className="font-black text-sm">مرکز تماس و پشتیبانی پیشخوان دولت</h3>
                  <p className="text-xs text-blue-100">پاسخگویی ۲۴ ساعته در ۷ روز هفته</p>
                </div>
              </div>

              <a
                href="tel:0218911"
                className="bg-white text-blue-900 hover:bg-blue-50 font-mono font-black px-4 py-2 rounded-xl text-xs shadow-md transition-all active:scale-95"
              >
                ۰۲۱-۸۹۱۱
              </a>
            </div>
          </div>

          {/* Submit Inquiry Ticket */}
          <div className="bg-white rounded-3xl p-5 shadow-sm border border-slate-100 space-y-3 text-xs">
            <h4 className="font-black text-slate-800 text-sm">ثبت تیکت و پیام پشتیبانی</h4>

            {inquirySent ? (
              <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-center text-emerald-900 space-y-1.5">
                <CheckCircle2 className="w-6 h-6 text-emerald-600 mx-auto" />
                <h5 className="font-black">پیام شما با موفقیت ثبت شد</h5>
                <p className="text-[11px] text-emerald-700">کارشناسان پیشخوان ظرف حداکثر ۲ ساعت با شما تماس خواهند گرفت.</p>
                <button
                  onClick={() => {
                    setInquirySent(false);
                    setInquirySubject('');
                    setInquiryText('');
                  }}
                  className="mt-2 text-xs font-bold text-emerald-800 underline cursor-pointer"
                >
                  ثبت پیام جدید
                </button>
              </div>
            ) : (
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  if (!inquirySubject || !inquiryText) return;
                  setInquirySent(true);
                }}
                className="space-y-3"
              >
                <div>
                  <label className="font-bold text-slate-700 block mb-1">موضوع درخواست:</label>
                  <input
                    type="text"
                    value={inquirySubject}
                    onChange={(e) => setInquirySubject(e.target.value)}
                    placeholder="مثلاً: پیگیری صدور کارت ملی یا تعویض دفتر نوبت..."
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none focus:border-blue-500 focus:bg-white"
                    required
                  />
                </div>

                <div>
                  <label className="font-bold text-slate-700 block mb-1">متن توضیحات:</label>
                  <textarea
                    value={inquiryText}
                    onChange={(e) => setInquiryText(e.target.value)}
                    rows={3}
                    placeholder="شرح کامل سوال یا مشکل خود را بنویسید..."
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none focus:border-blue-500 focus:bg-white"
                    required
                  />
                </div>

                <button
                  type="submit"
                  className="w-full bg-blue-700 hover:bg-blue-600 text-white font-black py-2.5 rounded-xl shadow-md transition-all cursor-pointer flex items-center justify-center gap-1.5"
                >
                  <Send className="w-4 h-4" />
                  <span>ارسال تیکت پشتیبانی</span>
                </button>
              </form>
            )}
          </div>

        </div>
      )}

      {/* ========================================================================= */}
      {/* SUB-PAGE: پیام‌ها و ابلاغیه‌ها (Messages & Official Notices)               */}
      {/* ========================================================================= */}
      {activeSubPage === 'messages_and_notices' && (
        <div className="space-y-4 animate-in fade-in duration-200" dir="rtl">
          
          {/* Subpage Top Banner */}
          <div className="bg-slate-900 text-white rounded-3xl p-4 sm:p-5 shadow-sm space-y-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <div className="w-10 h-10 rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-400/30 flex items-center justify-center">
                  <Bell className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-sm font-extrabold text-white">صندوق ابلاغیه‌ها و پیام‌های شهروندی</h3>
                  <span className="text-[11px] text-slate-300">درگاه رسمی دریافت اعلانات حاکمیتی و پیشخوان</span>
                </div>
              </div>
              <span className="bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 text-[10px] font-black px-2.5 py-1 rounded-full">
                اتصال امن ثنا و دولت
              </span>
            </div>

            {/* Segmented Filter Tabs */}
            <div className="flex bg-slate-800/90 p-1 rounded-2xl gap-1 pt-1">
              <button
                onClick={() => setNoticeTab('notices')}
                className={`flex-1 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer ${
                  noticeTab === 'notices'
                    ? 'bg-emerald-600 text-white shadow-sm'
                    : 'text-slate-400 hover:text-white'
                }`}
              >
                ابلاغیه‌های رسمی ({3})
              </button>
              <button
                onClick={() => setNoticeTab('sms')}
                className={`flex-1 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer ${
                  noticeTab === 'sms'
                    ? 'bg-emerald-600 text-white shadow-sm'
                    : 'text-slate-400 hover:text-white'
                }`}
              >
                پیامک‌های دولتی ({3})
              </button>
              <button
                onClick={() => setNoticeTab('office_chats')}
                className={`flex-1 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer ${
                  noticeTab === 'office_chats'
                    ? 'bg-emerald-600 text-white shadow-sm'
                    : 'text-slate-400 hover:text-white'
                }`}
              >
                مکاتبات باجه ({messages.length})
              </button>
            </div>
          </div>

          {/* TAB 1: OFFICIAL NOTICES (ابلاغیه‌های رسمی) */}
          {noticeTab === 'notices' && (
            <div className="space-y-3">
              {/* Notice 1: ثبت احوال */}
              <div className="bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-100 space-y-3 relative overflow-hidden">
                <div className="flex items-start justify-between gap-2">
                  <div className="flex items-center gap-2.5">
                    <div className="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                      <FileCheck className="w-4.5 h-4.5" />
                    </div>
                    <div>
                      <h4 className="font-extrabold text-xs sm:text-sm text-slate-900">
                        تاییدیه و آماده تحویل کارت هوشمند ملی
                      </h4>
                      <span className="text-[10px] text-slate-400">سازمان ثبت احوال کشور • شناسه: ۹۸۲۱۴۷۸۳</span>
                    </div>
                  </div>
                  <span className="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded-full shrink-0">
                    جدید
                  </span>
                </div>

                <p className="text-xs text-slate-600 leading-relaxed">
                  شهروند گرامی، کارت هوشمند ملی شما صادر گردیده و به دفتر پیشخوان دولت منتخب تحویل داده شد. لطفاً با همراه داشتن اصل شناسنامه جهت دریافت مراجعه فرمایید.
                </p>

                <div className="p-2.5 bg-slate-50 rounded-2xl flex items-center justify-between text-[11px] text-slate-500 font-medium">
                  <span>مرکز تحویل: دفتر پیشخوان کد ۷۲۱۶۱۰۲۸ (سعادت‌آباد)</span>
                  <span className="font-mono text-slate-400">۱۴۰۳/۰۸/۱۲</span>
                </div>
              </div>

              {/* Notice 2: ثنا و قوه قضائیه */}
              <div className="bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-100 space-y-3">
                <div className="flex items-start justify-between gap-2">
                  <div className="flex items-center gap-2.5">
                    <div className="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                      <ShieldCheck className="w-4.5 h-4.5" />
                    </div>
                    <div>
                      <h4 className="font-extrabold text-xs sm:text-sm text-slate-900">
                        ابلاغیه الکترونیکی سامانه ثنا
                      </h4>
                      <span className="text-[10px] text-slate-400">مرکز آمار و فناوری اطلاعات قوه قضائیه</span>
                    </div>
                  </div>
                  <span className="text-[10px] text-slate-400">۱۴۰۳/۰۸/۱۰</span>
                </div>

                <p className="text-xs text-slate-600 leading-relaxed">
                  ابلاغیه شماره ۱۴۰۳۶۸۹۲۰۰۰۱۵۲۶۷۸۲ در سامانه ابلاغ الکترونیک قضایی برای شما صادر و رویت گردید.
                </p>
              </div>

              {/* Notice 3: پست */}
              <div className="bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-100 space-y-3">
                <div className="flex items-start justify-between gap-2">
                  <div className="flex items-center gap-2.5">
                    <div className="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                      <Truck className="w-4.5 h-4.5" />
                    </div>
                    <div>
                      <h4 className="font-extrabold text-xs sm:text-sm text-slate-900">
                        صدور گواهی تاییدیه کد پستی ۱۰ رقمی
                      </h4>
                      <span className="text-[10px] text-slate-400">شرکت ملی پست جمهوری اسلامی ایران</span>
                    </div>
                  </div>
                  <span className="text-[10px] text-slate-400">۱۴۰۳/۰۸/۰۵</span>
                </div>

                <p className="text-xs text-slate-600 leading-relaxed">
                  گواهی تاییدیه نشانی و کد پستی معتبر به همراه هولوگرام امنیتی برای نشانی ثبت شده صادر گردید و در مخزن اسناد شما بارگذاری شد.
                </p>
              </div>
            </div>
          )}

          {/* TAB 2: SMS INBOX (پیامک‌های دریافتی) */}
          {noticeTab === 'sms' && (
            <div className="space-y-3">
              {[
                {
                  sender: 'پیشخوان دولت (۹۸۲۰۰۰۱۴۰۲+)',
                  date: 'امروز، ۱۰:۱۵',
                  text: 'درخواست صدور گواهی سوء پیشینه شما توسط باجه دفتر پذیرش شد و استعلام قوه قضائیه دریافت گردید.',
                  tag: 'خدمت الکترونیک'
                },
                {
                  sender: 'پلیس راهور (۹۸۱۱۰+)',
                  date: 'دیروز، ۱۶:۴۰',
                  text: 'استعلام خلافی خودرو شماره ایران ۶۸-۷۷۲ج۴۴ با موفقیت انجام و تسویه حساب صادر شد.',
                  tag: 'خدمات خودرو'
                },
                {
                  sender: 'ثبت احوال کشور (۹۸۳۰۰۰۲۱+)',
                  date: '۳ روز پیش',
                  text: 'احراز هویت دیجیتال و بیومتریک شما با موفقیت در سامانه هدا به ثبت رسید.',
                  tag: 'احراز هویت'
                }
              ].map((smsItem, idx) => (
                <div key={idx} className="bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-100 space-y-2">
                  <div className="flex items-center justify-between text-xs">
                    <div className="flex items-center gap-2">
                      <Smartphone className="w-4 h-4 text-slate-400" />
                      <span className="font-bold text-slate-900">{smsItem.sender}</span>
                    </div>
                    <span className="text-[10px] text-slate-400 font-mono">{smsItem.date}</span>
                  </div>
                  <p className="text-xs text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-2xl border border-slate-100">
                    {smsItem.text}
                  </p>
                </div>
              ))}
            </div>
          )}

          {/* TAB 3: OFFICE CHATS (مکاتبات باجه‌ها) */}
          {noticeTab === 'office_chats' && (
            <div className="space-y-3">
              {messages.length === 0 ? (
                <div className="bg-white rounded-3xl p-8 text-center text-slate-400 space-y-2 border border-slate-100">
                  <MessageSquare className="w-10 h-10 mx-auto text-slate-300" />
                  <p className="text-xs font-bold text-slate-600">هنوز پیام چتی ثبت نشده است.</p>
                  <p className="text-[11px]">با ثبت هر درخواست خدمت و انتخاب دفتر، گفتگوهای آنلاین با باجه در این قسمت نمایش داده می‌شوند.</p>
                </div>
              ) : (
                messages.map((m) => (
                  <div key={m.id} className="bg-white rounded-3xl p-4 shadow-sm border border-slate-100 space-y-2">
                    <div className="flex items-center justify-between text-xs">
                      <div className="flex items-center gap-2">
                        <Building2 className="w-4 h-4 text-emerald-600" />
                        <span className="font-bold text-slate-900">{m.senderName}</span>
                      </div>
                      <span className="text-[10px] text-slate-400">{m.time}</span>
                    </div>
                    <p className="text-xs text-slate-700 bg-slate-50 p-3 rounded-2xl">
                      {m.text}
                    </p>
                  </div>
                ))
              )}
            </div>
          )}

        </div>
      )}

      {/* ========================================================================= */}
      {/* 9. SUB-PAGE: درباره ما (About Us)                                          */}
      {/* ========================================================================= */}
      {activeSubPage === 'about' && (
        <div className="space-y-4 animate-in fade-in duration-200">
          
          <div className="bg-white rounded-3xl p-5 shadow-sm border border-slate-100 space-y-3 text-xs text-slate-700 leading-relaxed">
            <div className="flex items-center gap-2.5 pb-2 border-b border-slate-100">
              <Building2 className="w-6 h-6 text-blue-700" />
              <div>
                <h3 className="font-black text-sm text-slate-900">سامانه جامع پیشخوان دولت الکترونیک</h3>
                <span className="text-[10px] text-slate-400">نسخه ۲.۴.۰ - سال ۱۴۰۳</span>
              </div>
            </div>

            <p>
              سامانه هوشمند پیشخوان دولت در راستای تکریم حقوق شهروندی و تسهیل دسترسی به خدمات حاکمیتی و عمومی طراحی شده است.
            </p>

            <div className="p-3 bg-blue-50/60 rounded-2xl border border-blue-100 space-y-1 text-[11px] text-blue-950 font-medium">
              <p>🔒 دارای گواهینامه امنیت اطلاعات و پروتکل رمزنگاری دولتی</p>
              <p>⚡ اتصال برخط به سرورهای ثبت احوال، پلیس راهور، پست و مالیات</p>
              <p>📅 سامانه رزرواسیون با زمان‌بندی دقیق و یادآور هوشمند ۱ ساعت قبل</p>
            </div>
          </div>

        </div>
      )}

      {/* ========================================================================= */}
      {/* MODALS & OVERLAYS                                                         */}
      {/* ========================================================================= */}

      {/* MODAL 1: Smart 1-Hour Reminder Alert Simulator & SMS Preview */}
      {activeAlertAppointment && (
        <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
          <div className="bg-white text-slate-900 w-full max-w-md rounded-3xl p-5 sm:p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
            {/* Header */}
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2.5">
                <div className="w-9 h-9 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                  <BellRing className="w-5 h-5" />
                </div>
                <div>
                  <h4 className="text-sm font-black text-slate-900">شبیه‌ساز پیام هشدار ۱ ساعت قبل</h4>
                  <span className="text-[11px] text-slate-400">متن پیامک و نوتیفیکیشن ارسالی به شهروند</span>
                </div>
              </div>
              <button 
                onClick={() => setActiveAlertAppointment(null)} 
                className="text-slate-400 hover:text-slate-600 p-1 cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Smartphone SMS Simulation Card */}
            <div className="bg-slate-900 text-white rounded-2xl p-4 space-y-3 shadow-inner">
              <div className="flex items-center justify-between border-b border-slate-800 pb-2 text-[11px]">
                <span className="flex items-center gap-1 text-slate-400">
                  <Smartphone className="w-3.5 h-3.5 text-cyan-400" />
                  پیامک دریافتی از: ۹۸۲۰۰۰۱۴۰۲+ (پیشخوان دولت)
                </span>
                <span className="text-amber-400 font-mono font-bold">۰۹:۳۰</span>
              </div>

              {/* SMS Text Content */}
              <div className="bg-slate-800/90 border border-slate-700/60 rounded-xl p-3 text-xs leading-relaxed font-sans space-y-2">
                <p className="font-bold text-amber-300 flex items-center gap-1">
                  <AlertCircle className="w-3.5 h-3.5" />
                  هشدار یادآوری نوبت حضوری پیشخوان (۱ ساعت مانده)
                </p>
                <p className="text-slate-200">
                  جناب آقای <strong className="text-white">{profile.fullName}</strong>،
                  <br />
                  نوبت حضوری شما برای خدمت <strong className="text-emerald-300">«{activeAlertAppointment.serviceTitle}»</strong> در تاریخ <span className="font-bold text-white">{activeAlertAppointment.date}</span> ساعت <span className="font-bold text-amber-300">{activeAlertAppointment.timeSlot}</span> فرا می‌رسد.
                </p>
                <div className="p-2 bg-slate-900/80 rounded-lg text-[11px] text-slate-300 space-y-1">
                  <p>📍 <strong>مرکز:</strong> {activeAlertAppointment.officeName}</p>
                  <p>📌 <strong>آدرس:</strong> {activeAlertAppointment.address || 'تهران، خ ولی‌عصر، پلاک ۲۴'}</p>
                  <p>🎫 <strong>کد نوبت:</strong> <span className="font-mono font-black text-cyan-300">{activeAlertAppointment.trackingCode}</span></p>
                </div>
                <p className="text-[11px] text-amber-200/90 font-medium">
                  ⚠️ مدارک الزامی: اصل شناسنامه، کارت ملی و کد رهگیری نوبت
                </p>
              </div>
            </div>

            {/* Quick Helper Actions */}
            <div className="space-y-2 pt-1">
              <div className="bg-emerald-50 border border-emerald-200 rounded-2xl p-3 text-xs flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                  <span className="text-emerald-900 font-bold text-[11px]">
                    یادآور برای این نوبت فعال و تنظیم شده است.
                  </span>
                </div>
                <button
                  onClick={playAlertChime}
                  className="text-emerald-700 hover:text-emerald-800 font-black text-[11px] flex items-center gap-1 cursor-pointer"
                >
                  <Volume2 className="w-3.5 h-3.5" />
                  پخش مجدد زنگ
                </button>
              </div>

              <button
                onClick={() => {
                  setActiveAlertAppointment(null);
                  setToastMessage('هشدار یادآوری تایید گردید.');
                  setTimeout(() => setToastMessage(null), 2500);
                }}
                className="w-full bg-blue-700 hover:bg-blue-600 text-white font-black py-2.5 rounded-xl shadow-md transition-all cursor-pointer text-xs"
              >
                متوجه شدم و تایید دریافت پیام
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 2: Reminder Settings Modal */}
      {showReminderSettingsModal && (
        <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
          <div className="bg-white text-slate-900 w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <div className="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
                  <Sliders className="w-4 h-4" />
                </div>
                <h4 className="text-sm font-black text-slate-900">تنظیمات زمان‌بندی یادآور نوبت</h4>
              </div>
              <button onClick={() => setShowReminderSettingsModal(false)} className="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div>
                <label className="font-extrabold text-slate-700 block mb-1.5">
                  ارسال پیام هشدار چند دقیقه قبل از نوبت:
                </label>
                <div className="grid grid-cols-2 gap-2">
                  {[
                    { minutes: 60, label: '۱ ساعت قبل (پیش‌فرض هوشمند)' },
                    { minutes: 30, label: '۳۰ دقیقه قبل' },
                    { minutes: 120, label: '۲ ساعت قبل' },
                    { minutes: 1440, label: '۱ روز قبل' }
                  ].map(option => (
                    <button
                      key={option.minutes}
                      type="button"
                      onClick={() => setReminderLeadMinutes(option.minutes)}
                      className={`p-2.5 rounded-xl border text-center font-bold text-[11px] transition-all cursor-pointer ${
                        reminderLeadMinutes === option.minutes
                          ? 'bg-blue-700 text-white border-blue-700 shadow-sm'
                          : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
                      }`}
                    >
                      {option.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Toggles */}
              <div className="space-y-2 pt-2 border-t border-slate-100">
                <label className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 cursor-pointer">
                  <span className="font-bold text-slate-800">ارسال پیامک (SMS)</span>
                  <input
                    type="checkbox"
                    checked={enableSms}
                    onChange={(e) => setEnableSms(e.target.checked)}
                    className="w-4 h-4 text-emerald-600 rounded cursor-pointer"
                  />
                </label>

                <label className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 cursor-pointer">
                  <span className="font-bold text-slate-800">اعلان پوش (Push Notification)</span>
                  <input
                    type="checkbox"
                    checked={enablePush}
                    onChange={(e) => setEnablePush(e.target.checked)}
                    className="w-4 h-4 text-emerald-600 rounded cursor-pointer"
                  />
                </label>

                <label className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 cursor-pointer">
                  <span className="font-bold text-slate-800">پخش زنگ هشدار صوتی</span>
                  <input
                    type="checkbox"
                    checked={enableSound}
                    onChange={(e) => setEnableSound(e.target.checked)}
                    className="w-4 h-4 text-emerald-600 rounded cursor-pointer"
                  />
                </label>

                <label className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 cursor-pointer">
                  <span className="font-bold text-slate-800">احتساب زمان ترافیک مسیر</span>
                  <input
                    type="checkbox"
                    checked={enableTrafficCalc}
                    onChange={(e) => setEnableTrafficCalc(e.target.checked)}
                    className="w-4 h-4 text-emerald-600 rounded cursor-pointer"
                  />
                </label>
              </div>

              <button
                onClick={() => {
                  setShowReminderSettingsModal(false);
                  setToastMessage('تنظیمات یادآور هوشمند ذخیره شد.');
                  setTimeout(() => setToastMessage(null), 2500);
                }}
                className="w-full bg-blue-700 hover:bg-blue-600 text-white font-black py-2.5 rounded-xl shadow-md transition-all mt-2 cursor-pointer"
              >
                ذخیره تنظیمات
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Document Details View */}
      {selectedDoc && (
        <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
          <div className="bg-white text-slate-900 w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100 space-y-3">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <div className="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                  <ShieldCheck className="w-4 h-4" />
                </div>
                <h4 className="text-sm font-extrabold text-slate-900">{selectedDoc.title}</h4>
              </div>
              <button onClick={() => setSelectedDoc(null)} className="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div className="bg-slate-50 p-3 rounded-2xl border border-slate-100 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-slate-500">شماره سند:</span>
                  <span className="font-bold text-slate-900 font-mono">{selectedDoc.docNumber}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-slate-500">تاریخ صدور:</span>
                  <span className="font-bold text-slate-900">{selectedDoc.issueDate}</span>
                </div>
                {selectedDoc.attributes.map((attr, idx) => (
                  <div key={idx} className="flex items-center justify-between pt-1 border-t border-slate-200/50">
                    <span className="text-slate-500">{attr.label}:</span>
                    <span className="font-bold text-slate-900">{attr.value}</span>
                  </div>
                ))}
              </div>

              <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-center text-emerald-900 text-[11px]">
                <CheckCircle2 className="w-5 h-5 text-emerald-600 mx-auto mb-1" />
                این سند در مخزن یکپارچه مدارک ثبت شده و در تمامی درخواست‌های پیشخوان تایید گردیده است.
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Add Document */}
      {showAddDocModal && (
        <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
          <div className="bg-white text-slate-900 w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
              <h4 className="text-sm font-extrabold text-slate-900">افزودن مدرک به مخزن پرونده</h4>
              <button onClick={() => setShowAddDocModal(false)} className="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleCreateDocument} className="space-y-3 text-xs">
              <div>
                <label className="font-bold text-slate-700 block mb-1">عنوان مدرک:</label>
                <input
                  type="text"
                  value={newDocTitle}
                  onChange={(e) => setNewDocTitle(e.target.value)}
                  placeholder="مثلاً: سند مالکیت خودرو، کارت بازرگانی..."
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none focus:border-blue-500 focus:bg-white"
                  required
                />
              </div>

              <div>
                <label className="font-bold text-slate-700 block mb-1">دسته‌بندی:</label>
                <select
                  value={newDocType}
                  onChange={(e) => setNewDocType(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none focus:border-blue-500"
                >
                  <option value="هویتی و سجلی">هویتی و سجلی</option>
                  <option value="ملکی و سکونت">ملکی و سکونت</option>
                  <option value="خودرو و گواهینامه">خودرو و گواهینامه</option>
                  <option value="کسب‌وکار و مجوز">کسب‌وکار و مجوز</option>
                  <option value="پزشکی و سلامت">پزشکی و سلامت</option>
                </select>
              </div>

              <div>
                <label className="font-bold text-slate-700 block mb-1">شماره سند / کد رهگیری:</label>
                <input
                  type="text"
                  value={newDocNumber}
                  onChange={(e) => setNewDocNumber(e.target.value)}
                  placeholder="مثلاً: ۹۸۴۰۲۱۸۹"
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none focus:border-blue-500 focus:bg-white"
                  required
                />
              </div>

              <button
                type="submit"
                className="w-full bg-blue-700 hover:bg-blue-600 text-white font-extrabold py-3 rounded-xl shadow-md transition-all mt-2 cursor-pointer"
              >
                ثبت و ذخیره در مخزن
              </button>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Logout Confirmation */}
      {showLogoutModal && (
        <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
          <div className="bg-white text-slate-900 w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100 space-y-4 text-center">
            <div className="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
              <LogOut className="w-6 h-6" />
            </div>

            <div className="space-y-1">
              <h4 className="font-black text-slate-900 text-sm">خروج از حساب کاربری</h4>
              <p className="text-xs text-slate-500">
                آیا از خروج از سامانه پیشخوان دولت الکترونیک اطمینان دارید؟
              </p>
            </div>

            <div className="grid grid-cols-2 gap-2 pt-2 text-xs font-bold">
              <button
                onClick={() => setShowLogoutModal(false)}
                className="py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors cursor-pointer"
              >
                انصراف
              </button>

              <button
                onClick={() => {
                  setShowLogoutModal(false);
                  setToastMessage('از حساب کاربری خارج شدید.');
                  setTimeout(() => {
                    setToastMessage(null);
                    if (onLogout) onLogout();
                  }, 600);
                }}
                className="py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white transition-colors cursor-pointer"
              >
                بله، خروج
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
};
