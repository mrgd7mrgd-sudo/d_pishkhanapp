import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  ArrowRight, 
  Plus, 
  ArrowUpRight, 
  ArrowDownLeft, 
  ArrowLeftRight, 
  Gift, 
  Sparkles, 
  CheckCircle2, 
  X, 
  Zap, 
  AlertCircle
} from 'lucide-react';
import { CitizenProfile, WalletTransaction } from '../types';

interface WalletDetailsViewProps {
  profile: CitizenProfile;
  transactions: WalletTransaction[];
  onBack: () => void;
  onTopUp: (amountTomans: number, title?: string) => void;
  onWithdraw?: (amountTomans: number, iban: string) => boolean;
  onTransfer?: (amountTomans: number, recipientMobileOrId: string, note?: string) => boolean;
  onRedeemGiftCode?: (code: string) => { success: boolean; amount?: number; message: string };
}

export const WalletDetailsView: React.FC<WalletDetailsViewProps> = ({
  profile,
  transactions,
  onBack,
  onTopUp,
  onWithdraw,
  onTransfer,
  onRedeemGiftCode
}) => {
  // Modals for the 4 operations
  const [activeModal, setActiveModal] = useState<'topup' | 'withdraw' | 'transfer' | 'gift' | null>(null);

  // Top-Up state
  const [customTopUpAmount, setCustomTopUpAmount] = useState<string>('');
  const [selectedQuickTopUp, setSelectedQuickTopUp] = useState<number>(100000);
  const [topUpSuccess, setTopUpSuccess] = useState<boolean>(false);

  // Withdraw state
  const [withdrawAmount, setWithdrawAmount] = useState<string>('');
  const [withdrawIban, setWithdrawIban] = useState<string>('IR120170000000109283746501');
  const [withdrawSuccess, setWithdrawSuccess] = useState<boolean>(false);
  const [withdrawError, setWithdrawError] = useState<string>('');

  // Transfer state
  const [transferAmount, setTransferAmount] = useState<string>('');
  const [transferRecipient, setTransferRecipient] = useState<string>('');
  const [transferNote, setTransferNote] = useState<string>('');
  const [transferSuccess, setTransferSuccess] = useState<boolean>(false);
  const [transferError, setTransferError] = useState<string>('');

  // Gift Code state
  const [giftCode, setGiftCode] = useState<string>('');
  const [giftResult, setGiftResult] = useState<{ success: boolean; message: string; amount?: number } | null>(null);

  // Balance in Rial (1 Toman = 10 Rials)
  const balanceRial = profile.walletBalance * 10;

  // Quick Top-up amounts in Tomans
  const quickAmountsTomans = [50000, 100000, 200000, 500000];

  // Handle Top-Up
  const handleExecuteTopUp = () => {
    const amount = customTopUpAmount ? parseInt(customTopUpAmount) : selectedQuickTopUp;
    if (amount > 0) {
      onTopUp(amount, 'افزایش موجودی کیف پول آنلاین');
      setTopUpSuccess(true);
      setTimeout(() => {
        setTopUpSuccess(false);
        setActiveModal(null);
        setCustomTopUpAmount('');
      }, 1400);
    }
  };

  // Handle Withdraw
  const handleExecuteWithdraw = () => {
    const amount = parseInt(withdrawAmount);
    setWithdrawError('');
    if (!amount || amount <= 0) {
      setWithdrawError('لطفاً مبلغ معتبری وارد کنید.');
      return;
    }
    if (amount > profile.walletBalance) {
      setWithdrawError('موجودی کیف پول برای این برداشت کافی نیست.');
      return;
    }
    if (onWithdraw) {
      const ok = onWithdraw(amount, withdrawIban);
      if (ok) {
        setWithdrawSuccess(true);
        setTimeout(() => {
          setWithdrawSuccess(false);
          setActiveModal(null);
          setWithdrawAmount('');
        }, 1400);
      }
    } else {
      onTopUp(-amount, `برداشت از کیف پول به شبا ${withdrawIban.slice(0, 8)}...`);
      setWithdrawSuccess(true);
      setTimeout(() => {
        setWithdrawSuccess(false);
        setActiveModal(null);
        setWithdrawAmount('');
      }, 1400);
    }
  };

  // Handle Transfer
  const handleExecuteTransfer = () => {
    const amount = parseInt(transferAmount);
    setTransferError('');
    if (!amount || amount <= 0) {
      setTransferError('لطفاً مبلغ معتبری وارد کنید.');
      return;
    }
    if (!transferRecipient.trim()) {
      setTransferError('شماره موبایل یا کدملی مقصد را وارد کنید.');
      return;
    }
    if (amount > profile.walletBalance) {
      setTransferError('موجودی کیف پول کافی نیست.');
      return;
    }
    if (onTransfer) {
      const ok = onTransfer(amount, transferRecipient, transferNote);
      if (ok) {
        setTransferSuccess(true);
        setTimeout(() => {
          setTransferSuccess(false);
          setActiveModal(null);
          setTransferAmount('');
          setTransferRecipient('');
        }, 1400);
      }
    } else {
      onTopUp(-amount, `انتقال به شهروند (${transferRecipient})`);
      setTransferSuccess(true);
      setTimeout(() => {
        setTransferSuccess(false);
        setActiveModal(null);
        setTransferAmount('');
        setTransferRecipient('');
      }, 1400);
    }
  };

  // Handle Gift Code
  const handleExecuteGiftCode = () => {
    const code = giftCode.trim().toUpperCase();
    if (!code) return;

    if (onRedeemGiftCode) {
      const res = onRedeemGiftCode(code);
      setGiftResult(res);
      if (res.success) {
        setTimeout(() => {
          setGiftResult(null);
          setActiveModal(null);
          setGiftCode('');
        }, 1600);
      }
    } else {
      if (code === 'GIFT50' || code === 'PISHKHAN1403' || code === 'DIGIPAY50') {
        const bonus = 50000;
        onTopUp(bonus, `هدیه شارژ کیف پول - کد ${code}`);
        setGiftResult({ success: true, message: 'کد هدیه ۵۰,۰۰۰ تومانی با موفقیت اعمال شد!', amount: bonus });
        setTimeout(() => {
          setGiftResult(null);
          setActiveModal(null);
          setGiftCode('');
        }, 1600);
      } else if (code === 'WELCOME100' || code === 'EID1403') {
        const bonus = 100000;
        onTopUp(bonus, `هدیه ویژه عضویت - کد ${code}`);
        setGiftResult({ success: true, message: 'کد هدیه ۱۰۰,۰۰۰ تومانی با موفقیت اعمال شد!', amount: bonus });
        setTimeout(() => {
          setGiftResult(null);
          setActiveModal(null);
          setGiftCode('');
        }, 1600);
      } else {
        setGiftResult({ success: false, message: 'کد هدیه وارد شده نامعتبر یا منقضی شده است.' });
      }
    }
  };

  return (
    <div className="min-h-[80vh] bg-white rounded-3xl pb-10 animate-in fade-in duration-200 border border-slate-100 shadow-sm">
      
      {/* Top Header */}
      <div className="p-4 border-b border-slate-100 flex items-center justify-between">
        <button
          onClick={onBack}
          className="flex items-center gap-1.5 text-slate-700 hover:text-slate-950 text-xs font-black p-2 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer"
        >
          <ArrowRight className="w-4 h-4" />
          <span>بازگشت به خانه</span>
        </button>

        <h2 className="text-sm font-black text-slate-900">کیف پول</h2>
        
        <div className="w-16" /> {/* Placeholder for symmetry */}
      </div>

      {/* Main Content Body */}
      <div className="p-4 sm:p-5 space-y-6">
        
        {/* 1. Large Balance Center Area */}
        <div className="text-center py-4 space-y-1.5">
          <span className="text-xs font-bold text-slate-500">موجودی</span>
          <div className="flex items-center justify-center gap-2 text-3xl sm:text-4xl font-black text-slate-950 tracking-tight">
            <span>{balanceRial.toLocaleString('fa-IR')}</span>
            <span className="text-base font-extrabold text-slate-800">ریال</span>
          </div>
          <span className="text-[11px] text-slate-400 font-medium block">
            معادل {profile.walletBalance.toLocaleString('fa-IR')} تومان
          </span>
        </div>

        {/* 2. Four Action Buttons (Matching Screenshot 2) */}
        <div className="flex items-center justify-between gap-2 sm:gap-3 px-1">
          
          {/* Button 1: افزودن موجودی (Wide Capsule) */}
          <motion.button
            whileTap={{ scale: 0.95 }}
            onClick={() => setActiveModal('topup')}
            className="flex-1 flex items-center justify-center gap-1.5 py-3 px-3 rounded-2xl bg-[#eff5ff] hover:bg-[#e4eeff] text-[#2563eb] transition-all cursor-pointer shadow-xs border border-blue-100/70"
          >
            <div className="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0">
              <Plus className="w-4 h-4" />
            </div>
            <span className="text-xs font-black whitespace-nowrap">افزودن موجودی</span>
          </motion.button>

          {/* Button 2: برداشت (Circle) */}
          <div className="flex flex-col items-center">
            <motion.button
              whileTap={{ scale: 0.92 }}
              onClick={() => setActiveModal('withdraw')}
              className="w-12 h-12 rounded-2xl bg-[#eff5ff] hover:bg-[#e4eeff] text-[#2563eb] flex items-center justify-center transition-all cursor-pointer shadow-xs border border-blue-100/70"
            >
              <ArrowUpRight className="w-5 h-5" />
            </motion.button>
            <span className="text-[11px] font-bold text-slate-700 mt-1.5">برداشت</span>
          </div>

          {/* Button 3: انتقال (Circle) */}
          <div className="flex flex-col items-center">
            <motion.button
              whileTap={{ scale: 0.92 }}
              onClick={() => setActiveModal('transfer')}
              className="w-12 h-12 rounded-2xl bg-[#eff5ff] hover:bg-[#e4eeff] text-[#2563eb] flex items-center justify-center transition-all cursor-pointer shadow-xs border border-blue-100/70"
            >
              <ArrowLeftRight className="w-5 h-5" />
            </motion.button>
            <span className="text-[11px] font-bold text-slate-700 mt-1.5">انتقال</span>
          </div>

          {/* Button 4: کد هدیه (Circle) */}
          <div className="flex flex-col items-center">
            <motion.button
              whileTap={{ scale: 0.92 }}
              onClick={() => setActiveModal('gift')}
              className="w-12 h-12 rounded-2xl bg-[#eff5ff] hover:bg-[#e4eeff] text-[#2563eb] flex items-center justify-center transition-all cursor-pointer shadow-xs border border-blue-100/70"
            >
              <Gift className="w-5 h-5" />
            </motion.button>
            <span className="text-[11px] font-bold text-slate-700 mt-1.5">کد هدیه</span>
          </div>

        </div>

        {/* 3. Transactions Section (Matching Screenshot 2) */}
        <div className="space-y-3 pt-4 border-t border-slate-100">
          <div className="flex items-center justify-between">
            <h3 className="font-extrabold text-sm text-slate-900">تراکنش‌ها</h3>
            <span className="text-[11px] text-slate-400 font-medium">
              {transactions.length} گردش حساب
            </span>
          </div>

          {/* If no transactions, show the exact empty state illustration */}
          {transactions.length === 0 ? (
            <div className="py-10 flex flex-col items-center justify-center text-center">
              {/* Empty State Graphic */}
              <div className="w-28 h-28 rounded-full bg-[#f1f4f9] flex items-center justify-center relative mb-3">
                <Sparkles className="w-3.5 h-3.5 text-slate-300 absolute top-4 left-5" />
                <Sparkles className="w-3.5 h-3.5 text-slate-300 absolute top-4 right-5" />
                
                {/* Document sheet with empty badge */}
                <div className="w-14 h-16 rounded-2xl bg-gradient-to-b from-slate-200 to-slate-300 flex flex-col items-center justify-center p-2 relative shadow-xs">
                  <div className="w-8 h-1.5 bg-white/70 rounded-full mb-1" />
                  <div className="w-6 h-1.5 bg-white/70 rounded-full mb-1" />
                  <div className="w-7 h-1.5 bg-white/70 rounded-full" />
                  
                  {/* Small circle X badge at bottom right */}
                  <div className="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-slate-100 border border-slate-300 flex items-center justify-center text-slate-400">
                    <X className="w-3 h-3" />
                  </div>
                </div>
              </div>
              <p className="text-xs font-bold text-slate-600">هنوز تراکنشی ثبت نشده است</p>
              <p className="text-[10px] text-slate-400 mt-0.5">پس از افزایش موجودی یا پرداخت خدمات، اینجا نمایش داده می‌شود.</p>
            </div>
          ) : (
            <div className="space-y-2">
              {transactions.map((tx) => (
                <div
                  key={tx.id}
                  className="p-3 sm:p-3.5 rounded-2xl bg-slate-50 hover:bg-slate-100/80 border border-slate-100 flex items-center justify-between transition-colors"
                >
                  <div className="flex items-center gap-2.5">
                    <div className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${
                      tx.amount > 0 
                        ? 'bg-emerald-100 text-emerald-700' 
                        : 'bg-blue-100 text-blue-700'
                    }`}>
                      {tx.amount > 0 ? (
                        <ArrowDownLeft className="w-4 h-4" />
                      ) : (
                        <ArrowUpRight className="w-4 h-4" />
                      )}
                    </div>
                    <div className="text-right">
                      <h4 className="text-xs font-bold text-slate-900">{tx.title}</h4>
                      <span className="text-[10px] text-slate-400 block mt-0.5">
                        {tx.date} • کد پیگیری: {tx.trackingId}
                      </span>
                    </div>
                  </div>

                  <div className="text-left">
                    <span className={`text-xs font-black block ${
                      tx.amount > 0 ? 'text-emerald-600' : 'text-slate-900'
                    }`}>
                      {tx.amount > 0 ? `+${(tx.amount * 10).toLocaleString('fa-IR')}` : `${(tx.amount * 10).toLocaleString('fa-IR')}`}
                    </span>
                    <span className="text-[9px] font-bold text-slate-400">ریال</span>
                  </div>
                </div>
              ))}
            </div>
          )}

        </div>

      </div>

      {/* ========================================================================= */}
      {/* ACTION MODALS: TopUp, Withdraw, Transfer, Gift Code                       */}
      {/* ========================================================================= */}

      {/* MODAL 1: افزودن موجودی (Deposit) */}
      <AnimatePresence>
        {activeModal === 'topup' && (
          <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <motion.div 
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="bg-white text-slate-900 w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100 relative"
            >
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <Plus className="w-4 h-4" />
                  </div>
                  <h3 className="text-sm font-extrabold text-slate-900">افزودن موجودی به کیف پول</h3>
                </div>
                <button 
                  onClick={() => setActiveModal(null)}
                  className="text-slate-400 hover:text-slate-600 p-1 rounded-full cursor-pointer"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {topUpSuccess ? (
                <div className="text-center py-6">
                  <div className="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 animate-bounce">
                    <CheckCircle2 className="w-8 h-8" />
                  </div>
                  <h4 className="font-extrabold text-slate-900 text-base">افزایش موجودی با موفقیت انجام شد</h4>
                  <p className="text-xs text-slate-500 mt-1">کیف پول شهروندی شما به صورت آنی شارژ شد.</p>
                </div>
              ) : (
                <div className="space-y-4 text-right">
                  <div>
                    <label className="text-xs font-bold text-slate-700 mb-2 block">
                      انتخاب مبالغ پیشنهادی:
                    </label>
                    <div className="grid grid-cols-2 gap-2">
                      {quickAmountsTomans.map((amt) => (
                        <button
                          key={amt}
                          onClick={() => {
                            setSelectedQuickTopUp(amt);
                            setCustomTopUpAmount('');
                          }}
                          className={`p-2.5 rounded-xl border text-xs font-bold transition-all cursor-pointer ${
                            selectedQuickTopUp === amt && !customTopUpAmount
                              ? 'bg-blue-50 border-blue-600 text-blue-700 ring-2 ring-blue-500/20'
                              : 'border-slate-200 text-slate-700 hover:bg-slate-50'
                          }`}
                        >
                          {(amt * 10).toLocaleString('fa-IR')} ریال
                        </button>
                      ))}
                    </div>
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-700 mb-1.5 block">
                      یا مبلغ دلخواه به ریال:
                    </label>
                    <input
                      type="number"
                      value={customTopUpAmount ? parseInt(customTopUpAmount) * 10 : ''}
                      onChange={(e) => {
                        const val = e.target.value;
                        setCustomTopUpAmount(val ? (parseInt(val) / 10).toString() : '');
                        setSelectedQuickTopUp(0);
                      }}
                      placeholder="مثلاً ۱,۰۰۰,۰۰۰ ریال"
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-500/10 outline-none font-bold text-right"
                    />
                  </div>

                  <div className="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs flex items-center justify-between text-slate-600">
                    <span>درگاه پرداخت شتابی:</span>
                    <span className="font-bold text-slate-900 flex items-center gap-1">
                      <Zap className="w-3.5 h-3.5 text-amber-500" />
                      شاپرک بدون کارمزد
                    </span>
                  </div>

                  <button
                    onClick={handleExecuteTopUp}
                    className="w-full bg-blue-600 hover:bg-blue-500 active:scale-98 text-white font-extrabold py-3 rounded-xl shadow-md shadow-blue-600/20 text-xs sm:text-sm transition-all cursor-pointer"
                  >
                    پرداخت و شارژ مستقیم
                  </button>
                </div>
              )}
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL 2: برداشت (Withdraw) */}
      <AnimatePresence>
        {activeModal === 'withdraw' && (
          <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <motion.div 
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="bg-white text-slate-900 w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100 relative"
            >
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <ArrowUpRight className="w-4 h-4" />
                  </div>
                  <h3 className="text-sm font-extrabold text-slate-900">برداشت از کیف پول به حساب بانکی</h3>
                </div>
                <button 
                  onClick={() => setActiveModal(null)}
                  className="text-slate-400 hover:text-slate-600 p-1 rounded-full cursor-pointer"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {withdrawSuccess ? (
                <div className="text-center py-6">
                  <div className="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 animate-bounce">
                    <CheckCircle2 className="w-8 h-8" />
                  </div>
                  <h4 className="font-extrabold text-slate-900 text-base">درخواست برداشت با موفقیت ثبت شد</h4>
                  <p className="text-xs text-slate-500 mt-1">مبلغ طی سیکل پایا به شماره شبا واریز خواهد شد.</p>
                </div>
              ) : (
                <div className="space-y-4 text-right">
                  <div>
                    <label className="text-xs font-bold text-slate-700 mb-1.5 block">
                      شماره شبا مقصد (IR):
                    </label>
                    <input
                      type="text"
                      value={withdrawIban}
                      onChange={(e) => setWithdrawIban(e.target.value)}
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-blue-600 outline-none font-mono text-left"
                    />
                  </div>

                  <div>
                    <div className="flex items-center justify-between mb-1.5">
                      <label className="text-xs font-bold text-slate-700">مبلغ برداشت (ریال):</label>
                      <span className="text-[10px] text-slate-500">
                        موجودی: {(profile.walletBalance * 10).toLocaleString('fa-IR')} ریال
                      </span>
                    </div>
                    <input
                      type="number"
                      value={withdrawAmount ? parseInt(withdrawAmount) * 10 : ''}
                      onChange={(e) => {
                        const val = e.target.value;
                        setWithdrawAmount(val ? (parseInt(val) / 10).toString() : '');
                      }}
                      placeholder="مثلاً ۵۰۰,۰۰۰ ریال"
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-blue-600 outline-none font-bold text-right"
                    />
                  </div>

                  {withdrawError && (
                    <div className="p-2.5 rounded-xl bg-red-50 text-red-700 border border-red-200 text-xs flex items-center gap-1.5">
                      <AlertCircle className="w-4 h-4 shrink-0" />
                      <span>{withdrawError}</span>
                    </div>
                  )}

                  <button
                    onClick={handleExecuteWithdraw}
                    className="w-full bg-slate-900 hover:bg-slate-800 active:scale-98 text-white font-extrabold py-3 rounded-xl shadow-md text-xs sm:text-sm transition-all cursor-pointer"
                  >
                    تایید و ثبت انتقال پایا
                  </button>
                </div>
              )}
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL 3: انتقال (Transfer to Citizen) */}
      <AnimatePresence>
        {activeModal === 'transfer' && (
          <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <motion.div 
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="bg-white text-slate-900 w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100 relative"
            >
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <ArrowLeftRight className="w-4 h-4" />
                  </div>
                  <h3 className="text-sm font-extrabold text-slate-900">انتقال اعتبار به شهروند دیگر</h3>
                </div>
                <button 
                  onClick={() => setActiveModal(null)}
                  className="text-slate-400 hover:text-slate-600 p-1 rounded-full cursor-pointer"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {transferSuccess ? (
                <div className="text-center py-6">
                  <div className="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 animate-bounce">
                    <CheckCircle2 className="w-8 h-8" />
                  </div>
                  <h4 className="font-extrabold text-slate-900 text-base">انتقال با موفقیت انجام شد</h4>
                  <p className="text-xs text-slate-500 mt-1">مبلغ به کیف پول مخاطب واریز گردید.</p>
                </div>
              ) : (
                <div className="space-y-4 text-right">
                  <div>
                    <label className="text-xs font-bold text-slate-700 mb-1.5 block">
                      شماره موبایل یا کدملی گیرنده:
                    </label>
                    <input
                      type="text"
                      value={transferRecipient}
                      onChange={(e) => setTransferRecipient(e.target.value)}
                      placeholder="۰۹۱۲..."
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-blue-600 outline-none font-bold text-right"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-700 mb-1.5 block">
                      مبلغ انتقال (ریال):
                    </label>
                    <input
                      type="number"
                      value={transferAmount ? parseInt(transferAmount) * 10 : ''}
                      onChange={(e) => {
                        const val = e.target.value;
                        setTransferAmount(val ? (parseInt(val) / 10).toString() : '');
                      }}
                      placeholder="مثلاً ۲۰۰,۰۰۰ ریال"
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-blue-600 outline-none font-bold text-right"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-bold text-slate-700 mb-1.5 block">
                      بابت / توضیحات (اختیاری):
                    </label>
                    <input
                      type="text"
                      value={transferNote}
                      onChange={(e) => setTransferNote(e.target.value)}
                      placeholder="هزینه استعلام وکالت..."
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:bg-white focus:border-blue-600 outline-none text-right"
                    />
                  </div>

                  {transferError && (
                    <div className="p-2.5 rounded-xl bg-red-50 text-red-700 border border-red-200 text-xs flex items-center gap-1.5">
                      <AlertCircle className="w-4 h-4 shrink-0" />
                      <span>{transferError}</span>
                    </div>
                  )}

                  <button
                    onClick={handleExecuteTransfer}
                    className="w-full bg-blue-600 hover:bg-blue-500 active:scale-98 text-white font-extrabold py-3 rounded-xl shadow-md text-xs sm:text-sm transition-all cursor-pointer"
                  >
                    تایید و انتقال آنی
                  </button>
                </div>
              )}
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL 4: کد هدیه (Gift Code) */}
      <AnimatePresence>
        {activeModal === 'gift' && (
          <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <motion.div 
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="bg-white text-slate-900 w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100 relative"
            >
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <Gift className="w-4 h-4" />
                  </div>
                  <h3 className="text-sm font-extrabold text-slate-900">ثبت و فعال‌سازی کد هدیه</h3>
                </div>
                <button 
                  onClick={() => {
                    setActiveModal(null);
                    setGiftResult(null);
                  }}
                  className="text-slate-400 hover:text-slate-600 p-1 rounded-full cursor-pointer"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <div className="space-y-4 text-right">
                <p className="text-xs text-slate-500 leading-relaxed">
                  اگر کارت هدیه یا کد تخفیف خدمات دولتی پیشخوان دارید، در کادر زیر وارد کنید تا به موجودی شما اضافه شود:
                </p>

                <div>
                  <label className="text-xs font-bold text-slate-700 mb-1.5 block">
                    کد هدیه:
                  </label>
                  <input
                    type="text"
                    value={giftCode}
                    onChange={(e) => setGiftCode(e.target.value)}
                    placeholder="مثلاً: GIFT50 یا PISHKHAN1403"
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-blue-600 outline-none font-mono uppercase text-center tracking-widest font-extrabold"
                  />
                </div>

                <div className="flex items-center gap-1.5 text-[11px] text-slate-400 bg-slate-50 p-2.5 rounded-xl">
                  <Sparkles className="w-3.5 h-3.5 text-amber-500 shrink-0" />
                  <span>کدهای تست: <code className="text-blue-600 font-mono font-bold">GIFT50</code> یا <code className="text-blue-600 font-mono font-bold">WELCOME100</code></span>
                </div>

                {giftResult && (
                  <div className={`p-3 rounded-2xl border text-xs font-bold flex items-center gap-2 ${
                    giftResult.success 
                      ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                      : 'bg-red-50 text-red-700 border-red-200'
                  }`}>
                    {giftResult.success ? (
                      <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                    ) : (
                      <AlertCircle className="w-4 h-4 text-red-600 shrink-0" />
                    )}
                    <span>{giftResult.message}</span>
                  </div>
                )}

                <button
                  onClick={handleExecuteGiftCode}
                  className="w-full bg-blue-600 hover:bg-blue-500 active:scale-98 text-white font-extrabold py-3 rounded-xl shadow-md text-xs sm:text-sm transition-all cursor-pointer"
                >
                  بررسی و اعمال کد هدیه
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
};
