import React, { useState, useEffect } from 'react';
import { motion } from 'motion/react';
import { 
  ShieldCheck, 
  Smartphone, 
  KeyRound, 
  User, 
  CheckCircle2, 
  ArrowRight, 
  ArrowLeft, 
  Sparkles, 
  Lock, 
  RefreshCw, 
  Info, 
  AlertCircle,
  X,
  UserPlus,
  LogIn
} from 'lucide-react';
import { CitizenProfile } from '../types';
import { INITIAL_CITIZEN_PROFILE } from '../data/mockData';

// Known registered members database (for smart lookup)
const REGISTERED_USERS_DB: Record<string, Partial<CitizenProfile>> = {
  '09123456781': {
    ...INITIAL_CITIZEN_PROFILE,
    mobile: '۰۹۱۲۳۴۵۶۷۸۱',
    nationalId: '۰۰۸۲۳۴۵۶۷۱',
    fullName: 'سید علی حسینی'
  },
  '09121112233': {
    ...INITIAL_CITIZEN_PROFILE,
    mobile: '۰۹۱۲۱۱۱۲۲۳۳',
    nationalId: '۰۰۷۶۵۴۳۲۱۰',
    fullName: 'رضا محمدی'
  },
  '09351234567': {
    ...INITIAL_CITIZEN_PROFILE,
    mobile: '۰۹۳۵۱۲۳۴۵۶۷',
    nationalId: '۰۰۱۹۸۴۷۲۶۱',
    fullName: 'مریم سلیمانی',
    tier: 'silver',
    tierName: 'شهروند نقره‌ای (احراز هویت شده)'
  }
};

interface CitizenLoginViewProps {
  onLoginSuccess: (profile: CitizenProfile) => void;
  onCancel?: () => void;
  isModal?: boolean;
  contextMessage?: string;
  targetTabName?: string;
}

