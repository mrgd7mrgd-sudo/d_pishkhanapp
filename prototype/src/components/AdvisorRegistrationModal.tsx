import React, { useState } from 'react';
import { 
  X, 
  UserCheck, 
  Award, 
  FileText, 
  Upload, 
  CheckCircle2, 
  Sparkles, 
  ShieldCheck, 
  DollarSign, 
  Briefcase, 
  Scale, 
  Building2, 
  Check, 
  HelpCircle 
} from 'lucide-react';
import { ConsultationCategory, ConsultationAdvisor } from '../types';

interface AdvisorRegistrationModalProps {
  onClose: () => void;
  onRegisterAdvisor: (newAdvisor: ConsultationAdvisor) => void;
}

export const AdvisorRegistrationModal: React.FC<AdvisorRegistrationModalProps> = ({
  onClose,
  onRegisterAdvisor
}) => {
  const [step, setStep] = useState<'form' | 'success'>('form');

  const [formData, setFormData] = useState({
    fullName: '',
    mobile: '',
    nationalId: '',
    category: 'tax' as ConsultationCategory,
    title: '',
    experienceYears: 10,
    licenseType: 'پروانه وکالت پایه یک دادگستری',
    licenseNumber: '',
    bio: '',
    ratePhonePerMin: 18000,
    rateTextChat: 95000,
    rateDeepReview: 450000,
    specialtyTags: 'سامانه مودیان، لایحه ماده ۲۳۸، مالیات مشاغل',
    uploadedDocName: ''
  });

  const [termsAccepted, setTermsAccepted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.fullName || !formData.mobile || !termsAccepted) return;

    const createdAdvisor: ConsultationAdvisor = {
      id: `adv-reg-${Date.now()}`,
      name: formData.fullName,
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=240&auto=format&fit=crop&q=80',
      title: formData.title || 'کارشناس رسمی و مشاور تخصصی اداری',
      category: formData.category,
      categoryTitle: 
        formData.category === 'tax' ? 'امور مالیاتی و مودیان' :
        formData.category === 'insurance_labor' ? 'بیمه، بازنشستگی و اداره کار' :
        formData.category === 'legal_registry' ? 'حقوقی، ثبتی و اسناد' :
        formData.category === 'tenders_permits' ? 'مناقصات و درگاه ملی مجوزها' :
        'شهرداری و کمیسیون ماده ۱۰۰',
      credentialsBadge: formData.licenseType,
      licenseNumber: formData.licenseNumber || 'PISHKHAN-VERIFIED',
      experienceYears: formData.experienceYears,
      rating: 5.0,
      reviewCount: 1,
      ratingBreakdown: {
        accuracy: 5.0,
        eloquence: 5.0,
        patience: 5.0
      },
      specialties: formData.specialtyTags.split('،').map(s => s.trim()),
      isOnline: true,
      isVerified: true,
      bio: formData.bio || 'مشاور تایید شده سامانه پیشخوان با سابقه درخشان در احقاق حقوق شهروندان و فعالان اقتصادی.',
      consultationCount: 0,
      pricing: {
        textChat: formData.rateTextChat,
        phonePerMinute: formData.ratePhonePerMin,
        caseDeepReview: formData.rateDeepReview
      },
      linkedActionServices: [
        {
          serviceId: 'srv-net-tax-portal',
          title: 'پیگیری پرونده در باجه پیشخوان',
          description: 'ارجاع مستقیم متقاضی به دفاتر رسمی'
        }
      ]
    };

    onRegisterAdvisor(createdAdvisor);
    setStep('success');
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 overflow-y-auto" dir="rtl">
      <div className="bg-white rounded-3xl w-full max-w-xl shadow-2xl border border-slate-100 flex flex-col max-h-[92vh] overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-5 flex items-center justify-between shrink-0">
          <div className="flex items-center gap-3">
            <div className="p-2.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-2xl">
              <UserCheck className="w-6 h-6" />
            </div>
            <div>
              <h2 className="font-black text-sm sm:text-base text-white">پورتال جذب و ثبت‌نام مشاوران متخصص</h2>
              <p className="text-[11px] text-slate-300">پیوستن به شبکه رسمی مشاوران دولتی و حقوقی پیشخوان</p>
            </div>
          </div>

          <button 
            onClick={onClose}
            className="p-2 rounded-2xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white transition cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        {step === 'form' ? (
          <form onSubmit={handleSubmit} className="flex-1 p-5 overflow-y-auto space-y-4 bg-slate-50">
            
            {/* Value Proposition */}
            <div className="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-xs text-emerald-950 space-y-1.5">
              <div className="flex items-center gap-2 font-black">
                <Sparkles className="w-4 h-4 text-emerald-600" />
                <span>مزایای همکاری به عنوان مشاور در پیشخوان:</span>
              </div>
              <ul className="space-y-1 text-[11px] text-emerald-900 list-disc list-inside">
                <li>تسویه حساب روزانه و منظم درآمد مشاوره‌ها با کارمزد منصفانه پلتفرم (۱۵ الی ۲۵ درصد)</li>
                <li>قابلیت اتصال مشاوره به خدمات اجرایی و ارجاع پرونده به بیش از ۱۶ هزار دفتر پیشخوان سراسر کشور</li>
                <li>امنیت کامل شماره تماس، ارتباط صوتی و تصویری اینترنتی امن درون‌برنامه‌ای</li>
              </ul>
            </div>

            {/* Personal Info */}
            <div className="bg-white rounded-2xl p-4 border border-slate-200/80 space-y-3 shadow-xs">
              <h3 className="font-black text-xs text-slate-800 flex items-center gap-1.5">
                <Briefcase className="w-4 h-4 text-slate-500" />
                <span>مشخصات فردی و هویتی</span>
              </h3>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-[11px] font-bold text-slate-700 mb-1">نام و نام خانوادگی:</label>
                  <input
                    type="text"
                    required
                    value={formData.fullName}
                    onChange={e => setFormData({ ...formData, fullName: e.target.value })}
                    placeholder="مثال: دکتر علیرضا نعمتی"
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-bold text-slate-700 mb-1">شماره همراه (جهت احراز هویت):</label>
                  <input
                    type="tel"
                    required
                    value={formData.mobile}
                    onChange={e => setFormData({ ...formData, mobile: e.target.value })}
                    placeholder="۰۹۱۲..."
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none text-left"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-[11px] font-bold text-slate-700 mb-1">حوزه تخصصی اصلی:</label>
                  <select
                    value={formData.category}
                    onChange={e => setFormData({ ...formData, category: e.target.value as ConsultationCategory })}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                  >
                    <option value="tax">امور مالیاتی و مودیان</option>
                    <option value="insurance_labor">بیمه، بازنشستگی و اداره کار</option>
                    <option value="legal_registry">حقوقی، ثبتی و اسناد</option>
                    <option value="tenders_permits">مناقصات و درگاه ملی مجوزها</option>
                    <option value="municipal">شهرداری و کمیسیون ماده ۱۰۰</option>
                    <option value="business_startup">شرکت‌ها و استارتاپ‌ها</option>
                  </select>
                </div>
                <div>
                  <label className="block text-[11px] font-bold text-slate-700 mb-1">سابقه کار تخصصی (سال):</label>
                  <input
                    type="number"
                    value={formData.experienceYears}
                    onChange={e => setFormData({ ...formData, experienceYears: Number(e.target.value) })}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[11px] font-bold text-slate-700 mb-1">عنوان تخصصی و حرفه‌ای:</label>
                <input
                  type="text"
                  value={formData.title}
                  onChange={e => setFormData({ ...formData, title: e.target.value })}
                  placeholder="مثال: کارشناس ارشد ممیزی مالیاتی و مستشار دادرسی"
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                />
              </div>
            </div>

            {/* Credential & Licensing Verification */}
            <div className="bg-white rounded-2xl p-4 border border-slate-200/80 space-y-3 shadow-xs">
              <h3 className="font-black text-xs text-slate-800 flex items-center gap-1.5">
                <Award className="w-4 h-4 text-emerald-600" />
                <span>احراز تخصص و مدارک رسمی</span>
              </h3>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-[11px] font-bold text-slate-700 mb-1">نوع پروانه یا مدرک صلاحیت:</label>
                  <select
                    value={formData.licenseType}
                    onChange={e => setFormData({ ...formData, licenseType: e.target.value })}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                  >
                    <option value="پروانه وکالت پایه یک دادگستری">پروانه وکالت پایه یک دادگستری</option>
                    <option value="عضو جامعه حسابداران رسمی ایران">عضو جامعه حسابداران رسمی ایران</option>
                    <option value="عضو جامعه مشاوران رسمی مالیاتی">عضو جامعه مشاوران رسمی مالیاتی</option>
                    <option value="حکم بازنشستگی کارشناسی تامین اجتماعی">حکم بازنشستگی کارشناسی تامین اجتماعی</option>
                    <option value="کارشناس رسمی دادگستری (ماده ۱۰۰ و ثبت)">کارشناس رسمی دادگستری (ماده ۱۰۰ و ثبت)</option>
                    <option value="مستشار سابق هیئت‌های حل اختلاف اداره کار">مستشار سابق هیئت‌های حل اختلاف اداره کار</option>
                  </select>
                </div>
                <div>
                  <label className="block text-[11px] font-bold text-slate-700 mb-1">شماره پروانه / حکم نظام:</label>
                  <input
                    type="text"
                    value={formData.licenseNumber}
                    onChange={e => setFormData({ ...formData, licenseNumber: e.target.value })}
                    placeholder="مثال: IACPA-88410"
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none text-left"
                  />
                </div>
              </div>

              {/* Upload Certificate File */}
              <div>
                <label className="block text-[11px] font-bold text-slate-700 mb-1">تصویر پروانه وکالت / کارت حسابدار رسمی / حکم بازنشستگی:</label>
                <label className="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl p-3 flex flex-col items-center justify-center text-slate-500 hover:text-emerald-700 cursor-pointer transition bg-slate-50">
                  <Upload className="w-5 h-5 mb-1 text-slate-400" />
                  <span className="text-xs font-bold">
                    {formData.uploadedDocName || 'بارگذاری تصویر پروانه یا گواهی صلاحیت'}
                  </span>
                  <input 
                    type="file" 
                    className="hidden" 
                    onChange={e => {
                      if (e.target.files?.[0]) {
                        setFormData({ ...formData, uploadedDocName: e.target.files[0].name });
                      }
                    }} 
                  />
                </label>
              </div>
            </div>

            {/* Pricing Config */}
            <div className="bg-white rounded-2xl p-4 border border-slate-200/80 space-y-3 shadow-xs">
              <h3 className="font-black text-xs text-slate-800 flex items-center gap-1.5">
                <DollarSign className="w-4 h-4 text-amber-600" />
                <span>تعیین تعرفه و نرخ خدمات مشاوره</span>
              </h3>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                <div>
                  <label className="block text-[10px] font-bold text-slate-700 mb-1">تماس صوتی (تومان/دقیقه):</label>
                  <input
                    type="number"
                    step={1000}
                    value={formData.ratePhonePerMin}
                    onChange={e => setFormData({ ...formData, ratePhonePerMin: Number(e.target.value) })}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs font-mono text-slate-800"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-slate-700 mb-1">مشاوره متنی (تومان):</label>
                  <input
                    type="number"
                    step={5000}
                    value={formData.rateTextChat}
                    onChange={e => setFormData({ ...formData, rateTextChat: Number(e.target.value) })}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs font-mono text-slate-800"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-slate-700 mb-1">تنظیم لایحه / عمیق (تومان):</label>
                  <input
                    type="number"
                    step={10000}
                    value={formData.rateDeepReview}
                    onChange={e => setFormData({ ...formData, rateDeepReview: Number(e.target.value) })}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs font-mono text-slate-800"
                  />
                </div>
              </div>
            </div>

            {/* Terms checkbox */}
            <div className="p-3 bg-white rounded-2xl border border-slate-200 flex items-start gap-2.5">
              <input
                type="checkbox"
                id="terms"
                checked={termsAccepted}
                onChange={e => setTermsAccepted(e.target.checked)}
                className="mt-0.5 w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer"
              />
              <label htmlFor="terms" className="text-[11px] text-slate-600 leading-relaxed cursor-pointer">
                اینجانب صحت کلیه مدارک ارائه‌شده و تعهد به رازداری اطلاعات مراجعین و پاسخگویی منطبق بر قوانین جاری کشور را می‌پذیرم.
              </label>
            </div>

            {/* Submit Button */}
            <div className="pt-2">
              <button
                type="submit"
                disabled={!termsAccepted || !formData.fullName}
                className={`w-full py-3.5 rounded-2xl font-black text-xs flex items-center justify-center gap-2 transition cursor-pointer shadow-lg ${
                  termsAccepted && formData.fullName
                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/25'
                    : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                }`}
              >
                <Check className="w-4 h-4" />
                <span>ثبت پروفایل و فعال‌سازی آنلاین در سامانه مشاوران</span>
              </button>
            </div>

          </form>
        ) : (
          /* Success Screen */
          <div className="p-6 text-center space-y-4 my-auto">
            <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto ring-8 ring-emerald-50">
              <CheckCircle2 className="w-9 h-9" />
            </div>

            <h3 className="font-black text-base text-slate-900">پروفایل کارشناسی شما با موفقیت ثبت و فعال شد!</h3>
            <p className="text-xs text-slate-600 leading-relaxed max-w-md mx-auto">
              مدارک پروانه و صلاحیت حرفه‌ای شما تایید گردید. هم‌اکنون پروفایل شما در لیست مشاوران آنلاین قرار گرفت و شهروندان می‌توانند جهت مشاوره صوتی، متنی و تنظیم لایحه با شما ارتباط برقرار کنند.
            </p>

            <button
              onClick={onClose}
              className="px-6 py-3 rounded-2xl bg-slate-900 text-white font-black text-xs hover:bg-slate-800 transition cursor-pointer shadow-sm"
            >
              مشاهده در لیست مشاوران
            </button>
          </div>
        )}

      </div>
    </div>
  );
};
