import React, { useState } from 'react';
import { 
  Mic, 
  MicOff, 
  Sparkles, 
  X, 
  ArrowLeft, 
  Volume2, 
  CheckCircle2,
  HelpCircle,
  Zap,
  Car,
  BookOpen,
  Gift,
  HeartPulse,
  Building2,
  MapPin
} from 'lucide-react';
import { CitizenService, PishkhanOffice } from '../types';

interface VoiceAssistantModalProps {
  services: CitizenService[];
  onClose: () => void;
  onSelectService: (service: CitizenService) => void;
  onGoToMap: () => void;
}

export const VoiceAssistantModal: React.FC<VoiceAssistantModalProps> = ({
  services,
  onClose,
  onSelectService,
  onGoToMap
}) => {
  const [isListening, setIsListening] = useState(false);
  const [recognizedText, setRecognizedText] = useState('');
  const [assistantAnswer, setAssistantAnswer] = useState<string | null>(null);
  const [matchedService, setMatchedService] = useState<CitizenService | null>(null);

  const samplePrompts = [
    {
      text: 'می‌خوام شناسنامه‌ام رو تعویض کنم',
      icon: <BookOpen className="w-4 h-4 text-emerald-600" />,
      serviceId: 'id-birth-cert',
      response: 'خدمت «تعویض و صدور المثنی شناسنامه» انتخاب شد. اطلاعات شما از مخزن مدارک بارگذاری می‌شود.'
    },
    {
      text: 'استعلام و پرداخت خلافی خودرو',
      icon: <Car className="w-4 h-4 text-blue-600" />,
      serviceId: 'vh-penalties',
      response: 'سامانه استعلام آنلاین خلافی پلیس راهور آماده شد. پلاک شما آماده استعلام است.'
    },
    {
      text: 'استعلام کالابرگ و یارانه این ماه',
      icon: <Gift className="w-4 h-4 text-amber-600" />,
      serviceId: 'wf-kalabarg',
      response: 'سامانه کالابرگ الکترونیک انتخاب شد. مانده اعتبار خرید شما بررسی می‌شود.'
    },
    {
      text: 'تمدید آنلاین بیمه سلامت',
      icon: <HeartPulse className="w-4 h-4 text-rose-600" />,
      serviceId: 'hl-insurance',
      response: 'خدمت صدور و تمدید دفترچه بیمه سلامت ایرانیان انتخاب شد.'
    },
    {
      text: 'پیدا کردن نزدیک‌ترین دفتر پیشخوان',
      icon: <MapPin className="w-4 h-4 text-indigo-600" />,
      action: 'map',
      response: 'نقشه دفاتر پیشخوان آنلاین و باز در نزدیکی شما باز شد.'
    }
  ];

  const handleTriggerVoice = () => {
    setIsListening(true);
    setRecognizedText('در حال شنیدن صدای شما...');
    setAssistantAnswer(null);
    setMatchedService(null);

    // Simulate recognition of standard query
    setTimeout(() => {
      setIsListening(false);
      const chosen = samplePrompts[0];
      setRecognizedText(chosen.text);
      setAssistantAnswer(chosen.response);
      const targetService = services.find(s => s.id === chosen.serviceId);
      if (targetService) setMatchedService(targetService);
    }, 2200);
  };

  const handleSelectPrompt = (prompt: typeof samplePrompts[0]) => {
    setRecognizedText(prompt.text);
    setAssistantAnswer(prompt.response);

    if (prompt.action === 'map') {
      setTimeout(() => {
        onGoToMap();
        onClose();
      }, 1000);
      return;
    }

    const targetService = services.find(s => s.id === prompt.serviceId);
    if (targetService) {
      setMatchedService(targetService);
    }
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200">
      <div className="bg-white text-slate-900 w-full max-w-md rounded-3xl p-5 sm:p-6 shadow-2xl border border-slate-100 relative">
        
        {/* Header */}
        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-xl bg-gradient-to-tr from-violet-600 to-indigo-600 text-white flex items-center justify-center">
              <Sparkles className="w-4 h-4" />
            </div>
            <div>
              <h3 className="text-sm font-extrabold text-slate-900">دستیار صوتی و راهنمای آسان</h3>
              <span className="text-[11px] text-slate-400">بدون نیاز به تایپ، فقط صحبت کنید</span>
            </div>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 p-1">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Big Mic Button Center */}
        <div className="my-6 text-center">
          <div className="relative inline-flex items-center justify-center">
            {isListening && (
              <>
                <div className="absolute w-28 h-28 rounded-full bg-violet-500/20 animate-ping" />
                <div className="absolute w-24 h-24 rounded-full bg-violet-500/30 animate-pulse" />
              </>
            )}
            
            <button
              onClick={handleTriggerVoice}
              className={`relative w-20 h-20 rounded-full flex items-center justify-center text-white shadow-xl transition-all cursor-pointer ${
                isListening 
                  ? 'bg-rose-500 shadow-rose-500/40 scale-105' 
                  : 'bg-gradient-to-tr from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 shadow-indigo-500/30 hover:scale-105 active:scale-95'
              }`}
            >
              {isListening ? <MicOff className="w-8 h-8 animate-bounce" /> : <Mic className="w-8 h-8" />}
            </button>
          </div>

          <h4 className="font-extrabold text-sm text-slate-800 mt-3">
            {isListening ? 'در حال شنیدن... صحبت کنید' : 'برای صحبت کردن دکمه میکروفون را لمس کنید'}
          </h4>
          <p className="text-xs text-slate-400 mt-0.5">
            مثال: «می‌خوام برای تعویض شناسنامه اقدام کنم»
          </p>
        </div>

        {/* Recognized & Assistant Answer */}
        {recognizedText && (
          <div className="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-2 text-xs mb-4">
            <div className="flex items-center gap-1.5 text-slate-500 font-semibold">
              <Volume2 className="w-3.5 h-3.5 text-violet-600" />
              <span>عبارت تشخیص داده شده:</span>
            </div>
            <p className="font-bold text-slate-900 text-sm">«{recognizedText}»</p>

            {assistantAnswer && (
              <div className="pt-2 border-t border-slate-200 text-indigo-900 text-[11px] leading-relaxed">
                💡 {assistantAnswer}
              </div>
            )}

            {matchedService && (
              <button
                onClick={() => {
                  onSelectService(matchedService);
                  onClose();
                }}
                className="w-full mt-2 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold py-2.5 rounded-xl shadow-sm text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
              >
                <span>شروع درخواست {matchedService.title}</span>
                <ArrowLeft className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
        )}

        {/* Quick Click Prompts */}
        <div className="space-y-1.5">
          <span className="text-[11px] font-bold text-slate-500 block">یا یکی از درخواست‌های پرکاربرد زیر را انتخاب کنید:</span>
          <div className="space-y-1.5 max-h-48 overflow-y-auto pr-1">
            {samplePrompts.map((p, idx) => (
              <button
                key={idx}
                onClick={() => handleSelectPrompt(p)}
                className="w-full p-2.5 rounded-xl bg-slate-50 hover:bg-violet-50 text-right border border-slate-100 hover:border-violet-200 flex items-center gap-2.5 text-xs text-slate-700 hover:text-violet-950 font-bold transition-all"
              >
                {p.icon}
                <span className="flex-1 truncate">{p.text}</span>
              </button>
            ))}
          </div>
        </div>

      </div>
    </div>
  );
};
