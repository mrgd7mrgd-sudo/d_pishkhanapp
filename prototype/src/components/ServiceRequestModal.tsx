import React, { useState, useEffect } from 'react';
import { 
  X, 
  CheckCircle2, 
  ShieldCheck, 
  Building2, 
  Zap, 
  MapPin, 
  CreditCard, 
  Clock, 
  ArrowRight,
  FileCheck2,
  Radio,
  Sparkles,
  AlertCircle,
  ChevronLeft
} from 'lucide-react';
import { CitizenService, CitizenProfile, PishkhanOffice, CaseRequest, ServiceTag } from '../types';
import { ServiceTagBadge } from './ServiceTagBadge';

interface ServiceRequestModalProps {
  service: CitizenService;
  profile: CitizenProfile;
  offices: PishkhanOffice[];
  onClose: () => void;
  onSubmitCase: (newCase: CaseRequest) => void;
  preSelectedOfficeId?: string | null;
}

export const ServiceRequestModal: React.FC<ServiceRequestModalProps> = ({
  service,
  profile,
  offices,
  onClose,
  onSubmitCase,
  preSelectedOfficeId
}) => {
  const [step, setStep] = useState<'info' | 'dispatch_type' | 'payment' | 'searching' | 'assigned'>('info');
  const [dispatchMode, setDispatchMode] = useState<'auto_snapp' | 'manual_office'>('auto_snapp');
  const [selectedOfficeId, setSelectedOfficeId] = useState<string>(
    preSelectedOfficeId || offices.find(o => o.isOnline)?.id || offices[0].id
  );
  const [paymentMethod, setPaymentMethod] = useState<'wallet' | 'gateway'>('wallet');

  // Search animation state
  const [searchPhase, setSearchPhase] = useState<number>(0);
  const [assignedOffice, setAssignedOffice] = useState<PishkhanOffice | null>(null);
  const [createdCase, setCreatedCase] = useState<CaseRequest | null>(null);

  // Auto-filled data from vault
  const [citizenName] = useState(profile.fullName);
  const [citizenNationalId] = useState(profile.nationalId);
  const [citizenMobile] = useState(profile.mobile);
  const [citizenAddress] = useState(profile.address);
  const [citizenPostalCode] = useState(profile.postalCode);

  const selectedOffice = offices.find(o => o.id === selectedOfficeId) || offices[0];

  // If preselected office was passed, default to manual
  useEffect(() => {
    if (preSelectedOfficeId) {
      setDispatchMode('manual_office');
      setSelectedOfficeId(preSelectedOfficeId);
    }
  }, [preSelectedOfficeId]);

  // Simulation of Snapp-like dispatch
  useEffect(() => {
    let timer1: any, timer2: any, timer3: any;

    if (step === 'searching') {
      setSearchPhase(1); // Searching nearest offices

      timer1 = setTimeout(() => {
        setSearchPhase(2); // Pinging office 1...
      }, 1500);

      timer2 = setTimeout(() => {
        setSearchPhase(3); // Office accepted!
      }, 3000);

      timer3 = setTimeout(() => {
        const targetOffice = dispatchMode === 'auto_snapp' 
          ? (offices.find(o => o.isOnline && o.supportedCategoryIds.includes(service.categoryId)) || offices[0])
          : selectedOffice;

        setAssignedOffice(targetOffice);

        const newCaseNumber = `PK-1403-${Math.floor(10000 + Math.random() * 90000)}`;
        const now = new Date();
        const formattedDate = `۱۴۰۳/۰۶/۰۲ - ${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;

        const newCase: CaseRequest = {
          id: `case-${Date.now()}`,
          trackingCode: newCaseNumber,
          serviceId: service.id,
          serviceTitle: service.title,
          serviceCategory: service.categoryId,
          serviceTag: service.tags[0] || 'online',
          status: 'assigned_to_office',
          currentStepNumber: 2,
          totalSteps: 5,
          turnOwner: 'office',
          turnOwnerText: 'در نوبت بررسی کارشناس باجه پیشخوان',
          createdAt: formattedDate,
          updatedAt: formattedDate,
          estimatedCompletion: service.estimatedDays,
          citizenName,
          citizenNationalId,
          feePaid: service.fee,
          officeId: targetOffice.id,
          assignedOffice: targetOffice,
          uploadedDocuments: service.requirements.map(req => ({
            name: req,
            type: 'image/jpeg',
            verified: true
          })),
          timeline: [
            {
              id: 'st-1',
              title: 'ثبت و تکمیل خودکار پرونده از مخزن مدارک',
              description: `درخواست با موفقیت ثبت شد و اطلاعات هویتی ${profile.fullName} تایید گردید.`,
              timestamp: formattedDate,
              status: 'done',
              icon: 'CheckCircle',
              turnOwner: 'system',
              turnOwnerLabel: 'سیستم ثبت هوشمند'
            },
            {
              id: 'st-2',
              title: `ارجاع به ${targetOffice.name}`,
              description: 'دفتر پیشخوان سیگنال اعزام را دریافت و پرونده به کارشناس ارجاع شد.',
              timestamp: formattedDate,
              status: 'current',
              icon: 'Building2',
              turnOwner: 'office',
              turnOwnerLabel: 'کارشناس باجه پیشخوان',
              officeNote: `مدیر دفتر: ${targetOffice.managerName} | کارشناس برخط فعال`
            },
            {
              id: 'st-3',
              title: 'بررسی مدارک و تطبیق احراز هویت',
              description: 'کنترل صحت اطلاعات و اسناد بارگذاری شده',
              status: 'pending',
              icon: 'FileCheck',
              turnOwner: 'office',
              turnOwnerLabel: 'کارشناس باجه پیشخوان'
            },
            {
              id: 'st-4',
              title: 'استعلام از سامانه مرکزی وزارتخانه/سازمان',
              description: `ارتباط با پایگاه داده ${service.department}`,
              status: 'pending',
              icon: 'Database',
              turnOwner: 'system',
              turnOwnerLabel: 'سامانه دولت الکترونیک'
            },
            {
              id: 'st-5',
              title: 'صدور تاییدیه نهایی و تحویل به شهروند',
              description: 'ارسال نسخه دیجیتال یا مرسوله پستی',
              status: 'pending',
              icon: 'Award',
              turnOwner: 'system',
              turnOwnerLabel: 'سامانه و باجه تحویل'
            }
          ]
        };

        setCreatedCase(newCase);
        onSubmitCase(newCase);
        setStep('assigned');
      }, 4200);
    }

    return () => {
      clearTimeout(timer1);
      clearTimeout(timer2);
      clearTimeout(timer3);
    };
  }, [step, dispatchMode, selectedOfficeId, offices, service, citizenName, citizenNationalId, profile, onSubmitCase]);

  return (
    <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto animate-in fade-in duration-200">
      <div className="bg-white text-slate-900 w-full max-w-lg rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-auto max-h-[92vh] flex flex-col">
        
        {/* Modal Header */}
        <div className="bg-slate-900 text-white p-4 sm:p-5 flex items-center justify-between shrink-0">
          <div className="flex items-center gap-2.5">
            <div className="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center">
              <Zap className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="font-extrabold text-sm sm:text-base text-white">{service.title}</h3>
              </div>
              <span className="text-xs text-slate-400 block mt-0.5">{service.department}</span>
            </div>
          </div>
          <button 
            onClick={onClose}
            className="text-slate-400 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Wizard content */}
        <div className="p-4 sm:p-6 overflow-y-auto flex-1 space-y-4">
          
          {/* STEP 1: Info & Vault check */}
          {step === 'info' && (
            <div className="space-y-4">
              {/* Vault Auto-Fill Banner */}
              <div className="bg-emerald-50 border border-emerald-200 rounded-2xl p-3.5 flex items-start gap-3">
                <ShieldCheck className="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                <div>
                  <h4 className="font-bold text-xs text-emerald-900 flex items-center gap-1.5">
                    <span>تکمیل خودکار از مخزن مدارک هوشمند</span>
                    <span className="bg-emerald-200/80 text-emerald-800 text-[10px] px-1.5 py-0.2 rounded-full">فعال</span>
                  </h4>
                  <p className="text-[11px] text-emerald-800 mt-1 leading-relaxed">
                    اطلاعات سجلی، کد پستی و نشانی شما به طور خودکار از پرونده شهروندی استخراج و ثبت شد و نیازی به تایپ مجدد نیست.
                  </p>
                </div>
              </div>

              {/* Citizen Details Box */}
              <div className="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/70 space-y-2.5 text-xs">
                <span className="font-bold text-slate-700 block pb-1.5 border-b border-slate-200/70">
                  مشخصات استخراج شده متقاضی:
                </span>
                
                <div className="grid grid-cols-2 gap-2 text-slate-600">
                  <div>
                    <span className="text-slate-400 block text-[10px]">نام و نام خانوادگی:</span>
                    <span className="font-bold text-slate-900">{citizenName}</span>
                  </div>
                  <div>
                    <span className="text-slate-400 block text-[10px]">کد ملی:</span>
                    <span className="font-bold text-slate-900 tracking-wider">{citizenNationalId}</span>
                  </div>
                  <div>
                    <span className="text-slate-400 block text-[10px]">شماره تماس:</span>
                    <span className="font-bold text-slate-900">{citizenMobile}</span>
                  </div>
                  <div>
                    <span className="text-slate-400 block text-[10px]">کد پستی ۱۰ رقمی:</span>
                    <span className="font-bold text-slate-900 tracking-wider">{citizenPostalCode}</span>
                  </div>
                </div>

                <div className="pt-1.5 border-t border-slate-200/50">
                  <span className="text-slate-400 block text-[10px]">نشانی پستی محل سکونت:</span>
                  <span className="font-medium text-slate-800 text-[11px]">{citizenAddress}</span>
                </div>
              </div>

              {/* Service tags & requirements */}
              <div>
                <span className="text-xs font-bold text-slate-700 mb-2 block">نوع و شرایط انجام این خدمت:</span>
                <div className="flex flex-wrap gap-2 mb-3">
                  {service.tags.map(t => (
                    <ServiceTagBadge key={t} tag={t} />
                  ))}
                </div>

                <div className="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-3 text-xs space-y-1.5">
                  <span className="font-bold text-indigo-950 flex items-center gap-1.5">
                    <FileCheck2 className="w-4 h-4 text-indigo-600" />
                    مدارک مورد نیاز جهت پرونده:
                  </span>
                  <ul className="space-y-1 mr-2 list-disc list-inside text-indigo-900 text-[11px]">
                    {service.requirements.map((req, i) => (
                      <li key={i} className="flex items-center gap-1.5">
                        <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                        <span>{req}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>

              {/* Button */}
              <button
                onClick={() => setStep('dispatch_type')}
                className="w-full bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-extrabold py-3.5 rounded-2xl shadow-lg shadow-emerald-600/25 flex items-center justify-center gap-2 text-xs sm:text-sm transition-all"
              >
                <span>مرحله بعد: انتخاب شیوه ارجاع به دفتر</span>
                <ChevronLeft className="w-4 h-4" />
              </button>
            </div>
          )}

          {/* STEP 2: Dispatch Type (Snapp Auto vs Manual) */}
          {step === 'dispatch_type' && (
            <div className="space-y-4">
              <div>
                <h4 className="text-sm font-extrabold text-slate-800">نحوه انتخاب و ارجاع به دفتر پیشخوان</h4>
                <p className="text-xs text-slate-500 mt-1">مشخص نمایید پرونده شما چگونه به دفاتر پیشخوان ارسال گردد:</p>
              </div>

              {/* Mode A: Snapp Dispatch */}
              <div 
                onClick={() => setDispatchMode('auto_snapp')}
                className={`p-4 rounded-2xl border-2 transition-all cursor-pointer relative ${
                  dispatchMode === 'auto_snapp'
                    ? 'border-emerald-500 bg-emerald-50/50 shadow-md ring-4 ring-emerald-500/10'
                    : 'border-slate-200 hover:bg-slate-50'
                }`}
              >
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-2.5">
                    <div className="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
                      <Zap className="w-5 h-5" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h5 className="font-extrabold text-xs sm:text-sm text-slate-900">
                          ارسال هوشمند به نزدیک‌ترین دفتر آنلاین (مشابه اسنپ)
                        </h5>
                        <span className="bg-emerald-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full">پیشنهادی</span>
                      </div>
                      <p className="text-[11px] text-slate-600 mt-1 leading-relaxed">
                        سیستم به طور خودکار نزدیک‌ترین دفتری که هم‌اکنون آنلاین و خلوت است را یافته و درخواست را به آن اساین می‌کند.
                      </p>
                    </div>
                  </div>
                </div>

                <div className="mt-3 pt-2.5 border-t border-emerald-200/60 flex items-center justify-between text-[11px] text-emerald-800 font-semibold">
                  <span>میانگین زمان پذیرش: ۲ دقیقه</span>
                  <span className="text-emerald-700">تضمین بالاترین امتیاز رضایت</span>
                </div>
              </div>

              {/* Mode B: Manual Selection */}
              <div 
                onClick={() => setDispatchMode('manual_office')}
                className={`p-4 rounded-2xl border-2 transition-all cursor-pointer relative ${
                  dispatchMode === 'manual_office'
                    ? 'border-indigo-500 bg-indigo-50/50 shadow-md ring-4 ring-indigo-500/10'
                    : 'border-slate-200 hover:bg-slate-50'
                }`}
              >
                <div className="flex items-start gap-2.5">
                  <div className="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                    <Building2 className="w-5 h-5" />
                  </div>
                  <div className="flex-1">
                    <h5 className="font-extrabold text-xs sm:text-sm text-slate-900">
                      انتخاب دستی از روی لیست و نقشه دفاتر
                    </h5>
                    <p className="text-[11px] text-slate-600 mt-1 leading-relaxed">
                      شما می‌توانید دفتر پیشخوان خاصی را با بررسی مدال‌ها، تخصص‌ها و امتیاز انتخاب کنید.
                    </p>

                    {dispatchMode === 'manual_office' && (
                      <div className="mt-3 space-y-2">
                        <label className="text-xs font-bold text-slate-700 block">انتخاب دفتر جهت ارجاع برخط پرونده:</label>
                        <select
                          value={selectedOfficeId}
                          onChange={(e) => setSelectedOfficeId(e.target.value)}
                          className="w-full bg-white border border-indigo-200 rounded-xl p-2 text-xs font-semibold text-slate-800 outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                          {offices.map(off => {
                            const status = off.membershipStatus || (off.isOnline ? 'registered_online' : 'registered_offline');
                            const isOnlineMember = status === 'registered_online';
                            return (
                              <option 
                                key={off.id} 
                                value={off.id}
                                disabled={!isOnlineMember}
                              >
                                {isOnlineMember ? '🟢 ' : status === 'unregistered' ? '🟠 (غیرعضو) ' : '⚪ (آفلاین) '}
                                {off.name} - ({off.distanceKm} ک.م | امتیاز {off.rating} ★)
                                {!isOnlineMember ? ' [عدم پذیرش آنلاین]' : ' [آماده پذیرش]'}
                              </option>
                            );
                          })}
                        </select>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Navigation buttons */}
              <div className="flex items-center gap-2 pt-2">
                <button
                  onClick={() => setStep('info')}
                  className="px-4 py-3 bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold rounded-2xl text-xs"
                >
                  بازگشت
                </button>
                <button
                  onClick={() => setStep('payment')}
                  className="flex-1 bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-extrabold py-3.5 rounded-2xl shadow-lg shadow-emerald-600/25 flex items-center justify-center gap-2 text-xs sm:text-sm transition-all"
                >
                  <span>مرحله بعد: تایید نهایی و پرداخت کارمزد</span>
                  <ChevronLeft className="w-4 h-4" />
                </button>
              </div>
            </div>
          )}

          {/* STEP 3: Payment */}
          {step === 'payment' && (
            <div className="space-y-4">
              <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                <div className="flex items-center justify-between pb-2.5 border-b border-slate-200">
                  <span className="text-xs font-bold text-slate-600">خدمت درخواستی:</span>
                  <span className="text-xs font-extrabold text-slate-900">{service.title}</span>
                </div>
                <div className="flex items-center justify-between pb-2.5 border-b border-slate-200">
                  <span className="text-xs font-bold text-slate-600">شیوه ارجاع:</span>
                  <span className="text-xs font-extrabold text-emerald-700">
                    {dispatchMode === 'auto_snapp' ? '⚡ ارسال هوشمند نزدیک‌ترین دفتر آنلاین' : `📍 دفتر منتخب: ${selectedOffice.name}`}
                  </span>
                </div>
                <div className="flex items-center justify-between text-sm">
                  <span className="font-extrabold text-slate-800">مبلغ کل کارمزد دولتی:</span>
                  <span className="font-black text-emerald-600 text-base">
                    {service.fee === 0 ? 'رایگان' : `${service.fee.toLocaleString('fa-IR')} تومان`}
                  </span>
                </div>
              </div>

              {/* Payment Method Selector */}
              <div>
                <label className="text-xs font-bold text-slate-700 mb-2 block">روش پرداخت:</label>
                <div className="grid grid-cols-2 gap-2.5">
                  <button
                    onClick={() => setPaymentMethod('wallet')}
                    className={`p-3 rounded-2xl border text-right transition-all cursor-pointer ${
                      paymentMethod === 'wallet'
                        ? 'border-emerald-500 bg-emerald-50 text-emerald-950 ring-2 ring-emerald-500/20'
                        : 'border-slate-200 text-slate-700 hover:bg-slate-50'
                    }`}
                  >
                    <div className="flex items-center gap-1.5 mb-1">
                      <CreditCard className="w-4 h-4 text-emerald-600" />
                      <span className="font-extrabold text-xs">کیف پول شهروندی</span>
                    </div>
                    <span className="text-[10px] text-slate-500 block">
                      موجودی: {profile.walletBalance.toLocaleString('fa-IR')} تومان
                    </span>
                  </button>

                  <button
                    onClick={() => setPaymentMethod('gateway')}
                    className={`p-3 rounded-2xl border text-right transition-all cursor-pointer ${
                      paymentMethod === 'gateway'
                        ? 'border-emerald-500 bg-emerald-50 text-emerald-950 ring-2 ring-emerald-500/20'
                        : 'border-slate-200 text-slate-700 hover:bg-slate-50'
                    }`}
                  >
                    <div className="flex items-center gap-1.5 mb-1">
                      <Zap className="w-4 h-4 text-amber-500" />
                      <span className="font-extrabold text-xs">درگاه شاپرک (شتاب)</span>
                    </div>
                    <span className="text-[10px] text-slate-500 block">
                      کارت‌های بانکی شتاب
                    </span>
                  </button>
                </div>
              </div>

              {/* Launch button */}
              <div className="flex items-center gap-2 pt-2">
                <button
                  onClick={() => setStep('dispatch_type')}
                  className="px-4 py-3.5 bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold rounded-2xl text-xs"
                >
                  بازگشت
                </button>
                <button
                  onClick={() => setStep('searching')}
                  className="flex-1 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:scale-98 text-white font-extrabold py-3.5 rounded-2xl shadow-xl shadow-emerald-600/25 flex items-center justify-center gap-2 text-xs sm:text-sm transition-all cursor-pointer"
                >
                  <Zap className="w-4 h-4 text-amber-300" />
                  <span>پرداخت و ارسال درخواست به پیشخوان</span>
                </button>
              </div>
            </div>
          )}

          {/* STEP 4: Snapp Searching Radar Animation */}
          {step === 'searching' && (
            <div className="text-center py-8 space-y-6">
              <div className="relative w-28 h-28 mx-auto flex items-center justify-center">
                {/* Pulsing Radar Rings */}
                <div className="absolute inset-0 rounded-full bg-emerald-500/20 animate-ping duration-1000" />
                <div className="absolute inset-2 rounded-full bg-emerald-500/30 animate-pulse" />
                <div className="relative w-16 h-16 rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-xl shadow-emerald-500/40">
                  <Radio className="w-8 h-8 animate-spin" />
                </div>
              </div>

              <div>
                <h4 className="font-black text-base text-slate-900">
                  {searchPhase === 1 && 'در حال جستجوی نزدیک‌ترین دفاتر پیشخوان فعال...'}
                  {searchPhase === 2 && 'ارسال سیگنال درخواست به دفتر پیشخوان ولی‌عصر (فاصله ۸۰۰ متر)...'}
                  {searchPhase === 3 && 'دفتر پیشخوان درخواست شما را دریافت و تایید نمود!'}
                </h4>
                <p className="text-xs text-slate-500 mt-2">
                  سیستم در حال اختصاص کارشناس سجلی و صدور کد رهگیری پرونده می‌باشد...
                </p>
              </div>

              <div className="max-w-xs mx-auto bg-slate-50 p-3 rounded-2xl border border-slate-200 text-xs space-y-1.5">
                <div className="flex items-center justify-between text-slate-600">
                  <span>نوع ارجاع:</span>
                  <span className="font-bold text-emerald-700">اعزام هوشمند اسنپ‌پیشخوان</span>
                </div>
                <div className="flex items-center justify-between text-slate-600">
                  <span>وضعیت سرور ثبت احوال:</span>
                  <span className="font-bold text-emerald-600 flex items-center gap-1">
                    <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                    پایدار و متصل
                  </span>
                </div>
              </div>
            </div>
          )}

          {/* STEP 5: Successfully Assigned to Office */}
          {step === 'assigned' && assignedOffice && createdCase && (
            <div className="text-center py-4 space-y-5">
              <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto shadow-md shadow-emerald-500/20">
                <CheckCircle2 className="w-10 h-10" />
              </div>

              <div>
                <span className="bg-emerald-100 text-emerald-800 text-xs font-extrabold px-3 py-1 rounded-full">
                  درخواست با موفقیت به دفتر اساین شد
                </span>
                <h4 className="font-black text-lg text-slate-900 mt-2">{service.title}</h4>
                <p className="text-xs text-slate-500 mt-1">
                  کد رهگیری ملی شما: <span className="font-black text-slate-900 text-sm tracking-wider">{createdCase.trackingCode}</span>
                </p>
              </div>

              {/* Assigned Office Info Card */}
              <div className="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-4 text-right space-y-2.5 shadow-lg">
                <div className="flex items-center justify-between border-b border-slate-700/80 pb-2">
                  <div className="flex items-center gap-2">
                    <Building2 className="w-4 h-4 text-emerald-400" />
                    <span className="font-extrabold text-xs text-white">{assignedOffice.name}</span>
                  </div>
                  <span className="text-amber-400 font-bold text-xs">{assignedOffice.rating} ⭐</span>
                </div>

                <div className="grid grid-cols-2 gap-2 text-[11px] text-slate-300">
                  <div>
                    <span className="text-slate-400 block text-[10px]">مدیر دفتر:</span>
                    <span>{assignedOffice.managerName}</span>
                  </div>
                  <div>
                    <span className="text-slate-400 block text-[10px]">تلفن پیگیری:</span>
                    <span className="font-mono">{assignedOffice.phone}</span>
                  </div>
                </div>

                <p className="text-[10px] text-slate-400 pt-1 border-t border-slate-700/50">
                  نشانی: {assignedOffice.address}
                </p>
              </div>

              {/* Action buttons */}
              <div className="space-y-2 pt-2">
                <button
                  onClick={onClose}
                  className="w-full bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-extrabold py-3.5 rounded-2xl shadow-lg shadow-emerald-600/25 text-xs sm:text-sm transition-all"
                >
                  مشاهده روند پرونده در گراف پیگیری
                </button>
              </div>
            </div>
          )}

        </div>

      </div>
    </div>
  );
};
