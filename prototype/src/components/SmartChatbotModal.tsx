import React, { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Send, 
  Mic, 
  MicOff, 
  X, 
  Sparkles, 
  Bot, 
  User, 
  ArrowLeft, 
  CheckCircle2, 
  Clock, 
  Car, 
  BookOpen, 
  Building2, 
  MapPin, 
  FileText, 
  CreditCard, 
  HelpCircle,
  ChevronLeft
} from 'lucide-react';
import { CitizenService, CaseRequest, PishkhanOffice } from '../types';

interface SmartChatbotModalProps {
  services: CitizenService[];
  cases: CaseRequest[];
  offices?: PishkhanOffice[];
  onClose: () => void;
  onSelectService?: (service: CitizenService) => void;
  onNavigateToCases?: () => void;
  onNavigateToMap?: () => void;
}

interface Message {
  id: string;
  sender: 'bot' | 'user';
  text: string;
  timestamp: string;
  suggestedActions?: {
    label: string;
    actionType: 'service' | 'cases' | 'map' | 'quick_reply';
    payload?: string;
  }[];
}

export const SmartChatbotModal: React.FC<SmartChatbotModalProps> = ({
  services,
  cases,
  offices = [],
  onClose,
  onSelectService,
  onNavigateToCases,
  onNavigateToMap
}) => {
  const [messages, setMessages] = useState<Message[]>([
    {
      id: 'msg-welcome',
      sender: 'bot',
      text: 'سلام! 👋 من دستیار هوشمند پیشخوان دولت و خدمات شهروندی هستم. چطور می‌تونم کمکتون کنم؟\nمی‌تونید در مورد استعلام خلافی، کارت ملی هوشمند، ثبت‌نام خدمات، پیگیری پرونده یا نزدیک‌ترین دفاتر از من بپرسید.',
      timestamp: 'لحظاتی پیش',
      suggestedActions: [
        { label: 'استعلام و پرداخت خلافی', actionType: 'quick_reply', payload: 'استعلام و پرداخت خلافی' },
        { label: 'پیگیری پرونده‌های من', actionType: 'cases' },
        { label: 'تعویض شناسنامه و کارت ملی', actionType: 'quick_reply', payload: 'تعویض شناسنامه و کارت ملی' },
        { label: 'نزدیک‌ترین دفتر پیشخوان', actionType: 'map' }
      ]
    }
  ]);

  const [inputText, setInputText] = useState('');
  const [isTyping, setIsTyping] = useState(false);
  const [isListening, setIsListening] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages, isTyping]);

  const handleSendMessage = (textToSend?: string) => {
    const text = (textToSend || inputText).trim();
    if (!text) return;

    const userMsg: Message = {
      id: `user-${Date.now()}`,
      sender: 'user',
      text,
      timestamp: new Date().toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' })
    };

    setMessages(prev => [...prev, userMsg]);
    if (!textToSend) setInputText('');
    setIsTyping(true);

    // AI Logic for Responding
    setTimeout(() => {
      generateBotResponse(text);
      setIsTyping(false);
    }, 800);
  };

  const generateBotResponse = (query: string) => {
    const q = query.toLowerCase();
    let replyText = '';
    let actions: Message['suggestedActions'] = [];

    if (q.includes('خلافی') || q.includes('جریمه') || q.includes('خودرو') || q.includes('ماشین')) {
      const penaltyService = services.find(s => s.id === 'vh-penalties') || services[0];
      replyText = 'برای استعلام و پرداخت آنلاین خلافی خودرو، می‌توانید از سامانه یکپارچه راهور استفاده کنید. اطلاعات پلاک و بارکد کارت خودرو بررسی شده و تسویه آنی انجام می‌شود.';
      actions = [
        { label: 'شروع استعلام خلافی ←', actionType: 'service', payload: penaltyService.id },
        { label: 'استعلام نمره منفی گواهینامه', actionType: 'quick_reply', payload: 'نمره منفی گواهینامه' }
      ];
    } else if (q.includes('شناسنامه') || q.includes('کارت ملی') || q.includes('هویت') || q.includes('ثبت احوال')) {
      const idService = services.find(s => s.id === 'id-birth-cert' || s.id === 'id-national-card') || services[0];
      replyText = 'برای تعویض شناسنامه مستعمل یا صدور المثنی، مدارک هویتی شما از مخزن مدارک دیجیتال بارگذاری می‌شود و پس از تایید توسط باجه پیشخوان، اصل مدرک توسط پست درب منزل تحویل می‌گردد.';
      actions = [
        { label: 'ثبت درخواست تعویض شناسنامه ←', actionType: 'service', payload: idService.id },
        { label: 'مشاهده مدارک من در مخزن', actionType: 'quick_reply', payload: 'مخزن مدارک' }
      ];
    } else if (q.includes('پیگیری') || q.includes('پرونده') || q.includes('درخواست') || q.includes('وضعیت')) {
      const activeCount = cases.filter(c => c.status !== 'completed').length;
      replyText = `شما در حال حاضر ${activeCount} پرونده در دست بررسی دارید. آخرین پرونده‌های شما در کارتابل رهگیری با جزئیات مراحل، زمان تخمینی و گزارش اپراتور قابل مشاهده است.`;
      actions = [
        { label: 'مشاهده پرونده‌های من ←', actionType: 'cases' }
      ];
    } else if (q.includes('دفتر') || q.includes('دفاتر') || q.includes('آدرس') || q.includes('نوبت') || q.includes('حضوری') || q.includes('نقشه')) {
      replyText = 'دفاتر پیشخوان دولت در سراسر کشور با سامانه هوشمند رزرو نوبت و مسیریابی متصل هستند. شما می‌توانید نزدیک‌ترین دفتر را روی نقشه انتخاب کنید و در کمتر از ۱ دقیقه نوبت بگیرید.';
      actions = [
        { label: 'مشاهده نقشه و شعب دفاتر پیشخوان ←', actionType: 'map' }
      ];
    } else if (q.includes('وام') || q.includes('اعتبار') || q.includes('کیف پول') || q.includes('شارژ')) {
      replyText = 'کیف پول شهروندی شما امکان شارژ آنی بدون کارمزد شتابی، برداشت به شبا پایا، انتقال به شهروندان و فعال‌سازی کدهای هدیه پیشخوان را داراست.';
      actions = [
        { label: 'کد هدیه ۵۰ هزار تومانی GIFT50', actionType: 'quick_reply', payload: 'کد هدیه' }
      ];
    } else if (q.includes('بیمه') || q.includes('سلامت') || q.includes('تامین اجتماعی')) {
      const insService = services.find(s => s.id === 'hl-insurance') || services[0];
      replyText = 'خدمت صدور و تمدید آنلاین دفترچه بیمه سلامت ایرانیان فعال است و سوابق بیمه‌ای شما مستقیماً از سامانه استعلام می‌شود.';
      actions = [
        { label: 'تمدید بیمه سلامت ←', actionType: 'service', payload: insService.id }
      ];
    } else {
      replyText = 'درخواست شما بررسی شد. شما می‌توانید از فهرست ۲۴ خدمت دولتی آنلاین، استعلام و ثبت‌نام هوشمند را در چند دقیقه انجام دهید یا از گزینه‌های زیر استفاده کنید:';
      actions = [
        { label: 'فهرست خدمات دولتی', actionType: 'quick_reply', payload: 'فهرست خدمات' },
        { label: 'پیگیری پرونده‌ها', actionType: 'cases' },
        { label: 'نزدیک‌ترین دفتر پیشخوان', actionType: 'map' }
      ];
    }

    const botMsg: Message = {
      id: `bot-${Date.now()}`,
      sender: 'bot',
      text: replyText,
      timestamp: new Date().toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' }),
      suggestedActions: actions
    };

    setMessages(prev => [...prev, botMsg]);
  };

  const handleActionClick = (action: { label: string; actionType: string; payload?: string }) => {
    if (action.actionType === 'service') {
      const s = services.find(srv => srv.id === action.payload) || services[0];
      if (s && onSelectService) {
        onClose();
        onSelectService(s);
      }
    } else if (action.actionType === 'cases') {
      if (onNavigateToCases) {
        onClose();
        onNavigateToCases();
      }
    } else if (action.actionType === 'map') {
      if (onNavigateToMap) {
        onClose();
        onNavigateToMap();
      }
    } else if (action.actionType === 'quick_reply' && action.payload) {
      handleSendMessage(action.payload);
    }
  };

  const handleToggleVoice = () => {
    if (!isListening) {
      setIsListening(true);
      setInputText('در حال ضبط صدا...');
      setTimeout(() => {
        setIsListening(false);
        const sampleQuery = 'استعلام و پرداخت خلافی خودرو';
        setInputText(sampleQuery);
        handleSendMessage(sampleQuery);
      }, 2000);
    } else {
      setIsListening(false);
      setInputText('');
    }
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4">
      <motion.div
        initial={{ y: 50, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        exit={{ y: 50, opacity: 0 }}
        className="bg-white text-slate-900 w-full max-w-lg h-[92vh] sm:h-[650px] rounded-t-3xl sm:rounded-3xl shadow-2xl flex flex-col overflow-hidden border border-slate-200/80"
        dir="rtl"
      >
        {/* Header matching the Robot Avatar branding */}
        <div className="bg-gradient-to-r from-slate-900 via-slate-800 to-[#1e293b] text-white p-4 flex items-center justify-between border-b border-slate-700/50">
          <div className="flex items-center gap-3">
            {/* Robot Avatar Icon with Green Dot */}
            <div className="relative">
              <div className="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-xs">
                {/* SVG Robot Icon from screenshot */}
                <svg className="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M12 2v2" />
                  <path d="M12 4a5 5 0 0 0-5 5v5a5 5 0 0 0 5 5h1l3 3v-3a5 5 0 0 0 3-4.5V9a5 5 0 0 0-5-5h-2z" />
                  <circle cx="9.5" cy="10.5" r="1" fill="currentColor" />
                  <circle cx="14.5" cy="10.5" r="1" fill="currentColor" />
                  <path d="M9.5 14c.8.7 1.7 1 2.5 1s1.7-.3 2.5-1" />
                  <path d="M4 11h2" />
                  <path d="M18 11h2" />
                </svg>
              </div>
              {/* Green online badge */}
              <span className="absolute -top-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 border-2 border-slate-900 rounded-full flex items-center justify-center">
                <span className="w-1.5 h-1.5 bg-white rounded-full animate-ping" />
              </span>
            </div>

            <div>
              <div className="flex items-center gap-1.5">
                <h3 className="font-black text-sm text-white">دستیار هوشمند پیشخوان</h3>
                <span className="text-[9px] bg-emerald-500/20 text-emerald-300 font-bold px-1.5 py-0.5 rounded-full border border-emerald-500/30">
                  آنلاین و پاسخگو
                </span>
              </div>
              <p className="text-[11px] text-slate-300">راهنمایی، استعلام و ثبت ۲۴ خدمت دولتی</p>
            </div>
          </div>

          <button
            onClick={onClose}
            className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Chat Messages Body */}
        <div className="flex-1 p-4 overflow-y-auto space-y-3.5 bg-[#f8fafc]">
          {messages.map((msg) => (
            <div
              key={msg.id}
              className={`flex items-start gap-2.5 ${
                msg.sender === 'user' ? 'flex-row-reverse' : 'flex-row'
              }`}
            >
              {/* Sender Avatar */}
              {msg.sender === 'bot' ? (
                <div className="w-7 h-7 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                  <Bot className="w-4 h-4 text-emerald-400" />
                </div>
              ) : (
                <div className="w-7 h-7 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                  <User className="w-4 h-4" />
                </div>
              )}

              {/* Message Bubble */}
              <div className={`max-w-[82%] space-y-2 ${
                msg.sender === 'user' ? 'text-left' : 'text-right'
              }`}>
                <div
                  className={`p-3.5 rounded-2xl text-xs leading-relaxed ${
                    msg.sender === 'user'
                      ? 'bg-blue-600 text-white rounded-tl-xs shadow-xs font-medium'
                      : 'bg-white text-slate-800 rounded-tr-xs shadow-xs border border-slate-200/80 whitespace-pre-line font-normal'
                  }`}
                >
                  {msg.text}
                </div>

                {/* Suggested Action Buttons */}
                {msg.suggestedActions && msg.suggestedActions.length > 0 && (
                  <div className="flex flex-wrap gap-1.5 pt-1">
                    {msg.suggestedActions.map((action, idx) => (
                      <button
                        key={idx}
                        onClick={() => handleActionClick(action)}
                        className="bg-white hover:bg-slate-50 text-blue-700 active:scale-95 text-[11px] font-bold px-2.5 py-1.5 rounded-xl border border-blue-200 shadow-2xs transition-all flex items-center gap-1 cursor-pointer"
                      >
                        <span>{action.label}</span>
                      </button>
                    ))}
                  </div>
                )}

                <span className="text-[9px] text-slate-400 block px-1">
                  {msg.timestamp}
                </span>
              </div>
            </div>
          ))}

          {isTyping && (
            <div className="flex items-center gap-2">
              <div className="w-7 h-7 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <Bot className="w-4 h-4 text-emerald-400" />
              </div>
              <div className="bg-white p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-1.5">
                <span className="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" />
                <span className="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce delay-100" />
                <span className="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce delay-200" />
                <span className="text-[10px] text-slate-400 mr-1 font-medium">در حال پاسخگویی...</span>
              </div>
            </div>
          )}

          <div ref={messagesEndRef} />
        </div>

        {/* Quick Suggestion Chips */}
        <div className="p-2 bg-white border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
          <button
            onClick={() => handleSendMessage('استعلام و پرداخت خلافی')}
            className="shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
          >
            🚗 استعلام خلافی
          </button>
          <button
            onClick={() => handleSendMessage('تعویض شناسنامه و کارت ملی')}
            className="shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
          >
            🪪 شناسنامه و کارت ملی
          </button>
          <button
            onClick={() => handleSendMessage('نزدیک‌ترین دفتر پیشخوان')}
            className="shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
          >
            📍 رزرو نوبت دفتر
          </button>
          <button
            onClick={() => handleSendMessage('پیگیری پرونده‌های من')}
            className="shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
          >
            📁 رهگیری پرونده
          </button>
        </div>

        {/* Input Bar with Voice & Send */}
        <div className="p-3 bg-white border-t border-slate-200 flex items-center gap-2">
          <button
            onClick={handleToggleVoice}
            className={`w-10 h-10 rounded-2xl flex items-center justify-center transition-all cursor-pointer shrink-0 ${
              isListening
                ? 'bg-rose-500 text-white animate-pulse shadow-md shadow-rose-500/30'
                : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
            }`}
            title="دستیار صوتی هوشمند"
          >
            {isListening ? <MicOff className="w-5 h-5" /> : <Mic className="w-5 h-5 text-emerald-600" />}
          </button>

          <input
            type="text"
            value={inputText}
            onChange={(e) => setInputText(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') handleSendMessage();
            }}
            placeholder="سوال یا خدمت مورد نظر را بنویسید..."
            className="flex-1 bg-slate-100 focus:bg-white border border-transparent focus:border-blue-600 rounded-2xl px-3.5 py-2.5 text-xs text-slate-900 outline-none transition-all font-medium"
          />

          <button
            onClick={() => handleSendMessage()}
            disabled={!inputText.trim()}
            className={`w-10 h-10 rounded-2xl flex items-center justify-center transition-all shrink-0 ${
              inputText.trim()
                ? 'bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-600/20 cursor-pointer active:scale-95'
                : 'bg-slate-100 text-slate-400 cursor-not-allowed'
            }`}
          >
            <Send className="w-4 h-4 transform rotate-180" />
          </button>
        </div>
      </motion.div>
    </div>
  );
};
