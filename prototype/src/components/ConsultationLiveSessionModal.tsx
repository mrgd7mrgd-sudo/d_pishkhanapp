import React, { useState } from 'react';
import { 
  X, 
  Phone, 
  PhoneCall, 
  PhoneOff, 
  Mic, 
  MicOff, 
  Volume2, 
  ShieldCheck, 
  Upload, 
  FileText, 
  Send, 
  Sparkles, 
  Clock, 
  ArrowLeft, 
  CheckCircle2, 
  FileCheck2, 
  Building2,
  FileCheck,
  AlertTriangle,
  Paperclip,
  Check
} from 'lucide-react';
import { ConsultationAdvisor, ConsultationMode, ConsultationSession, CitizenService } from '../types';

interface ConsultationLiveSessionModalProps {
  advisor: ConsultationAdvisor;
  mode: ConsultationMode;
  onClose: () => void;
  onSessionComplete: (session: ConsultationSession) => void;
  onExecuteLinkedService?: (serviceId: string) => void;
}

export const ConsultationLiveSessionModal: React.FC<ConsultationLiveSessionModalProps> = ({
  advisor,
  mode,
  onClose,
  onSessionComplete,
  onExecuteLinkedService
}) => {
  // Call state
  const [callStatus, setCallStatus] = useState<'connecting' | 'connected' | 'ended'>(
    mode === 'call' ? 'connecting' : 'connected'
  );
  const [callDurationSeconds, setCallDurationSeconds] = useState(0);
  const [isMuted, setIsMuted] = useState(false);
  const [isSpeakerOn, setIsSpeakerOn] = useState(true);

  // Chat state
  const [chatMessages, setChatMessages] = useState<Array<{
    id: string;
    sender: 'user' | 'advisor';
    text: string;
    time: string;
    attachmentName?: string;
  }>>([
    {
      id: 'm1',
      sender: 'advisor',
      text: `سلام و احترام. من ${advisor.name} هستم، ${advisor.title}. مدارک یا شرح دقیق سوال حقوقی یا اداری خود را بفرمایید تا با استناد به آخرین بخشنامه‌ها بررسی کنیم.`,
      time: 'هم‌اکنون'
    }
  ]);
  const [inputText, setInputText] = useState('');
  const [uploadedDocName, setUploadedDocName] = useState<string | null>(null);

  // Deep Review state
  const [deepReviewStep, setDeepReviewStep] = useState<'upload' | 'analyzing' | 'verdict'>(
    mode === 'case_review' ? 'upload' : 'verdict'
  );
  const [uploadedFiles, setUploadedFiles] = useState<string[]>([
    'برگ_تشخیص_مالیاتی_۱۴۰۲.pdf',
    'صورتحساب_الکترونیکی_کارپوشه.jpg'
  ]);
  const [caseDescription, setCaseDescription] = useState('');

  // Call timer simulation
  React.useEffect(() => {
    let timer: any;
    if (mode === 'call') {
      const connectTimeout = setTimeout(() => {
        setCallStatus('connected');
      }, 2000);

      timer = setInterval(() => {
        setCallDurationSeconds(prev => prev + 1);
      }, 1000);

      return () => {
        clearTimeout(connectTimeout);
        clearInterval(timer);
      };
    }
  }, [mode]);

  const formatCallTime = (secs: number) => {
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
  };

  const handleSendMessage = () => {
    if (!inputText.trim() && !uploadedDocName) return;

    const newMsg = {
      id: `msg-${Date.now()}`,
      sender: 'user' as const,
      text: inputText,
      time: 'هم‌اکنون',
      attachmentName: uploadedDocName || undefined
    };

    setChatMessages(prev => [...prev, newMsg]);
    setInputText('');
    setUploadedDocName(null);

    // Auto advisor reply after 1.5s
    setTimeout(() => {
      let replyText = 'متن پیام و مدارک شما به طور کامل توسط اینجانب بررسی شد. طبق ماده قانونی مربوطه، بهترین راهکار تنظیم دادخواست و ارجاع پرونده از طریق دفتر پیشخوان است.';
      if (advisor.category === 'tax') {
        replyText = 'بر اساس بخشنامه اخیر سازمان امور مالیاتی، با تنظیم لایحه ماده ۲۳۸ امکان کاهش تا ۶۰٪ جریمه وجود دارد. می‌توانید همین الان پرونده را به دفتر پیشخوان بسپارید.';
      } else if (advisor.category === 'insurance_labor') {
        replyText = 'با توجه به شرح شما، دادخواست ماده ۱۴۸ قانون کار کاملا مسموع است و کارفرما ملزم به پرداخت حق بیمه معوقه خواهد بود.';
      }

      setChatMessages(p => [
        ...p,
        {
          id: `msg-adv-${Date.now()}`,
          sender: 'advisor' as const,
          text: replyText,
          time: 'هم‌اکنون'
        }
      ]);
    }, 1500);
  };

  const handleEndSession = () => {
    const totalCost = mode === 'call' 
      ? Math.max(1, Math.ceil(callDurationSeconds / 60)) * advisor.pricing.phonePerMinute
      : mode === 'case_review' 
      ? advisor.pricing.caseDeepReview 
      : advisor.pricing.textChat;

    const completedSession: ConsultationSession = {
      id: `cns-${Date.now()}`,
      advisorId: advisor.id,
      advisorName: advisor.name,
      advisorAvatar: advisor.avatar,
      advisorTitle: advisor.title,
      categoryTitle: advisor.categoryTitle,
      mode: mode,
      status: 'completed',
      durationSeconds: callDurationSeconds,
      totalFee: totalCost,
      createdAt: '۱۴۰۳/۰۸/۲۲ - برخط',
      trackingCode: `CNS-${Math.floor(10000 + Math.random() * 90000)}`,
      advisorVerdict: `جلسه مشاوره تخصصی پیرامون ${advisor.categoryTitle} با موفقیت پایان یافت و مستندات قانونی ثبت شد.`,
      linkedServiceToExecute: advisor.linkedActionServices[0] ? {
        serviceId: advisor.linkedActionServices[0].serviceId,
        title: advisor.linkedActionServices[0].title
      } : undefined
    };

    onSessionComplete(completedSession);
    setCallStatus('ended');
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-4" dir="rtl">
      <div className="bg-white rounded-3xl w-full max-w-xl shadow-2xl border border-slate-100 flex flex-col h-[650px] max-h-[92vh] overflow-hidden">
        
        {/* ------------------------------------------------------------- */}
        {/* MODAL HEADER                                                  */}
        {/* ------------------------------------------------------------- */}
        <div className="bg-slate-900 text-white p-4 flex items-center justify-between shrink-0">
          <div className="flex items-center gap-3">
            <div className="relative">
              <img 
                src={advisor.avatar} 
                alt={advisor.name} 
                className="w-11 h-11 rounded-2xl object-cover ring-2 ring-emerald-500/40"
              />
              <span className="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-slate-900 rounded-full" />
            </div>
            <div>
              <div className="flex items-center gap-1.5">
                <h3 className="font-black text-sm text-white">{advisor.name}</h3>
                <span className="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 px-2 py-0.5 rounded-md font-bold">
                  {advisor.credentialsBadge}
                </span>
              </div>
              <p className="text-[11px] text-slate-300 font-medium">
                {mode === 'call' && 'تماس صوتی اینترنتی امن'}
                {mode === 'text' && 'مشاوره متنی فوری و بارگذاری اسناد'}
                {mode === 'case_review' && 'بررسی عمیق پرونده و تنظیم لایحه'}
              </p>
            </div>
          </div>

          <button 
            onClick={handleEndSession}
            className="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white transition cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* ------------------------------------------------------------- */}
        {/* MODE 1: CALL VIEW (تماس صوتی اینترنتی امن)                     */}
        {/* ------------------------------------------------------------- */}
        {mode === 'call' && (
          <div className="flex-1 flex flex-col justify-between p-6 bg-gradient-to-b from-slate-900 to-slate-950 text-white text-center">
            
            {/* Top Security Banner */}
            <div className="inline-flex items-center gap-2 bg-white/10 border border-white/10 px-3.5 py-1.5 rounded-full text-[11px] text-emerald-300 mx-auto font-medium shadow-xs">
              <ShieldCheck className="w-4 h-4 text-emerald-400" />
              <span>تماس رمزنگاری‌شده E2E • بدون افشای شماره طرفین</span>
            </div>

            {/* Avatar and Wave Animation */}
            <div className="py-6 flex flex-col items-center">
              <div className="relative">
                {callStatus === 'connected' && (
                  <div className="absolute inset-0 rounded-full bg-emerald-500/20 animate-ping" />
                )}
                <img 
                  src={advisor.avatar} 
                  alt={advisor.name} 
                  className="w-28 h-28 rounded-full object-cover ring-4 ring-emerald-500/60 shadow-2xl relative z-10"
                />
              </div>

              <h2 className="text-lg font-black mt-4 text-white">{advisor.name}</h2>
              <p className="text-xs text-slate-400 mt-1">{advisor.title}</p>

              <div className="mt-4 bg-slate-800/80 border border-slate-700 px-4 py-1.5 rounded-2xl inline-flex items-center gap-2">
                <Clock className="w-4 h-4 text-emerald-400" />
                <span className="font-mono text-sm font-black text-emerald-300">
                  {callStatus === 'connecting' ? 'در حال برقراری ارتباط با مشاور...' : formatCallTime(callDurationSeconds)}
                </span>
              </div>

              <span className="text-[11px] text-slate-400 mt-2">
                تعرفه: {advisor.pricing.phonePerMinute.toLocaleString('fa-IR')} تومان / دقیقه
              </span>
            </div>

            {/* Call Controls Bar */}
            <div className="flex items-center justify-center gap-4 pt-4 border-t border-slate-800">
              <button
                onClick={() => setIsMuted(!isMuted)}
                className={`p-4 rounded-2xl transition cursor-pointer ${
                  isMuted ? 'bg-amber-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200'
                }`}
                title="میکروفون"
              >
                {isMuted ? <MicOff className="w-6 h-6" /> : <Mic className="w-6 h-6" />}
              </button>

              <button
                onClick={handleEndSession}
                className="px-6 py-4 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-black flex items-center gap-2 transition cursor-pointer shadow-lg shadow-rose-600/30"
              >
                <PhoneOff className="w-6 h-6" />
                <span className="text-sm">پایان مکالمه و صدور صورتجلسه</span>
              </button>

              <button
                onClick={() => setIsSpeakerOn(!isSpeakerOn)}
                className={`p-4 rounded-2xl transition cursor-pointer ${
                  isSpeakerOn ? 'bg-emerald-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200'
                }`}
                title="بلندگو"
              >
                <Volume2 className="w-6 h-6" />
              </button>
            </div>

          </div>
        )}

        {/* ------------------------------------------------------------- */}
        {/* MODE 2: TEXT CHAT VIEW (مشاوره متنی فوری)                      */}
        {/* ------------------------------------------------------------- */}
        {mode === 'text' && (
          <div className="flex-1 flex flex-col bg-slate-50 overflow-hidden">
            
            {/* Security Notice */}
            <div className="bg-emerald-50 border-b border-emerald-200/80 px-4 py-2 text-[11px] text-emerald-900 flex items-center justify-between">
              <div className="flex items-center gap-1.5 font-bold">
                <ShieldCheck className="w-4 h-4 text-emerald-600" />
                <span>مشاوره متنی برخط • دسترسی به مدارک پس از اتمام جلسه حذف خواهد شد</span>
              </div>
              <span className="text-[10px] text-emerald-700 font-black bg-white px-2 py-0.5 rounded-md">
                کارمزد: {advisor.pricing.textChat.toLocaleString('fa-IR')} تومان
              </span>
            </div>

            {/* Chat message bubbles */}
            <div className="flex-1 p-4 overflow-y-auto space-y-3">
              {chatMessages.map(msg => {
                const isUser = msg.sender === 'user';
                return (
                  <div key={msg.id} className={`flex flex-col ${isUser ? 'items-end' : 'items-start'}`}>
                    <div className={`max-w-[85%] rounded-2xl p-3.5 text-xs leading-relaxed shadow-xs ${
                      isUser 
                        ? 'bg-emerald-600 text-white rounded-br-xs' 
                        : 'bg-white text-slate-800 border border-slate-200 rounded-bl-xs'
                    }`}>
                      <p>{msg.text}</p>
                      {msg.attachmentName && (
                        <div className={`mt-2 p-2 rounded-xl flex items-center gap-2 text-[11px] font-bold ${
                          isUser ? 'bg-emerald-700/80 text-white' : 'bg-slate-100 text-slate-800'
                        }`}>
                          <FileText className="w-4 h-4 shrink-0" />
                          <span className="truncate">{msg.attachmentName}</span>
                        </div>
                      )}
                      <span className={`block text-[9px] mt-1 text-left ${isUser ? 'text-emerald-100' : 'text-slate-400'}`}>
                        {msg.time}
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Upload preview indicator */}
            {uploadedDocName && (
              <div className="bg-amber-50 border-t border-amber-200 px-4 py-2 flex items-center justify-between text-xs text-amber-900">
                <div className="flex items-center gap-2 font-bold">
                  <FileCheck2 className="w-4 h-4 text-amber-600" />
                  <span>فایل آماده ارسال: {uploadedDocName}</span>
                </div>
                <button 
                  onClick={() => setUploadedDocName(null)}
                  className="text-amber-800 hover:text-amber-950 font-black cursor-pointer text-xs"
                >
                  حذف
                </button>
              </div>
            )}

            {/* Input Bar */}
            <div className="p-3 bg-white border-t border-slate-200 flex items-center gap-2">
              <label 
                className="p-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 cursor-pointer transition shrink-0"
                title="بارگذاری فیش، برگ تشخیص یا مدرک"
              >
                <Paperclip className="w-5 h-5" />
                <input 
                  type="file" 
                  className="hidden" 
                  onChange={(e) => {
                    if (e.target.files?.[0]) {
                      setUploadedDocName(e.target.files[0].name);
                    }
                  }} 
                />
              </label>

              <input
                type="text"
                value={inputText}
                onChange={(e) => setInputText(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleSendMessage()}
                placeholder="سوال یا ابهام خود را بنویسید..."
                className="flex-1 bg-slate-100 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />

              <button
                onClick={handleSendMessage}
                className="p-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white transition cursor-pointer shrink-0 shadow-sm"
              >
                <Send className="w-5 h-5" />
              </button>
            </div>

          </div>
        )}

        {/* ------------------------------------------------------------- */}
        {/* MODE 3: DEEP CASE REVIEW VIEW (بررسی عمیق پرونده و لایحه)       */}
        {/* ------------------------------------------------------------- */}
        {mode === 'case_review' && (
          <div className="flex-1 flex flex-col p-4 sm:p-5 bg-slate-50 overflow-y-auto space-y-4">
            
            <div className="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-2xl p-3.5 space-y-1">
              <div className="flex items-center gap-2 text-indigo-950 font-black text-xs">
                <Sparkles className="w-4 h-4 text-indigo-600" />
                <span>بررسی موشکافانه پرونده + تنظیم لایحه تخصصی کتبی</span>
              </div>
              <p className="text-[11px] text-indigo-900 leading-relaxed">
                کارشناس پس از مطالعه اسناد بارگذاری‌شده، تحلیل حقوقی کتبی و لایحه اعتراضی استاندارد تدوین نموده و خدمت پیشخوان مناسب را جهت پیگیری مستقیم فعال می‌سازد.
              </p>
            </div>

            {/* Uploaded Documents List */}
            <div className="bg-white rounded-2xl p-4 border border-slate-200/80 space-y-3">
              <h4 className="font-black text-xs text-slate-800 flex items-center justify-between">
                <span>اسناد و ضمائم پرونده</span>
                <span className="text-[10px] text-slate-400 font-normal">فرمت‌های مجاز: PDF, JPG, PNG</span>
              </h4>

              <div className="space-y-2">
                {uploadedFiles.map((file, idx) => (
                  <div key={idx} className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <div className="flex items-center gap-2 font-bold text-slate-700">
                      <FileCheck className="w-4 h-4 text-emerald-600" />
                      <span>{file}</span>
                    </div>
                    <span className="text-[10px] bg-emerald-100 text-emerald-800 font-black px-2 py-0.5 rounded-md">
                      تایید اولیه
                    </span>
                  </div>
                ))}
              </div>

              <label className="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl p-3 flex flex-col items-center justify-center text-slate-500 hover:text-emerald-700 cursor-pointer transition">
                <Upload className="w-5 h-5 mb-1" />
                <span className="text-xs font-bold">افزودن مدرک جدید به پرونده</span>
                <input 
                  type="file" 
                  className="hidden" 
                  onChange={(e) => {
                    if (e.target.files?.[0]) {
                      setUploadedFiles(prev => [...prev, e.target.files![0].name]);
                    }
                  }} 
                />
              </label>
            </div>

            {/* Case Details Input */}
            <div className="bg-white rounded-2xl p-4 border border-slate-200/80 space-y-2">
              <label className="block text-xs font-black text-slate-800">
                توضیحات و خواسته دقیق از مشاور:
              </label>
              <textarea
                value={caseDescription}
                onChange={(e) => setCaseDescription(e.target.value)}
                placeholder="خواسته خود را بنویسید (مثلاً: ابطال جریمه عدم تسلیم اظهارنامه، تقاضای اعمال ماده ۲۳۸، یا اعتراض به رای بدوی اداره کار...)"
                rows={3}
                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
            </div>

            {/* Actionable Follow-up Box (مشاوره متصل به اقدام) */}
            {advisor.linkedActionServices.length > 0 && (
              <div className="bg-emerald-50 border border-emerald-300 rounded-2xl p-4 space-y-2">
                <div className="flex items-center gap-2 text-emerald-950 font-black text-xs">
                  <Building2 className="w-4 h-4 text-emerald-700" />
                  <span>قابلیت «مشاوره متصل به اقدام» پیشخوان:</span>
                </div>
                <p className="text-[11px] text-emerald-900 leading-relaxed">
                  پس از بررسی مدارک، لایحه دفاعیه مستقیماً به سرویس 
                  <strong className="mx-1 text-emerald-950 font-black">«{advisor.linkedActionServices[0].title}»</strong>
                  در نزدیک‌ترین دفتر پیشخوان متصل خواهد شد.
                </p>
              </div>
            )}

          </div>
        )}

        {/* ------------------------------------------------------------- */}
        {/* FOOTER ACTION BAR                                             */}
        {/* ------------------------------------------------------------- */}
        <div className="p-4 bg-white border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
          <div className="text-xs">
            <span className="text-slate-400 block text-[10px]">مبلغ قابل پرداخت:</span>
            <span className="font-black text-slate-900 font-mono text-sm">
              {mode === 'call' 
                ? `${advisor.pricing.phonePerMinute.toLocaleString('fa-IR')} ت/دقیقه`
                : mode === 'case_review' 
                ? `${advisor.pricing.caseDeepReview.toLocaleString('fa-IR')} تومان`
                : `${advisor.pricing.textChat.toLocaleString('fa-IR')} تومان`}
            </span>
          </div>

          <div className="flex items-center gap-2">
            {advisor.linkedActionServices[0] && onExecuteLinkedService && (
              <button
                onClick={() => {
                  onExecuteLinkedService(advisor.linkedActionServices[0].serviceId);
                  onClose();
                }}
                className="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-black text-xs px-3.5 py-2.5 rounded-xl transition cursor-pointer flex items-center gap-1.5"
              >
                <span>انتقال مستقیم به پیشخوان</span>
                <ArrowLeft className="w-4 h-4" />
              </button>
            )}

            <button
              onClick={handleEndSession}
              className="bg-slate-900 hover:bg-slate-800 text-white font-black text-xs px-4 py-2.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 shadow-sm"
            >
              <Check className="w-4 h-4" />
              <span>پایان و ثبت پرونده</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  );
};
