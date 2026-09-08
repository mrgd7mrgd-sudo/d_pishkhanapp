import React, { useState } from 'react';
import { 
  X, 
  Star, 
  ShieldCheck, 
  MessageSquare, 
  Phone, 
  FileText, 
  Clock, 
  Award, 
  Sparkles, 
  CheckCircle2, 
  ArrowLeft, 
  Building2, 
  HelpCircle,
  ThumbsUp,
  Briefcase,
  AlertCircle
} from 'lucide-react';
import { ConsultationAdvisor, ConsultationMode } from '../types';

interface ConsultationDetailModalProps {
  advisor: ConsultationAdvisor;
  onClose: () => void;
  onStartSession: (advisor: ConsultationAdvisor, mode: ConsultationMode) => void;
  onExecuteLinkedService?: (serviceId: string) => void;
}

export const ConsultationDetailModal: React.FC<ConsultationDetailModalProps> = ({
  advisor,
  onClose,
  onStartSession,
  onExecuteLinkedService
}) => {
  const [selectedMode, setSelectedMode] = useState<ConsultationMode>('call');

  return (
    <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 overflow-y-auto" dir="rtl">
      <div className="bg-white rounded-3xl w-full max-w-xl shadow-2xl border border-slate-100 flex flex-col max-h-[92vh] overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
        
        {/* Header with Advisor Card */}
        <div className="relative bg-gradient-to-br from-slate-900 via-slate-850 to-slate-900 text-white p-5 sm:p-6 shrink-0">
          <button 
            onClick={onClose}
            className="absolute top-4 left-4 p-2 rounded-2xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white transition cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>

          <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4">
            <div className="relative">
              <img 
                src={advisor.avatar} 
                alt={advisor.name} 
                className="w-20 h-20 rounded-2xl object-cover ring-3 ring-emerald-500/50 shadow-xl"
              />
              {advisor.isOnline && (
                <span className="absolute -bottom-1 -right-1 flex items-center gap-1 bg-emerald-500 text-slate-950 font-black text-[9px] px-2 py-0.5 rounded-full border-2 border-slate-900 shadow-md">
                  <span className="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
                  آماده گفتگو
                </span>
              )}
            </div>

            <div className="flex-1 text-center sm:text-right space-y-1.5">
              <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                <h2 className="text-base sm:text-lg font-black text-white">{advisor.name}</h2>
                <span className="inline-flex items-center gap-1 bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[10px] font-bold px-2 py-0.5 rounded-md">
                  <ShieldCheck className="w-3.5 h-3.5" />
                  {advisor.credentialsBadge}
                </span>
              </div>

              <p className="text-xs text-slate-300 font-medium">{advisor.title}</p>

              {/* Stats & Badge row */}
              <div className="flex flex-wrap items-center justify-center sm:justify-start gap-3 pt-1 text-[11px] text-slate-300">
                <div className="flex items-center gap-1 bg-amber-400/15 text-amber-300 px-2 py-0.5 rounded-md font-black">
                  <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                  <span>{advisor.rating}</span>
                  <span className="text-[9px] text-amber-200/80">({advisor.reviewCount} نظر)</span>
                </div>
                <div className="flex items-center gap-1 bg-white/10 px-2 py-0.5 rounded-md">
                  <Briefcase className="w-3.5 h-3.5 text-slate-400" />
                  <span>{advisor.experienceYears} سال سابقه تخصصی</span>
                </div>
                <div className="flex items-center gap-1 bg-white/10 px-2 py-0.5 rounded-md">
                  <ThumbsUp className="w-3.5 h-3.5 text-emerald-400" />
                  <span>{advisor.consultationCount} مشاوره موفق</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Scrollable Content */}
        <div className="flex-1 p-4 sm:p-5 overflow-y-auto space-y-5 bg-slate-50">
          
          {/* Multidimensional Evaluation Card (سیستم رضایت‌سنجی چندبعدی) */}
          <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-3">
            <h3 className="font-black text-xs text-slate-800 flex items-center justify-between">
              <span>شاخص‌های رضایت‌سنجی تخصصی</span>
              <span className="text-[10px] bg-emerald-50 text-emerald-800 font-bold px-2 py-0.5 rounded-md border border-emerald-200">
                احراز صلاحیت و مدارک رسمی
              </span>
            </h3>

            <div className="grid grid-cols-3 gap-2">
              <div className="bg-slate-50 rounded-xl p-2.5 text-center border border-slate-100">
                <span className="text-[10px] text-slate-400 block font-medium">دقت راهکار قانونی</span>
                <span className="font-black text-sm text-emerald-700 font-mono mt-0.5 block">
                  {advisor.ratingBreakdown.accuracy} / ۵.۰
                </span>
              </div>
              <div className="bg-slate-50 rounded-xl p-2.5 text-center border border-slate-100">
                <span className="text-[10px] text-slate-400 block font-medium">فن بیان و شفافیت</span>
                <span className="font-black text-sm text-emerald-700 font-mono mt-0.5 block">
                  {advisor.ratingBreakdown.eloquence} / ۵.۰
                </span>
              </div>
              <div className="bg-slate-50 rounded-xl p-2.5 text-center border border-slate-100">
                <span className="text-[10px] text-slate-400 block font-medium">صبوری و پاسخگویی</span>
                <span className="font-black text-sm text-emerald-700 font-mono mt-0.5 block">
                  {advisor.ratingBreakdown.patience} / ۵.۰
                </span>
              </div>
            </div>

            {advisor.licenseNumber && (
              <p className="text-[10px] text-slate-400 flex items-center gap-1.5 pt-1">
                <Award className="w-3.5 h-3.5 text-slate-500" />
                <span>شماره پروانه و نظام حرفه‌ای: <strong className="text-slate-700 font-mono">{advisor.licenseNumber}</strong></span>
              </p>
            )}
          </div>

          {/* Specialties / Domains */}
          <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-2">
            <h3 className="font-black text-xs text-slate-800">حوزه‌های اشراف و مشاوره:</h3>
            <div className="flex flex-wrap gap-1.5">
              {advisor.specialties.map((spec, idx) => (
                <span key={idx} className="text-[11px] bg-slate-100 text-slate-700 font-bold px-2.5 py-1 rounded-xl border border-slate-200">
                  {spec}
                </span>
              ))}
            </div>
            <p className="text-xs text-slate-600 leading-relaxed pt-2 border-t border-slate-100">
              {advisor.bio}
            </p>
          </div>

          {/* Consultation Modes Selector (انتخاب نحوه مشاوره) */}
          <div className="space-y-2.5">
            <h3 className="font-black text-xs text-slate-900 flex items-center justify-between">
              <span>شیوه مشاوره مورد نظر را انتخاب کنید:</span>
              <span className="text-[10px] text-emerald-600 font-bold">پاسخ در لحظه</span>
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
              
              {/* Option 1: Call */}
              <button
                onClick={() => setSelectedMode('call')}
                className={`p-3.5 rounded-2xl text-right transition cursor-pointer border flex flex-col justify-between ${
                  selectedMode === 'call'
                    ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/20 text-emerald-950'
                    : 'bg-white border-slate-200 hover:border-slate-300 text-slate-800'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <div className={`p-2 rounded-xl ${selectedMode === 'call' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                    <Phone className="w-4 h-4" />
                  </div>
                  {selectedMode === 'call' && <CheckCircle2 className="w-4 h-4 text-emerald-600" />}
                </div>
                <div>
                  <h4 className="font-black text-xs">تماس صوتی امن</h4>
                  <p className="text-[10px] text-slate-500 mt-0.5">گفتگوی مستقیم با مشاور</p>
                  <span className="block mt-2 font-black text-xs text-emerald-700 font-mono">
                    {advisor.pricing.phonePerMinute.toLocaleString('fa-IR')} ت / دقیقه
                  </span>
                </div>
              </button>

              {/* Option 2: Text Chat */}
              <button
                onClick={() => setSelectedMode('text')}
                className={`p-3.5 rounded-2xl text-right transition cursor-pointer border flex flex-col justify-between ${
                  selectedMode === 'text'
                    ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/20 text-emerald-950'
                    : 'bg-white border-slate-200 hover:border-slate-300 text-slate-800'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <div className={`p-2 rounded-xl ${selectedMode === 'text' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                    <MessageSquare className="w-4 h-4" />
                  </div>
                  {selectedMode === 'text' && <CheckCircle2 className="w-4 h-4 text-emerald-600" />}
                </div>
                <div>
                  <h4 className="font-black text-xs">مشاوره متنی فوری</h4>
                  <p className="text-[10px] text-slate-500 mt-0.5">بارگذاری مدارک و اسناد</p>
                  <span className="block mt-2 font-black text-xs text-emerald-700 font-mono">
                    {advisor.pricing.textChat.toLocaleString('fa-IR')} تومان
                  </span>
                </div>
              </button>

              {/* Option 3: Deep Review */}
              <button
                onClick={() => setSelectedMode('case_review')}
                className={`p-3.5 rounded-2xl text-right transition cursor-pointer border flex flex-col justify-between ${
                  selectedMode === 'case_review'
                    ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/20 text-emerald-950'
                    : 'bg-white border-slate-200 hover:border-slate-300 text-slate-800'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <div className={`p-2 rounded-xl ${selectedMode === 'case_review' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                    <FileText className="w-4 h-4" />
                  </div>
                  {selectedMode === 'case_review' && <CheckCircle2 className="w-4 h-4 text-emerald-600" />}
                </div>
                <div>
                  <h4 className="font-black text-xs">تنظیم لایحه و بررسی</h4>
                  <p className="text-[10px] text-slate-500 mt-0.5">تحلیل کتبی و لایحه رسمی</p>
                  <span className="block mt-2 font-black text-xs text-emerald-700 font-mono">
                    {advisor.pricing.caseDeepReview.toLocaleString('fa-IR')} تومان
                  </span>
                </div>
              </button>

            </div>
          </div>

          {/* Action-Connected Services (مشاوره متصل به اقدام) */}
          {advisor.linkedActionServices.length > 0 && (
            <div className="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl p-4 border border-emerald-200 space-y-2">
              <div className="flex items-center gap-2 text-emerald-950 font-black text-xs">
                <Building2 className="w-4 h-4 text-emerald-700" />
                <span>مزیت «مشاوره متصل به اقدام»:</span>
              </div>
              <p className="text-[11px] text-emerald-900 leading-relaxed">
                پس از گفت‌وگو با مشاور، نیازی به مراجعات مکرر اداری ندارید؛ پرونده شما مستقیماً به باجه دفاتر پیشخوان متصل و پیگیری می‌شود:
              </p>
              <div className="space-y-1.5 pt-1">
                {advisor.linkedActionServices.map((srv, idx) => (
                  <div key={idx} className="flex items-center justify-between bg-white/80 border border-emerald-300/80 rounded-xl p-2.5 text-xs">
                    <div className="font-bold text-slate-800">
                      <span>{srv.title}</span>
                    </div>
                    {onExecuteLinkedService && (
                      <button
                        onClick={() => {
                          onExecuteLinkedService(srv.serviceId);
                          onClose();
                        }}
                        className="text-[11px] text-emerald-700 hover:text-emerald-900 font-black flex items-center gap-1 cursor-pointer"
                      >
                        <span>اقدام در پیشخوان</span>
                        <ArrowLeft className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Recent Reviews */}
          {advisor.recentReviews && advisor.recentReviews.length > 0 && (
            <div className="space-y-2.5">
              <h3 className="font-black text-xs text-slate-900">تجربه مراجعین قبلی:</h3>
              <div className="space-y-2">
                {advisor.recentReviews.map(rev => (
                  <div key={rev.id} className="bg-white rounded-xl p-3 border border-slate-200/70 space-y-1">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-bold text-slate-800">{rev.userName}</span>
                      <div className="flex items-center gap-1 text-amber-500 font-bold text-[11px]">
                        <Star className="w-3.5 h-3.5 fill-amber-400" />
                        <span>{rev.rating}</span>
                      </div>
                    </div>
                    <p className="text-[11px] text-slate-600 leading-relaxed">{rev.comment}</p>
                    <div className="flex items-center justify-between text-[10px] text-slate-400 pt-1">
                      <span>نوع: {rev.consultationType}</span>
                      <span>{rev.date}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Disclaimer (سلب مسئولیت شفاف) */}
          <div className="bg-slate-100 rounded-xl p-3 text-[10px] text-slate-500 leading-relaxed flex items-start gap-2">
            <AlertCircle className="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
            <p>
              نکته حقوقی: نظرات مشاوران مبتنی بر آخرین قوانین و مقررات مصوب است و سامانه پیشخوان نقش تسهیل‌گر و احراز هویت کارشناسان را بر عهده دارد. کلیه اسناد هویتی و مالی پس از خاتمه مشاوره طبق پروتکل‌های امنیتی محافظت می‌گردند.
            </p>
          </div>

        </div>

        {/* Footer Action Button */}
        <div className="p-4 bg-white border-t border-slate-200 flex items-center justify-between shrink-0">
          <div>
            <span className="text-[10px] text-slate-400 block">هزینه مشاوره ({selectedMode === 'call' ? 'دقیقه‌ای' : 'پروژه‌ای'}):</span>
            <span className="font-black text-emerald-800 text-sm font-mono">
              {selectedMode === 'call' 
                ? `${advisor.pricing.phonePerMinute.toLocaleString('fa-IR')} ت/دقیقه`
                : selectedMode === 'case_review' 
                ? `${advisor.pricing.caseDeepReview.toLocaleString('fa-IR')} تومان`
                : `${advisor.pricing.textChat.toLocaleString('fa-IR')} تومان`}
            </span>
          </div>

          <button
            onClick={() => onStartSession(advisor, selectedMode)}
            className="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-6 py-3 rounded-2xl transition cursor-pointer flex items-center gap-2 shadow-lg shadow-emerald-600/25"
          >
            {selectedMode === 'call' && <Phone className="w-4 h-4" />}
            {selectedMode === 'text' && <MessageSquare className="w-4 h-4" />}
            {selectedMode === 'case_review' && <FileText className="w-4 h-4" />}
            <span>شروع مشاوره با {advisor.name}</span>
          </button>
        </div>

      </div>
    </div>
  );
};
