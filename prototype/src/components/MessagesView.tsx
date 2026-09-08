import React, { useState } from 'react';
import { ChatMessage, CaseRequest, PishkhanOffice } from '../types';
import { 
  MessageSquare, 
  Send, 
  Building2, 
  CheckCheck, 
  Paperclip, 
  Phone, 
  Clock, 
  ShieldCheck, 
  AlertCircle,
  ChevronLeft,
  Bell,
  CheckCircle2,
  FileText
} from 'lucide-react';

interface MessagesViewProps {
  messages: ChatMessage[];
  cases: CaseRequest[];
  onSendMessage: (caseId: string, text: string) => void;
}

export const MessagesView: React.FC<MessagesViewProps> = ({
  messages,
  cases,
  onSendMessage
}) => {
  const [activeTab, setActiveTab] = useState<'chats' | 'notifications'>('chats');
  const [selectedCaseId, setSelectedCaseId] = useState<string>(cases[0]?.id || 'case-1001');
  const [inputText, setInputText] = useState<string>('');

  const activeCase = cases.find(c => c.id === selectedCaseId) || cases[0];
  const caseMessages = messages.filter(m => m.caseId === selectedCaseId);

  const handleSend = (e: React.FormEvent) => {
    e.preventDefault();
    if (!inputText.trim()) return;
    onSendMessage(selectedCaseId, inputText.trim());
    setInputText('');
  };

  return (
    <div className="space-y-4 text-right pb-10" dir="rtl">
      {/* Top Segment Tabs */}
      <div className="flex bg-slate-200/80 p-1 rounded-xl">
        <button
          onClick={() => setActiveTab('chats')}
          className={`flex-1 py-2 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 ${
            activeTab === 'chats' 
              ? 'bg-white text-slate-900 shadow-sm' 
              : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          <MessageSquare className="w-3.5 h-3.5" />
          <span>گفتگوی باجه و اپراتور</span>
          <span className="bg-emerald-100 text-emerald-800 text-[10px] px-1.5 py-0.2 rounded-full">
            {messages.length}
          </span>
        </button>

        <button
          onClick={() => setActiveTab('notifications')}
          className={`flex-1 py-2 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 ${
            activeTab === 'notifications' 
              ? 'bg-white text-slate-900 shadow-sm' 
              : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          <Bell className="w-3.5 h-3.5" />
          <span>ابلاغیه‌ها و پیامک‌ها</span>
        </button>
      </div>

      {activeTab === 'chats' ? (
        <div className="space-y-3">
          {/* Active Case Selector Strip */}
          <div className="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
            {cases.map((c) => {
              const isSelected = c.id === selectedCaseId;
              return (
                <button
                  key={c.id}
                  onClick={() => setSelectedCaseId(c.id)}
                  className={`px-3 py-2 rounded-xl text-xs font-medium border whitespace-nowrap transition flex items-center gap-2 ${
                    isSelected 
                      ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' 
                      : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <span className="font-bold">{c.serviceTitle}</span>
                  <span className={`text-[10px] px-1.5 py-0.5 rounded ${
                    isSelected ? 'bg-emerald-700 text-emerald-100' : 'bg-slate-100 text-slate-600'
                  }`}>
                    {c.trackingCode}
                  </span>
                </button>
              );
            })}
          </div>

          {/* Chat Window Card */}
          <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-[520px]">
            {/* Chat Header */}
            <div className="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <div className="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                  <Building2 className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="font-bold text-xs text-slate-900 leading-tight">
                    {activeCase?.assignedOffice?.name || 'دفتر پیشخوان دولت ولی‌عصر (کد ۱۴۰۲)'}
                  </h3>
                  <div className="flex items-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>کارشناس سجلی برخط • پاسخگویی میانگین ۱۰ دقیقه</span>
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-1">
                <a 
                  href={`tel:${activeCase?.assignedOffice?.phone || '02188997766'}`}
                  className="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-emerald-600 hover:border-emerald-300 flex items-center justify-center transition"
                  title="تماس تلفنی با باجه"
                >
                  <Phone className="w-4 h-4" />
                </a>
              </div>
            </div>

            {/* Messages Scroll Area */}
            <div className="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50/50">
              {/* System Security Notice */}
              <div className="bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl p-2.5 text-[11px] flex items-center gap-2 justify-center text-center">
                <ShieldCheck className="w-4 h-4 text-emerald-600 shrink-0" />
                <span>پیام‌های این گفتگو در بستر امن شبکه دولت ثبت و مستندسازی می‌شود.</span>
              </div>

              {caseMessages.map((msg) => {
                const isCitizen = msg.sender === 'citizen';
                return (
                  <div
                    key={msg.id}
                    className={`flex flex-col ${isCitizen ? 'items-end' : 'items-start'}`}
                  >
                    <div
                      className={`max-w-[85%] rounded-2xl p-3 text-xs leading-relaxed shadow-xs ${
                        isCitizen
                          ? 'bg-emerald-600 text-white rounded-br-xs'
                          : 'bg-white text-slate-800 border border-slate-200 rounded-bl-xs'
                      }`}
                    >
                      <div className="text-[10px] font-bold opacity-75 mb-1">
                        {msg.senderName}
                      </div>
                      <p>{msg.text}</p>
                      <div
                        className={`text-[10px] mt-1.5 flex items-center gap-1 ${
                          isCitizen ? 'text-emerald-100 justify-end' : 'text-slate-400'
                        }`}
                      >
                        <Clock className="w-2.5 h-2.5" />
                        <span>{msg.time}</span>
                        {isCitizen && <CheckCheck className="w-3 h-3 text-emerald-200" />}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Chat Input Bar */}
            <form onSubmit={handleSend} className="p-2.5 bg-white border-t border-slate-200 flex items-center gap-2">
              <input
                type="text"
                value={inputText}
                onChange={(e) => setInputText(e.target.value)}
                placeholder="پیام خود را برای کارشناس دفتر بنویسید..."
                className="flex-1 bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:outline-none focus:border-emerald-600 transition"
              />
              <button
                type="submit"
                disabled={!inputText.trim()}
                className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white p-2.5 rounded-xl transition shadow flex items-center justify-center"
              >
                <Send className="w-4 h-4 rotate-180" />
              </button>
            </form>
          </div>
        </div>
      ) : (
        /* Notifications & SMS tab */
        <div className="space-y-2.5">
          <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-1">
            <div className="flex items-center justify-between text-xs">
              <span className="font-bold text-slate-800">تغییر وضعیت پرونده {cases[0]?.trackingCode}</span>
              <span className="text-slate-400 text-[11px]">۳ ساعت پیش</span>
            </div>
            <p className="text-xs text-slate-600 leading-relaxed">
              پرونده تعویض شناسنامه توسط دفتر پیشخوان بررسی و به دلیل عدم وضوح تصویر عودت شد.
            </p>
            <div className="pt-2 flex items-center gap-1.5 text-amber-700 text-xs font-bold">
              <AlertCircle className="w-3.5 h-3.5" />
              <span>اقدام فوری جهت رفع نقص نیاز است</span>
            </div>
          </div>

          <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-1">
            <div className="flex items-center justify-between text-xs">
              <span className="font-bold text-slate-800">تاییدیه تسویه خلافی خودرو</span>
              <span className="text-slate-400 text-[11px]">۱ روز پیش</span>
            </div>
            <p className="text-xs text-slate-600 leading-relaxed">
              مفاصاحساب رسمی پلاک ۷۲- ایران ۳۳ با موفقیت صادر و در مخزن اسناد شما ثبت شد.
            </p>
          </div>

          <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-1">
            <div className="flex items-center justify-between text-xs">
              <span className="font-bold text-slate-800">واریز کش‌بک شهروند طلایی</span>
              <span className="text-slate-400 text-[11px]">۳ روز پیش</span>
            </div>
            <p className="text-xs text-slate-600 leading-relaxed">
              مبلغ ۲۵٬۰۰۰ تومان هدیه نقدی به کیف پول شهروندی شما واریز گردید.
            </p>
          </div>
        </div>
      )}
    </div>
  );
};
