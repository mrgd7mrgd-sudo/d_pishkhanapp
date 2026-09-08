import React, { useState } from 'react';
import { motion } from 'motion/react';
import { 
  Building2, 
  ShieldCheck, 
  KeyRound, 
  Lock, 
  UserCheck, 
  RefreshCw, 
  ArrowRight, 
  ArrowLeft, 
  AlertCircle, 
  Eye,
  EyeOff
} from 'lucide-react';
import { PishkhanOffice } from '../types';
import { MOCK_OFFICES } from '../data/mockData';

interface OfficeLoginViewProps {
  offices?: PishkhanOffice[];
  onLoginSuccess: (office: PishkhanOffice, operatorData?: { operatorName: string; counterNumber: number; role: string }) => void;
  onBackToCitizen: () => void;
}

export const OfficeLoginView: React.FC<OfficeLoginViewProps> = ({
  offices = MOCK_OFFICES,
  onLoginSuccess,
  onBackToCitizen
}) => {
  // Credentials (Only Username, Password, Captcha)
  const [operatorUsername, setOperatorUsername] = useState<string>('op_valiasr');
  const [operatorPassword, setOperatorPassword] = useState<string>('pishkhan@2024');
  const [showPassword, setShowPassword] = useState<boolean>(false);
  
  // Security & Captcha
  const [captchaCode, setCaptchaCode] = useState<string>('7492');
  const [captchaInput, setCaptchaInput] = useState<string>('7492');
  
  // States
  const [isAuthenticating, setIsAuthenticating] = useState<boolean>(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const refreshCaptcha = () => {
    const randomCode = Math.floor(1000 + Math.random() * 9000).toString();
    setCaptchaCode(randomCode);
    setCaptchaInput(randomCode);
  };

  const handleLoginSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);

    if (!operatorUsername.trim()) {
      setErrorMessage('لطفاً نام کاربری اپراتور را وارد نمایید.');
      return;
    }

    if (!operatorPassword.trim()) {
      setErrorMessage('لطفاً کلمه عبور را وارد نمایید.');
      return;
    }

    if (captchaInput.trim() !== captchaCode.trim()) {
      setErrorMessage('کد امنیتی تصویر (کپچا) نادرست است.');
      return;
    }

    setIsAuthenticating(true);
    setTimeout(() => {
      setIsAuthenticating(false);
      // Auto-assign to default office & active counter
      const targetOffice = offices[0] || MOCK_OFFICES[0];
      onLoginSuccess(targetOffice, {
        operatorName: operatorUsername.trim() || 'کارشناس باجه (رضا کاظمی)',
        counterNumber: 2,
        role: 'کارشناس باجه سجلی و ثبت احوال'
      });
    }, 800);
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between py-6 px-4 font-sans text-right relative overflow-hidden" dir="rtl">
      
      {/* Background Grid & Ambient Glow */}
      <div className="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] opacity-25 pointer-events-none" />
      <div className="absolute top-1/4 right-1/4 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute bottom-1/4 left-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none" />

      {/* Top Navbar */}
      <header className="max-w-4xl w-full mx-auto flex items-center justify-between relative z-10">
        <div className="flex items-center gap-3">
          <div className="w-11 h-11 rounded-2xl bg-emerald-600/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center shadow-lg shadow-emerald-950/50">
            <Building2 className="w-6 h-6" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="text-base font-black text-white">درگاه اختصاصی دفاتر پیشخوان دولت</span>
              <span className="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full font-bold">
                نسخه کارتابل سازمانی
              </span>
            </div>
            <p className="text-xs text-slate-400 mt-0.5">
              سامانه احراز هویت اپراتورها و مدیران دفاتر پیشخوان سراسر کشور
            </p>
          </div>
        </div>

        {/* Back to Citizen Mode */}
        <button
          onClick={onBackToCitizen}
          className="flex items-center gap-1.5 bg-slate-800/80 hover:bg-slate-700 active:scale-95 text-slate-200 hover:text-white text-xs font-bold px-3.5 py-2 rounded-xl border border-slate-700 transition-all cursor-pointer"
        >
          <ArrowRight className="w-4 h-4 text-slate-400" />
          <span>بازگشت به نمای شهروند</span>
        </button>
      </header>

      {/* Main Login Card */}
      <main className="max-w-md w-full mx-auto my-6 relative z-10">
        <div className="bg-slate-900/95 backdrop-blur-md rounded-3xl border border-slate-800 shadow-2xl p-6 sm:p-8 space-y-5">
          
          {/* Header Inside Card */}
          <div className="border-b border-slate-800/80 pb-4 text-center space-y-1.5">
            <div className="inline-flex items-center gap-2 bg-slate-800 text-slate-300 text-[11px] font-bold px-3 py-1 rounded-full border border-slate-700">
              <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
              <span>شبکه امن دولتی (MPLS / APN اختصاصی)</span>
            </div>
            <h2 className="text-lg sm:text-xl font-black text-white pt-1">
              ورود به کارتابل و میز کار باجه
            </h2>
            <p className="text-xs text-slate-400">
              جهت ورود، نام کاربری، کلمه عبور و کد امنیتی را وارد نمایید.
            </p>
          </div>

          {/* Error Message */}
          {errorMessage && (
            <div className="p-3 bg-rose-950/70 border border-rose-800 text-rose-200 rounded-2xl text-xs font-bold flex items-center gap-2 animate-in shake">
              <AlertCircle className="w-4 h-4 text-rose-400 shrink-0" />
              <span>{errorMessage}</span>
            </div>
          )}

          {/* Clean Login Form: Only Username, Password & Captcha */}
          <form onSubmit={handleLoginSubmit} className="space-y-4 text-xs">
            
            {/* Field 1: Operator Username */}
            <div>
              <label className="block font-bold text-slate-300 mb-1.5 flex items-center gap-1.5">
                <UserCheck className="w-3.5 h-3.5 text-emerald-400" />
                <span>نام کاربری اپراتور</span>
              </label>
              <input
                type="text"
                dir="ltr"
                value={operatorUsername}
                onChange={(e) => setOperatorUsername(e.target.value)}
                placeholder="op_username"
                className="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl p-3 text-left font-mono font-bold text-white outline-none transition-all placeholder:text-slate-600"
                autoFocus
                required
              />
            </div>

            {/* Field 2: Operator Password */}
            <div>
              <label className="block font-bold text-slate-300 mb-1.5 flex items-center justify-between">
                <span className="flex items-center gap-1.5">
                  <KeyRound className="w-3.5 h-3.5 text-emerald-400" />
                  <span>کلمه عبور امنیتی</span>
                </span>
              </label>

              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  dir="ltr"
                  value={operatorPassword}
                  onChange={(e) => setOperatorPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl p-3 text-left font-mono font-bold text-white outline-none pl-10 transition-all placeholder:text-slate-600"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white cursor-pointer"
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>

            {/* Field 3: Captcha */}
            <div>
              <label className="block font-bold text-slate-300 mb-1.5">
                کد امنیتی تصویر (کپچا)
              </label>
              <div className="flex items-center gap-2">
                <input
                  type="text"
                  dir="ltr"
                  maxLength={4}
                  value={captchaInput}
                  onChange={(e) => setCaptchaInput(e.target.value)}
                  placeholder="کد تصویر"
                  className="w-28 bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl p-2.5 text-center font-mono font-black text-white text-base outline-none transition-all"
                  required
                />
                <div className="flex-1 bg-slate-800 border border-slate-700 rounded-xl p-2 flex items-center justify-between select-none">
                  <span className="font-mono font-black text-emerald-300 tracking-widest text-base line-through decoration-emerald-500/50">
                    {captchaCode}
                  </span>
                  <button
                    type="button"
                    onClick={refreshCaptcha}
                    className="text-slate-400 hover:text-white p-1 cursor-pointer transition-colors"
                    title="تغییر تصویر کد امنیتی"
                  >
                    <RefreshCw className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>

            {/* Submit Button */}
            <div className="pt-2">
              <button
                type="submit"
                disabled={isAuthenticating}
                className="w-full bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 active:scale-98 text-white font-black py-3.5 rounded-2xl shadow-xl shadow-emerald-950/60 flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
              >
                {isAuthenticating ? (
                  <>
                    <RefreshCw className="w-4 h-4 animate-spin" />
                    <span>در حال بررسی اعتبارنامه کاربری و اتصال به باجه...</span>
                  </>
                ) : (
                  <>
                    <Building2 className="w-4.5 h-4.5" />
                    <span>ورود به کارتابل پیشخوان</span>
                    <ArrowLeft className="w-4 h-4" />
                  </>
                )}
              </button>
            </div>

          </form>

        </div>
      </main>

      {/* Footer System Status */}
      <footer className="max-w-4xl w-full mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-slate-500 border-t border-slate-900 pt-4 relative z-10">
        <div className="flex items-center gap-2">
          <ShieldCheck className="w-4 h-4 text-emerald-500" />
          <span>پروتکل ارتباطی TLS 1.3 | نظارت کانون کشوری دفاتر پیشخوان دولت</span>
        </div>
        <div>
          <span>پشتیبانی فنی شبکه دفاتر: ۲۱-۸۸۹۹۰۰۰۰</span>
        </div>
      </footer>

    </div>
  );
};
