import React, { useState } from 'react';
import { 
  X, 
  CheckCircle2, 
  Sparkles, 
  ShieldCheck, 
  Building2, 
  Store, 
  Factory, 
  Zap, 
  HelpCircle, 
  CreditCard, 
  PhoneCall, 
  FileText, 
  Clock 
} from 'lucide-react';
import { BusinessSubscriptionPlan } from '../types';

interface BusinessSubscriptionModalProps {
  plans: BusinessSubscriptionPlan[];
  userWalletBalance: number;
  onClose: () => void;
  onSubscribe: (plan: BusinessSubscriptionPlan) => void;
}

export const BusinessSubscriptionModal: React.FC<BusinessSubscriptionModalProps> = ({
  plans,
  userWalletBalance,
  onClose,
  onSubscribe
}) => {
  const [selectedPlanId, setSelectedPlanId] = useState<string>(plans[1]?.id || plans[0]?.id);
  const [isSuccess, setIsSuccess] = useState(false);

  const selectedPlan = plans.find(p => p.id === selectedPlanId) || plans[0];

  const handleConfirm = () => {
    onSubscribe(selectedPlan);
    setIsSuccess(true);
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 overflow-y-auto" dir="rtl">
      <div className="bg-white rounded-3xl w-full max-w-2xl shadow-2xl border border-slate-100 flex flex-col max-h-[92vh] overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-5 sm:p-6 flex items-center justify-between shrink-0">
          <div className="space-y-1">
            <div className="flex items-center gap-2">
              <span className="p-1.5 rounded-xl bg-amber-400/20 text-amber-300 border border-amber-400/30">
                <Sparkles className="w-4 h-4" />
              </span>
              <h2 className="font-black text-base sm:text-lg text-white">طرح‌های اشتراک ماهانه اصناف و شرکت‌ها</h2>
            </div>
            <p className="text-xs text-slate-300">
              مشاور مقیم مالیاتی، بیمه و حقوقی اختصاصی کسب‌وکار شما با سهمیه ماهانه تنظیم لوایح
            </p>
          </div>

          <button 
            onClick={onClose}
            className="p-2 rounded-2xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white transition cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        {!isSuccess ? (
          <div className="flex-1 p-5 overflow-y-auto space-y-5 bg-slate-50">
            
            {/* Plans Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              {plans.map((plan) => {
                const isSelected = plan.id === selectedPlanId;
                return (
                  <div
                    key={plan.id}
                    onClick={() => setSelectedPlanId(plan.id)}
                    className={`relative rounded-2xl p-4 border transition cursor-pointer flex flex-col justify-between ${
                      isSelected
                        ? 'bg-white border-indigo-600 ring-2 ring-indigo-600/30 shadow-md'
                        : 'bg-white/70 border-slate-200 hover:border-slate-300'
                    }`}
                  >
                    {plan.badge && (
                      <span className={`absolute -top-2.5 right-3 text-[10px] font-black px-2 py-0.5 rounded-full shadow-xs ${
                        plan.isPopular 
                          ? 'bg-amber-500 text-white' 
                          : 'bg-slate-800 text-slate-200'
                      }`}>
                        {plan.badge}
                      </span>
                    )}

                    <div className="space-y-2">
                      <div className="flex items-center justify-between">
                        <div className="p-2 rounded-xl bg-slate-100 text-indigo-700">
                          {plan.id === 'plan-guild-basic' && <Store className="w-5 h-5" />}
                          {plan.id === 'plan-business-pro' && <Building2 className="w-5 h-5" />}
                          {plan.id === 'plan-enterprise' && <Factory className="w-5 h-5" />}
                        </div>
                        <input 
                          type="radio" 
                          name="plan" 
                          checked={isSelected} 
                          onChange={() => setSelectedPlanId(plan.id)}
                          className="w-4 h-4 text-indigo-600 cursor-pointer"
                        />
                      </div>

                      <div>
                        <h3 className="font-black text-xs text-slate-900 leading-snug">{plan.title}</h3>
                        <p className="text-[10px] text-slate-500 mt-1 leading-relaxed">{plan.targetAudience}</p>
                      </div>

                      <div className="pt-2 border-t border-slate-100">
                        <span className="text-[10px] text-slate-400 block">حق اشتراک:</span>
                        <div className="flex items-baseline gap-1">
                          <span className="font-black text-sm text-indigo-950 font-mono">
                            {plan.priceMonthly.toLocaleString('fa-IR')}
                          </span>
                          <span className="text-[10px] text-slate-500 font-bold">تومان / ماهانه</span>
                        </div>
                      </div>
                    </div>

                    <div className="mt-3 pt-2 border-t border-dashed border-slate-200 space-y-1 text-[10px] text-slate-600">
                      <div className="flex items-center gap-1 font-bold text-slate-800">
                        <Clock className="w-3 h-3 text-indigo-600" />
                        <span>{plan.quota.phoneMinutes} دقیقه مکالمه مستقیم</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <FileText className="w-3 h-3 text-emerald-600" />
                        <span>{plan.quota.laborDisputeDefense}</span>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Selected Plan Details & Feature Breakdown */}
            <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-3">
              <h3 className="font-black text-xs text-slate-900 flex items-center justify-between">
                <span>امکانات و سهمیه‌های طرح انتخابی: {selectedPlan.title}</span>
                <span className="text-[10px] text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md font-black">
                  فعال‌سازی آنی
                </span>
              </h3>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {selectedPlan.features.map((feature, idx) => (
                  <div key={idx} className="flex items-start gap-2 text-[11px] text-slate-700 leading-relaxed">
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                    <span>{feature}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Trust & Guarantee banner */}
            <div className="bg-amber-50 border border-amber-200/80 rounded-2xl p-3.5 flex items-center gap-3 text-xs text-amber-950">
              <ShieldCheck className="w-5 h-5 text-amber-600 shrink-0" />
              <p className="text-[11px] leading-relaxed">
                با خرید این اشتراک، گزارشات فصلی و لوایح تنظیمی شما تحت نظارت مستقیم کارشناسان تاییدشده کانون وکلا و جامعه حسابداران رسمی انجام می‌گیرد.
              </p>
            </div>

          </div>
        ) : (
          /* Success Screen */
          <div className="p-8 text-center space-y-4 my-auto">
            <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto ring-8 ring-emerald-50">
              <CheckCircle2 className="w-9 h-9" />
            </div>

            <h3 className="font-black text-base text-slate-900">اشتراک {selectedPlan.title} با موفقیت فعال شد!</h3>
            <p className="text-xs text-slate-600 leading-relaxed max-w-md mx-auto">
              سهمیه {selectedPlan.quota.phoneMinutes} دقیقه مشاوره تلفنی و سهمیه تنظیم لوایح ماهانه به حساب کاربری شما اضافه شد. هم‌اکنون می‌توانید از خدمات مشاوران مقیم استفاده کنید.
            </p>

            <button
              onClick={onClose}
              className="px-6 py-3 rounded-2xl bg-indigo-900 text-white font-black text-xs hover:bg-indigo-800 transition cursor-pointer shadow-sm"
            >
              شروع استفاده از خدمات اشتراک
            </button>
          </div>
        )}

        {/* Footer Bar */}
        {!isSuccess && (
          <div className="p-4 bg-white border-t border-slate-200 flex items-center justify-between shrink-0">
            <div>
              <span className="text-[10px] text-slate-400 block">مبلغ حق اشتراک ماهانه:</span>
              <span className="font-black text-indigo-950 font-mono text-sm">
                {selectedPlan.priceMonthly.toLocaleString('fa-IR')} تومان
              </span>
            </div>

            <button
              onClick={handleConfirm}
              className="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs px-6 py-3 rounded-2xl transition cursor-pointer flex items-center gap-2 shadow-lg shadow-indigo-600/25"
            >
              <CreditCard className="w-4 h-4" />
              <span>پرداخت و فعال‌سازی اشتراک</span>
            </button>
          </div>
        )}

      </div>
    </div>
  );
};