export const CitizenLoginView: React.FC<CitizenLoginViewProps> = ({
  onLoginSuccess,
  onCancel,
  isModal = false,
  contextMessage,
  targetTabName = 'حساب کاربری'
}) => {
  // Steps:
  // 1. 'phone_input': Only mobile number is requested
  // 2. 'national_id_input': Only shown if the user is NOT previously registered
  // 3. 'otp_verify': User enters the received SMS code
  const [step, setStep] = useState<'phone_input' | 'national_id_input' | 'otp_verify'>('phone_input');
  
  // User Inputs
  const [mobileNumber, setMobileNumber] = useState<string>('09123456781');
  const [nationalId, setNationalId] = useState<string>('');
  const [fullName, setFullName] = useState<string>('');
  const [otpCode, setOtpCode] = useState<string>('');
  
  // Registration Status
  const [isExistingUser, setIsExistingUser] = useState<boolean>(true);
  const [matchedProfile, setMatchedProfile] = useState<Partial<CitizenProfile> | null>(null);

  // OTP timer & loading
  const [countdown, setCountdown] = useState<number>(120);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [simulatedSmsReceived, setSimulatedSmsReceived] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // Helper to convert Persian/Arabic digits to English
  const toEnglishDigits = (str: string): string => {
    return str
      .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d).toString())
      .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d).toString());
  };

  // Countdown effect for OTP
  useEffect(() => {
    let timer: any;
    if (step === 'otp_verify' && countdown > 0) {
      timer = setInterval(() => {
        setCountdown(prev => prev - 1);
      }, 1000);
    }
    return () => clearInterval(timer);
  }, [step, countdown]);

  // STEP 1: Handle Mobile Submit
  const handlePhoneSubmit = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    setErrorMessage(null);

    const cleanMobile = toEnglishDigits(mobileNumber.trim());
    if (!cleanMobile.startsWith('09') || cleanMobile.length !== 11) {
      setErrorMessage('لطفاً شماره تلفن همراه معتبر ۱۱ رقمی وارد نمایید (مثال: ۰۹۱۲۳۴۵۶۷۸۱)');
      return;
    }

    setIsLoading(true);

    setTimeout(() => {
      setIsLoading(false);
      // Check if mobile number is in database
      const existing = REGISTERED_USERS_DB[cleanMobile];

      if (existing) {
        // User already registered -> Direct to OTP
        setIsExistingUser(true);
        setMatchedProfile(existing);
        setNationalId(existing.nationalId || '');
        setFullName(existing.fullName || '');
        
        // Trigger OTP
        triggerOtpFlow();
      } else {
        // New user -> Ask for National ID first, then OTP
        setIsExistingUser(false);
        setMatchedProfile(null);
        setStep('national_id_input');
      }
    }, 500);
  };

  // STEP 1.5: Handle National ID Submit (For New Users)
  const handleNationalIdSubmit = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    setErrorMessage(null);

    const cleanNationalId = toEnglishDigits(nationalId.trim());
    if (cleanNationalId.length !== 10 || !/^\d{10}$/.test(cleanNationalId)) {
      setErrorMessage('لطفاً کد ملی ۱۰ رقمی معتبر وارد نمایید.');
      return;
    }

    setIsLoading(true);
    setTimeout(() => {
      setIsLoading(false);
      // Now proceed to OTP verification
      triggerOtpFlow();
    }, 500);
  };

  // Helper to trigger OTP flow
  const triggerOtpFlow = () => {
    setStep('otp_verify');
    setCountdown(120);
    const generatedCode = '48291';
    setSimulatedSmsReceived(generatedCode);
    setOtpCode(generatedCode); // pre-fill for effortless UX
  };

  // STEP 2: Handle OTP Verification & Login
  const handleVerifyOtp = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    setErrorMessage(null);

    const cleanOtp = toEnglishDigits(otpCode.trim());
    if (!cleanOtp || cleanOtp.length < 4) {
      setErrorMessage('لطفاً کد تایید ۵ رقمی ارسال شده را وارد نمایید.');
      return;
    }

    setIsLoading(true);
    setTimeout(() => {
      setIsLoading(false);

      if (isExistingUser && matchedProfile) {
        // Existing user profile
        onLoginSuccess({
          ...INITIAL_CITIZEN_PROFILE,
          ...matchedProfile,
          mobile: mobileNumber
        } as CitizenProfile);
      } else {
        // Brand new citizen profile
        const newProfile: CitizenProfile = {
          ...INITIAL_CITIZEN_PROFILE,
          fullName: fullName.trim() || 'شهروند گرامی',
          nationalId: nationalId.trim(),
          mobile: mobileNumber.trim(),
          tier: 'silver',
          tierName: 'شهروند نقره‌ای (احراز هویت شده)',
          walletBalance: 100000,
          documents: INITIAL_CITIZEN_PROFILE.documents.slice(0, 2)
        };
        onLoginSuccess(newProfile);
      }
    }, 600);
  };

  const containerContent = (
    <div className="w-full max-w-md mx-auto bg-white rounded-3xl shadow-xl border border-slate-100 p-5 sm:p-7 text-right overflow-hidden relative" dir="rtl">
      
      {/* Decorative Brand Accent */}
      <div className="absolute -top-16 -right-16 w-36 h-36 bg-blue-500/10 rounded-full blur-2xl pointer-events-none" />
      <div className="absolute -bottom-16 -left-16 w-36 h-36 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none" />

      {/* Top Header / Modal Close */}
      <div className="flex items-center justify-between pb-4 border-b border-slate-100 relative z-10">
        <div className="flex items-center gap-2.5">
          <div className="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#2e4368] to-[#475d84] text-white flex items-center justify-center shadow-md shadow-blue-900/10">
            <ShieldCheck className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-base sm:text-lg font-black text-slate-900 leading-tight">
              ورود به سامانه پیشخوان دولت
            </h2>
            <span className="text-[11px] font-bold text-slate-400">
              احراز هویت امن با شماره همراه و رمز یکبار مصرف
            </span>
          </div>
        </div>

        {onCancel && (
          <button
            onClick={onCancel}
            className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors cursor-pointer"
            title="بستن"
          >
            <X className="w-4 h-4" />
          </button>
        )}
      </div>

      {/* Context Notice if redirected from cases/profile */}
      {contextMessage && (
        <div className="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-2.5 text-xs text-amber-900 animate-in fade-in">
          <Info className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
          <div>
            <p className="font-extrabold">{contextMessage}</p>
            <p className="text-[10px] text-amber-700/90 mt-0.5">
              جهت حفظ حریم خصوصی، اسناد هویتی و پیگیری درخواست‌ها نیازمند ورود به حساب کاربری است.
            </p>
          </div>
        </div>
      )}

      {/* ERROR MESSAGE IF ANY */}
      {errorMessage && (
        <div className="mt-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-bold text-rose-700 flex items-center gap-2 animate-in shake">
          <AlertCircle className="w-4 h-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STEP 1: ONLY PHONE NUMBER INPUT                                            */}
      {/* ========================================================================= */}
      {step === 'phone_input' && (
        <form onSubmit={handlePhoneSubmit} className="mt-5 space-y-4 relative z-10 animate-in fade-in">
          <div>
            <label className="block text-xs font-black text-slate-700 mb-2 flex items-center justify-between">
              <span className="flex items-center gap-1.5">
                <Smartphone className="w-4 h-4 text-blue-600" />
                شماره تلفن همراه
              </span>
              <span className="text-[10px] text-slate-400 font-normal">به نام متقاضی</span>
            </label>
            <div className="relative">
              <input
                type="tel"
                dir="ltr"
                value={mobileNumber}
                onChange={(e) => setMobileNumber(e.target.value)}
                placeholder="09123456789"
                className="w-full bg-slate-50 border-2 border-slate-200 focus:border-blue-600 focus:bg-white rounded-2xl py-3.5 px-4 text-center font-mono font-black text-lg text-slate-900 outline-none transition-all placeholder:text-slate-300"
                autoFocus
                required
              />
            </div>
            <p className="text-[11px] text-slate-400 mt-1.5 px-1 leading-relaxed">
              کد تایید ورود از طریق پیامک به این شماره ارسال خواهد شد.
            </p>
          </div>

          <div className="pt-2">
            <button
              type="submit"
              disabled={isLoading}
              className="w-full bg-gradient-to-r from-blue-700 to-indigo-700 hover:from-blue-600 hover:to-indigo-600 active:scale-98 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-blue-600/20 flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
            >
              {isLoading ? (
                <>
                  <RefreshCw className="w-4 h-4 animate-spin" />
                  <span>در حال بررسی حساب کاربری...</span>
                </>
              ) : (
                <>
                  <span>دریافت کد تایید</span>
                  <ArrowLeft className="w-4 h-4" />
                </>
              )}
            </button>
          </div>

          <p className="text-[10px] text-slate-400 text-center leading-relaxed pt-1">
            با ورود به سامانه، شرایط استفاده از خدمات الکترونیک پیشخوان دولت را می‌پذیرید.
          </p>
        </form>
      )}

      {/* ========================================================================= */}
      {/* STEP 1.5: NATIONAL ID & NAME INPUT (FOR NEW USERS NOT PREVIOUSLY REGISTERED) */}
      {/* ========================================================================= */}
      {step === 'national_id_input' && (
        <form onSubmit={handleNationalIdSubmit} className="mt-5 space-y-4 relative z-10 animate-in fade-in">
          <div className="p-3 bg-blue-50/80 border border-blue-200 rounded-2xl flex items-start gap-2.5 text-xs text-blue-950">
            <UserPlus className="w-4 h-4 text-blue-700 shrink-0 mt-0.5" />
            <div>
              <p className="font-extrabold text-blue-900">حساب کاربری جدید</p>
              <p className="text-[11px] text-blue-800/80 mt-0.5">
                شماره <span className="font-mono font-black" dir="ltr">{mobileNumber}</span> برای نخستین بار وارد سامانه شده است. لطفاً کد ملی خود را وارد نمایید.
              </p>
            </div>
          </div>

          <div>
            <label className="block text-xs font-black text-slate-700 mb-1.5 flex items-center justify-between">
              <span className="flex items-center gap-1.5">
                <User className="w-3.5 h-3.5 text-blue-600" />
                کد ملی ۱۰ رقمی
              </span>
              <span className="text-[10px] text-slate-400 font-normal">بدون خط تیره</span>
            </label>
            <div className="relative">
              <input
                type="text"
                dir="ltr"
                maxLength={10}
                value={nationalId}
                onChange={(e) => setNationalId(e.target.value)}
                placeholder="0012345678"
                className="w-full bg-slate-50 border-2 border-slate-200 focus:border-blue-600 focus:bg-white rounded-2xl py-3 px-4 text-center font-mono font-bold text-base text-slate-900 outline-none transition-all placeholder:text-slate-300"
                autoFocus
                required
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-black text-slate-700 mb-1.5 flex items-center justify-between">
              <span>نام و نام خانوادگی (اختیاری)</span>
              <span className="text-[10px] text-slate-400 font-normal">مطابق کارت ملی</span>
            </label>
            <input
              type="text"
              value={fullName}
              onChange={(e) => setFullName(e.target.value)}
              placeholder="مثال: علی رضایی"
              className="w-full bg-slate-50 border-2 border-slate-200 focus:border-blue-600 focus:bg-white rounded-2xl py-2.5 px-4 text-slate-900 text-sm font-bold outline-none transition-all"
            />
          </div>

          <div className="flex items-center gap-2 pt-2">
            <button
              type="button"
              onClick={() => {
                setStep('phone_input');
                setErrorMessage(null);
              }}
              className="px-4 py-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black transition-colors cursor-pointer flex items-center gap-1"
            >
              <ArrowRight className="w-3.5 h-3.5" />
              <span>بازگشت</span>
            </button>

            <button
              type="submit"
              disabled={isLoading}
              className="flex-1 bg-gradient-to-r from-blue-700 to-indigo-700 hover:from-blue-600 hover:to-indigo-600 active:scale-98 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-blue-600/20 flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
            >
              {isLoading ? (
                <>
                  <RefreshCw className="w-4 h-4 animate-spin" />
                  <span>در حال ارسال پیامک...</span>
                </>
              ) : (
                <>
                  <span>ارسال رمز یکبار مصرف</span>
                  <ArrowLeft className="w-4 h-4" />
                </>
              )}
            </button>
          </div>
        </form>
      )}

      {/* ========================================================================= */}
      {/* STEP 2: VERIFY OTP CODE (SMS)                                             */}
      {/* ========================================================================= */}
      {step === 'otp_verify' && (
        <form onSubmit={handleVerifyOtp} className="mt-5 space-y-4 relative z-10 animate-in fade-in">
          
          {/* Simulated SMS Notification Popup */}
          {simulatedSmsReceived && (
            <div className="bg-emerald-50 border border-emerald-300 rounded-2xl p-3 text-emerald-950 text-xs shadow-xs space-y-1">
              <div className="flex items-center justify-between">
                <span className="font-extrabold flex items-center gap-1.5 text-emerald-800">
                  <Smartphone className="w-3.5 h-3.5 text-emerald-600" />
                  پیامک حاوی رمز یکبار مصرف دریافت شد
                </span>
                <span className="text-[10px] font-mono text-emerald-600">هم‌اکنون</span>
              </div>
              <p className="text-[11px] text-emerald-800 leading-tight">
                کد تایید ورود پیشخوان: <strong className="font-mono text-sm font-black px-1.5 py-0.5 bg-emerald-200/60 rounded text-emerald-950">{simulatedSmsReceived}</strong>
              </p>
            </div>
          )}

          <div className="space-y-1.5 text-center">
            <p className="text-xs text-slate-600 font-bold">
              رمز یکبار مصرف به شماره <span className="font-mono text-blue-900 font-black" dir="ltr">{mobileNumber}</span> ارسال شد.
            </p>
            <button
              type="button"
              onClick={() => {
                setStep('phone_input');
                setErrorMessage(null);
              }}
              className="text-[11px] text-blue-700 hover:underline font-bold cursor-pointer inline-flex items-center gap-1"
            >
              <span>ویرایش شماره همراه</span>
            </button>
          </div>

          <div>
            <label className="block text-xs font-black text-slate-700 mb-2 text-center">
              کد تایید ۵ رقمی را وارد نمایید
            </label>
            <input
              type="text"
              dir="ltr"
              maxLength={5}
              value={otpCode}
              onChange={(e) => setOtpCode(e.target.value)}
              placeholder="•••••"
              className="w-full bg-slate-50 border-2 border-indigo-300 focus:border-blue-600 focus:bg-white rounded-2xl py-3 px-4 text-center font-mono font-black text-2xl tracking-[0.4em] text-slate-900 outline-none transition-all"
              autoFocus
              required
            />
          </div>

          {/* Timer / Resend */}
          <div className="flex items-center justify-between text-xs text-slate-500 pt-1">
            <span>
              {countdown > 0 ? (
                <span className="font-mono font-bold text-slate-700">
                  ارسال مجدد تا {Math.floor(countdown / 60)}:{(countdown % 60).toString().padStart(2, '0')}
                </span>
              ) : (
                <button
                  type="button"
                  onClick={() => triggerOtpFlow()}
                  className="text-blue-700 font-black hover:underline cursor-pointer"
                >
                  ارسال مجدد کد تایید
                </button>
              )}
            </span>
            
            <button
              type="button"
              onClick={() => setOtpCode('48291')}
              className="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-2 py-1 rounded-lg cursor-pointer"
            >
              درج کد (۴۸۲۹۱)
            </button>
          </div>

          <div className="pt-2">
            <button
              type="submit"
              disabled={isLoading}
              className="w-full bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 active:scale-98 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
            >
              {isLoading ? (
                <>
                  <RefreshCw className="w-4 h-4 animate-spin" />
                  <span>در حال تایید هویت...</span>
                </>
              ) : (
                <>
                  <CheckCircle2 className="w-4.5 h-4.5" />
                  <span>تایید و ورود به {targetTabName}</span>
                </>
              )}
            </button>
          </div>

        </form>
      )}

      {/* Safety Bottom Tag */}
      <div className="mt-6 pt-3 border-t border-slate-100 flex items-center justify-center gap-1.5 text-[10px] font-medium text-slate-400">
        <Lock className="w-3 h-3 text-emerald-600" />
        <span>ارتباط رمزنگاری شده ۲۵۶ بیتی با درگاه خدمات دولت</span>
      </div>

    </div>
  );

  if (isModal) {
    return (
      <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-200">
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 10 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 10 }}
          className="w-full max-w-md"
        >
          {containerContent}
        </motion.div>
      </div>
    );
  }

  return (
    <div className="py-2 px-1">
      {containerContent}
    </div>
  );
};
