import React, { useState } from 'react';
import { 
  FolderClock, 
  CheckCircle2, 
  Clock, 
  AlertTriangle, 
  Building2, 
  UploadCloud, 
  ChevronDown, 
  ChevronUp, 
  Phone, 
  RotateCcw, 
  ShieldCheck, 
  X, 
  MessageSquare, 
  Send, 
  CheckCheck, 
  Copy, 
  Check, 
  FileEdit,
  ArrowLeft,
  Calendar,
  Sparkles,
  Inbox
} from 'lucide-react';
import { CaseRequest, ChatMessage } from '../types';

interface CaseTrackingViewProps {
  cases: CaseRequest[];
  onFixCaseDocument: (caseId: string, docName: string, newFile: string) => void;
  selectedCaseId?: string | null;
  messages?: ChatMessage[];
  onSendMessage?: (caseId: string, text: string) => void;
}

export const CaseTrackingView: React.FC<CaseTrackingViewProps> = ({
  cases,
  onFixCaseDocument,
  selectedCaseId,
  messages = [],
  onSendMessage
}) => {
  const [filterTab, setFilterTab] = useState<'all' | 'action_required' | 'in_progress' | 'completed'>('all');
  const [expandedCaseId, setExpandedCaseId] = useState<string | null>(selectedCaseId || null);
  const [copiedCode, setCopiedCode] = useState<string | null>(null);
  
  // Fix document modal state
  const [fixingCase, setFixingCase] = useState<CaseRequest | null>(null);
  const [uploadedFilePreview, setUploadedFilePreview] = useState<string | null>(null);
  const [uploadedFileName, setUploadedFileName] = useState<string>('');
  const [fixSuccess, setFixSuccess] = useState(false);

  // Chat modal state for assigned office
  const [activeChatCase, setActiveChatCase] = useState<CaseRequest | null>(null);
  const [chatInputText, setChatInputText] = useState<string>('');

  const actionRequiredCount = cases.filter(c => c.status === 'action_required').length;
  const inProgressCount = cases.filter(c => c.status !== 'completed' && c.status !== 'action_required').length;
  const completedCount = cases.filter(c => c.status === 'completed').length;

  const filteredCases = cases.filter(c => {
    if (filterTab === 'action_required') return c.status === 'action_required';
    if (filterTab === 'completed') return c.status === 'completed';
    if (filterTab === 'in_progress') return c.status !== 'completed' && c.status !== 'action_required';
    return true;
  });

  const handleCopyTrackingCode = (code: string) => {
    navigator.clipboard?.writeText(code);
    setCopiedCode(code);
    setTimeout(() => setCopiedCode(null), 2000);
  };

  const handleOpenFixModal = (c: CaseRequest) => {
    setFixingCase(c);
    setUploadedFilePreview(null);
    setUploadedFileName('');
    setFixSuccess(false);
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0];
      setUploadedFileName(file.name);
      setUploadedFilePreview(URL.createObjectURL(file));
    }
  };

  const handleSimulateSelectDemoFile = () => {
    setUploadedFileName('shenasname_scan_high_quality.jpg');
    setUploadedFilePreview('https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=600&q=80');
  };

  const handleExecuteFix = () => {
    if (fixingCase) {
      onFixCaseDocument(
        fixingCase.id, 
        uploadedFileName || 'تصویر شناسنامه جدید اسکن شده',
        uploadedFilePreview || 'file://fixed-doc.jpg'
      );
      setFixSuccess(true);
      setTimeout(() => {
        setFixSuccess(false);
        setFixingCase(null);
      }, 1400);
    }
  };

  const handleSendChatMessage = (e: React.FormEvent) => {
    e.preventDefault();
    if (!chatInputText.trim() || !activeChatCase) return;
    if (onSendMessage) {
      onSendMessage(activeChatCase.id, chatInputText.trim());
    }
    setChatInputText('');
  };

  const handleSendQuickPreset = (presetText: string) => {
    if (!activeChatCase || !onSendMessage) return;
    onSendMessage(activeChatCase.id, presetText);
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'action_required':
        return (
          <span className="inline-flex items-center gap-1 bg-amber-50 text-amber-800 text-[11px] font-black px-2.5 py-1 rounded-full border border-amber-300 shadow-2xs">
            <span className="w-2 h-2 rounded-full bg-amber-500 animate-ping" />
            <span>نیازمند اقدام و اصلاح</span>
          </span>
        );
      case 'completed':
        return (
          <span className="inline-flex items-center gap-1 bg-emerald-50 text-emerald-800 text-[11px] font-black px-2.5 py-1 rounded-full border border-emerald-300 shadow-2xs">
            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
            <span>تکمیل و تحویل شد</span>
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center gap-1 bg-blue-50 text-blue-800 text-[11px] font-black px-2.5 py-1 rounded-full border border-blue-300 shadow-2xs">
            <Clock className="w-3.5 h-3.5 text-blue-600" />
            <span>در حال انجام و بررسی</span>
          </span>
        );
    }
  };

  const currentCaseMessages = activeChatCase 
    ? messages.filter(m => m.caseId === activeChatCase.id)
    : [];

  return (
    <div className="space-y-4 pb-28">
      
      {/* ------------------------------------------------------------- */}
      {/* 1. TOP HEADER & FILTER BAR                                     */}
      {/* ------------------------------------------------------------- */}
      <div className="bg-white rounded-3xl p-4 shadow-xs border border-slate-200/90 space-y-3.5">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <div className="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center justify-center shrink-0 shadow-2xs">
              <FolderClock className="w-5 h-5" />
            </div>
            <div>
              <h2 className="text-sm sm:text-base font-black text-slate-900 leading-tight">
                پرونده‌ها و پیگیری درخواست‌ها
              </h2>
              <p className="text-[11px] text-slate-500 font-medium mt-0.5">
                رهگیری آنلاین مراحل رسیدگی و ارتباط با دفتر
              </p>
            </div>
          </div>

          <div className="text-left">
            <span className="inline-flex items-center gap-1 text-[11px] font-black text-slate-700 bg-slate-100 px-2.5 py-1 rounded-xl border border-slate-200/70">
              <span>{cases.length}</span>
              <span className="text-[10px] text-slate-500 font-medium">پرونده</span>
            </span>
          </div>
        </div>

        {/* Action Required Quick Callout if any */}
        {actionRequiredCount > 0 && filterTab !== 'action_required' && (
          <div 
            onClick={() => setFilterTab('action_required')}
            className="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300/80 rounded-2xl p-2.5 flex items-center justify-between cursor-pointer hover:border-amber-400 transition shadow-2xs"
          >
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0 text-xs font-black shadow-xs">
                !
              </div>
              <div className="text-xs text-amber-950 font-bold">
                <span>{actionRequiredCount} پرونده نیازمند اصلاح مدارک است</span>
              </div>
            </div>
            <div className="flex items-center gap-1 text-[11px] text-amber-800 font-black">
              <span>مشاهده و اصلاح</span>
              <ArrowLeft className="w-3.5 h-3.5" />
            </div>
          </div>
        )}

        {/* Scrollable Filter Chips */}
        <div className="flex items-center gap-2 overflow-x-auto no-scrollbar pt-1 text-xs">
          <button
            onClick={() => setFilterTab('all')}
            className={`px-3.5 py-2 rounded-2xl font-black whitespace-nowrap transition-all flex items-center gap-1.5 cursor-pointer ${
              filterTab === 'all'
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80'
            }`}
          >
            <span>همه</span>
            <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${filterTab === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-200 text-slate-700'}`}>
              {cases.length}
            </span>
          </button>

          <button
            onClick={() => setFilterTab('action_required')}
            className={`px-3.5 py-2 rounded-2xl font-black whitespace-nowrap flex items-center gap-1.5 transition-all cursor-pointer ${
              filterTab === 'action_required'
                ? 'bg-amber-600 text-white shadow-sm'
                : 'bg-amber-50 text-amber-900 border border-amber-200/80 hover:bg-amber-100'
            }`}
          >
            <AlertTriangle className="w-3.5 h-3.5 text-amber-500 shrink-0" />
            <span>نیازمند اصلاح</span>
            {actionRequiredCount > 0 && (
              <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${filterTab === 'action_required' ? 'bg-amber-800 text-white' : 'bg-amber-200 text-amber-900'}`}>
                {actionRequiredCount}
              </span>
            )}
          </button>

          <button
            onClick={() => setFilterTab('in_progress')}
            className={`px-3.5 py-2 rounded-2xl font-black whitespace-nowrap flex items-center gap-1.5 transition-all cursor-pointer ${
              filterTab === 'in_progress'
                ? 'bg-blue-600 text-white shadow-sm'
                : 'bg-blue-50 text-blue-800 border border-blue-200/80 hover:bg-blue-100'
            }`}
          >
            <Clock className="w-3.5 h-3.5 text-blue-500 shrink-0" />
            <span>در جریان</span>
            {inProgressCount > 0 && (
              <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${filterTab === 'in_progress' ? 'bg-blue-800 text-white' : 'bg-blue-200 text-blue-900'}`}>
                {inProgressCount}
              </span>
            )}
          </button>

          <button
            onClick={() => setFilterTab('completed')}
            className={`px-3.5 py-2 rounded-2xl font-black whitespace-nowrap flex items-center gap-1.5 transition-all cursor-pointer ${
              filterTab === 'completed'
                ? 'bg-emerald-600 text-white shadow-sm'
                : 'bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100'
            }`}
          >
            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 shrink-0" />
            <span>تکمیل شده</span>
            {completedCount > 0 && (
              <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${filterTab === 'completed' ? 'bg-emerald-800 text-white' : 'bg-emerald-200 text-emerald-900'}`}>
                {completedCount}
              </span>
            )}
          </button>
        </div>
      </div>

      {/* ------------------------------------------------------------- */}
      {/* 2. LIST OF CASE CARDS                                          */}
      {/* ------------------------------------------------------------- */}
      <div className="space-y-3.5">
        {filteredCases.length === 0 ? (
          <div className="bg-white rounded-3xl p-10 text-center border border-slate-200/90 text-slate-400 space-y-3 shadow-xs">
            <div className="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
              <Inbox className="w-6 h-6" />
            </div>
            <p className="text-xs font-black text-slate-700">پرونده‌ای در این دسته یافت نشد</p>
            <p className="text-[11px] text-slate-400">می‌توانید فیلتر انتخابی را تغییر داده یا از بخش خدمات درخواست جدید ثبت کنید.</p>
          </div>
        ) : (
          filteredCases.map((caseItem) => {
            const isExpanded = expandedCaseId === caseItem.id;
            const isActionRequired = caseItem.status === 'action_required';
            const caseMessagesCount = messages.filter(m => m.caseId === caseItem.id).length;

            return (
              <div 
                key={caseItem.id}
                className={`bg-white rounded-3xl border transition-all overflow-hidden shadow-xs ${
                  isActionRequired
                    ? 'border-amber-400/90 ring-4 ring-amber-500/10'
                    : 'border-slate-200/90 hover:border-slate-300'
                }`}
              >
                
                {/* Main Card Content */}
                <div className="p-4 sm:p-5 space-y-3.5">
                  
                  {/* Row 1: Header + Status Badge */}
                  <div className="flex items-start justify-between gap-2">
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        {getStatusBadge(caseItem.status)}
                      </div>
                      <h3 className="font-black text-sm sm:text-base text-slate-900 leading-snug pt-0.5">
                        {caseItem.serviceTitle}
                      </h3>
                    </div>

                    {/* Copyable Tracking Code Pill */}
                    <button
                      onClick={() => handleCopyTrackingCode(caseItem.trackingCode)}
                      title="برای کپی کد رهگیری کلیک کنید"
                      className="flex items-center gap-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-1.5 rounded-xl text-xs font-mono font-bold transition shrink-0 cursor-pointer shadow-2xs"
                    >
                      <span>{caseItem.trackingCode}</span>
                      {copiedCode === caseItem.trackingCode ? (
                        <Check className="w-3.5 h-3.5 text-emerald-600" />
                      ) : (
                        <Copy className="w-3.5 h-3.5 text-slate-400" />
                      )}
                    </button>
                  </div>

                  {/* HIGH-PRIORITY ACTION REQUIRED BANNER */}
                  {isActionRequired && caseItem.returnReason && (
                    <div className="bg-gradient-to-b from-amber-50 to-amber-100/50 border border-amber-300 rounded-2xl p-3.5 space-y-3 shadow-xs">
                      <div className="flex items-center gap-2 text-amber-950 font-black text-xs">
                        <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0" />
                        <span>علت عودت توسط کارشناس دفتر پیشخوان:</span>
                      </div>

                      <div className="bg-white/90 rounded-xl p-3 border border-amber-200/80 shadow-2xs">
                        <p className="text-xs text-amber-950 leading-relaxed font-medium">
                          «{caseItem.returnReason}»
                        </p>
                      </div>

                      {caseItem.requiredFixField && (
                        <div className="flex items-center gap-1.5 text-[11px] text-amber-900 font-bold bg-amber-200/50 px-2.5 py-1 rounded-lg border border-amber-300/60">
                          <FileEdit className="w-3.5 h-3.5 text-amber-700" />
                          <span>مدرک نیازمند اصلاح: {caseItem.requiredFixField}</span>
                        </div>
                      )}

                      {/* Full-width High-contrast Action Button */}
                      <button
                        onClick={() => handleOpenFixModal(caseItem)}
                        className="w-full bg-amber-600 hover:bg-amber-700 active:scale-[0.98] text-white font-black text-xs sm:text-sm py-2.5 rounded-xl shadow-md shadow-amber-600/25 flex items-center justify-center gap-2 transition cursor-pointer"
                      >
                        <UploadCloud className="w-4 h-4" />
                        <span>اصلاح و بارگذاری مجدد مدرک</span>
                      </button>
                    </div>
                  )}

                  {/* Micro-Stats Grid (دفتر پیشخوان، تاریخ ثبت، زمان تخمینی) */}
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-1 text-xs">
                    
                    {caseItem.assignedOffice && (
                      <div className="bg-slate-50 border border-slate-100 rounded-2xl p-2.5 col-span-2 sm:col-span-1">
                        <span className="text-[10px] text-slate-400 block mb-0.5">دفتر پیشخوان مجری</span>
                        <div className="flex items-center gap-1.5 font-bold text-slate-800 text-[11px] truncate">
                          <Building2 className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                          <span className="truncate">{caseItem.assignedOffice.name}</span>
                        </div>
                      </div>
                    )}

                    <div className="bg-slate-50 border border-slate-100 rounded-2xl p-2.5">
                      <span className="text-[10px] text-slate-400 block mb-0.5">تاریخ ثبت درخواست</span>
                      <div className="flex items-center gap-1.5 font-bold text-slate-800 text-[11px]">
                        <Calendar className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                        <span>{caseItem.createdAt}</span>
                      </div>
                    </div>

                    <div className="bg-slate-50 border border-slate-100 rounded-2xl p-2.5">
                      <span className="text-[10px] text-slate-400 block mb-0.5">زمان تخمینی تحویل</span>
                      <div className="flex items-center gap-1.5 font-bold text-slate-800 text-[11px]">
                        <Clock className="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                        <span>{caseItem.estimatedCompletion}</span>
                      </div>
                    </div>
                  </div>

                  {/* Direct Contact & Action Strip */}
                  <div className="pt-2 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap text-xs">
                    
                    {/* Left: Chat & Call Buttons */}
                    <div className="flex items-center gap-2">
                      {caseItem.assignedOffice && (
                        <>
                          <button
                            onClick={() => setActiveChatCase(caseItem)}
                            className="flex items-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 px-3 py-2 rounded-xl font-black text-xs transition cursor-pointer shadow-2xs active:scale-95"
                          >
                            <MessageSquare className="w-3.5 h-3.5 text-emerald-600" />
                            <span>گفتگو با باجه</span>
                            {caseMessagesCount > 0 && (
                              <span className="bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.2 rounded-full">
                                {caseMessagesCount}
                              </span>
                            )}
                          </button>

                          {caseItem.assignedOffice.phone && (
                            <a 
                              href={`tel:${caseItem.assignedOffice.phone}`}
                              className="flex items-center gap-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-2 rounded-xl font-bold text-xs transition shadow-2xs active:scale-95"
                              title="تماس تلفنی با دفتر"
                            >
                              <Phone className="w-3.5 h-3.5 text-slate-600" />
                              <span className="hidden sm:inline">تماس</span>
                            </a>
                          )}
                        </>
                      )}
                    </div>

                    {/* Right: Expand / Collapse Timeline Toggle */}
                    <button
                      onClick={() => setExpandedCaseId(isExpanded ? null : caseItem.id)}
                      className={`flex items-center gap-1 px-3 py-2 rounded-xl font-black text-xs transition cursor-pointer ${
                        isExpanded
                          ? 'bg-indigo-50 text-indigo-700 border border-indigo-200'
                          : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
                      }`}
                    >
                      <span>{isExpanded ? 'بستن رهگیری' : 'مشاهده مراحل'}</span>
                      {isExpanded ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
                    </button>
                  </div>

                </div>

                {/* ----------------------------------------------------------- */}
                {/* EXPANDED VISUAL PROCESS TIMELINE GRAPH                      */}
                {/* ----------------------------------------------------------- */}
                {isExpanded && (
                  <div className="bg-slate-50/90 border-t border-slate-200/80 p-4 sm:p-5 space-y-4">
                    <div className="flex items-center justify-between pb-2 border-b border-slate-200/60">
                      <span className="text-xs font-black text-slate-800 flex items-center gap-1.5">
                        <Sparkles className="w-4 h-4 text-indigo-600" />
                        گراف بصری مراحل رسیدگی به پرونده
                      </span>
                      <span className="text-[10px] text-slate-500 font-medium">
                        تکمیل: {caseItem.timeline.filter(t => t.status === 'done').length} از {caseItem.timeline.length} مرحله
                      </span>
                    </div>

                    {/* Step-by-step Stepper */}
                    <div className="relative pr-6 space-y-4 before:absolute before:right-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200">
                      {caseItem.timeline.map((step) => {
                        const isDone = step.status === 'done';
                        const isCurrent = step.status === 'current';
                        const isWarning = step.status === 'warning';

                        return (
                          <div key={step.id} className="relative group">
                            {/* Node Dot / Icon on Line */}
                            <div className={`absolute -right-6 top-0 w-6 h-6 rounded-full flex items-center justify-center ring-4 ring-slate-50 z-10 transition-all ${
                              isDone
                                ? 'bg-emerald-500 text-white shadow-2xs'
                                : isWarning
                                ? 'bg-amber-500 text-white animate-bounce shadow-md shadow-amber-500/30'
                                : isCurrent
                                ? 'bg-blue-600 text-white ring-blue-100 animate-pulse'
                                : 'bg-slate-300 text-slate-600'
                            }`}>
                              {isDone && <CheckCircle2 className="w-3.5 h-3.5" />}
                              {isWarning && <AlertTriangle className="w-3.5 h-3.5" />}
                              {isCurrent && <Clock className="w-3.5 h-3.5" />}
                              {!isDone && !isWarning && !isCurrent && <span className="w-2 h-2 rounded-full bg-white" />}
                            </div>

                            {/* Step Content Box */}
                            <div className={`p-3.5 rounded-2xl border transition-all ${
                              isWarning
                                ? 'bg-amber-50 border-amber-300 text-amber-950 shadow-2xs'
                                : isCurrent
                                ? 'bg-white border-blue-400 shadow-sm ring-2 ring-blue-500/10'
                                : isDone
                                ? 'bg-white border-slate-200/80 text-slate-800'
                                : 'bg-slate-100/60 border-slate-200/50 text-slate-400'
                            }`}>
                              <div className="flex items-center justify-between">
                                <h4 className="font-black text-xs text-slate-900">{step.title}</h4>
                                {step.timestamp && (
                                  <span className="text-[10px] text-slate-400 font-medium">{step.timestamp}</span>
                                )}
                              </div>

                              <p className="text-[11px] text-slate-600 mt-1 leading-relaxed">
                                {step.description}
                              </p>

                              {/* Officer Note */}
                              {step.officeNote && (
                                <div className="mt-2 pt-2 border-t border-slate-200/60 text-[11px] text-indigo-950 bg-indigo-50/70 p-2 rounded-xl flex items-center justify-between">
                                  <span>یادداشت کارشناس: {step.officeNote}</span>
                                </div>
                              )}
                            </div>
                          </div>
                        );
                      })}
                    </div>

                  </div>
                )}
              </div>
            );
          })
        )}
      </div>

      {/* ========================================================================= */}
      {/* MODAL 1: DIRECT CHAT WITH ASSIGNED OFFICE OPERATOR                        */}
      {/* ========================================================================= */}
      {activeChatCase && (
        <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-xs flex items-center justify-center p-3.5 sm:p-4 animate-in fade-in duration-200" dir="rtl">
          <div className="bg-white text-slate-900 w-full max-w-lg rounded-3xl shadow-2xl border border-slate-100 flex flex-col h-[580px] max-h-[90vh] overflow-hidden">
            
            {/* Chat Header */}
            <div className="p-3.5 sm:p-4 bg-slate-900 text-white flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <div className="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30 flex items-center justify-center">
                  <Building2 className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="font-extrabold text-xs sm:text-sm text-white leading-tight">
                    {activeChatCase.assignedOffice?.name || 'دفتر پیشخوان دولت'}
                  </h3>
                  <div className="flex items-center gap-1.5 text-[10px] sm:text-[11px] text-slate-300 mt-0.5">
                    <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>باجه پاسخگویی برخط • کد: {activeChatCase.trackingCode}</span>
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-1.5">
                {activeChatCase.assignedOffice?.phone && (
                  <a
                    href={`tel:${activeChatCase.assignedOffice.phone}`}
                    className="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 flex items-center justify-center transition"
                    title="تماس تلفنی"
                  >
                    <Phone className="w-4 h-4" />
                  </a>
                )}
                <button
                  onClick={() => setActiveChatCase(null)}
                  className="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 flex items-center justify-center transition cursor-pointer"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Service Context Bar */}
            <div className="bg-slate-100 px-4 py-2 border-b border-slate-200 flex items-center justify-between text-xs">
              <span className="font-bold text-slate-800 truncate">
                خدمت: {activeChatCase.serviceTitle}
              </span>
              <span className="text-[11px] text-slate-500 font-medium shrink-0">
                {activeChatCase.assignedOffice?.city || 'تهران'}
              </span>
            </div>

            {/* Chat Message Scroll Area */}
            <div className="flex-1 p-3.5 sm:p-4 overflow-y-auto space-y-3 bg-slate-50">
              {/* Security Tag */}
              <div className="bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl p-2 text-[11px] flex items-center gap-1.5 justify-center text-center">
                <ShieldCheck className="w-4 h-4 text-emerald-600 shrink-0" />
                <span>گفتگوی رسمی در بستر امن سامانه پیشخوانو ثبت می‌گردد.</span>
              </div>

              {currentCaseMessages.length === 0 ? (
                <div className="text-center py-10 space-y-2 text-slate-400">
                  <MessageSquare className="w-10 h-10 mx-auto text-slate-300" />
                  <p className="text-xs font-bold text-slate-600">هنوز پیامی برای این پرونده ردوبدل نشده است.</p>
                  <p className="text-[11px]">می‌توانید سوال یا توضیحات خود را برای کارشناس باجه ارسال فرمایید.</p>
                </div>
              ) : (
                currentCaseMessages.map((msg) => {
                  const isCitizen = msg.sender === 'citizen';
                  return (
                    <div
                      key={msg.id}
                      className={`flex flex-col ${isCitizen ? 'items-end' : 'items-start'}`}
                    >
                      <div
                        className={`max-w-[85%] rounded-2xl p-3 text-xs leading-relaxed shadow-2xs ${
                          isCitizen
                            ? 'bg-emerald-600 text-white rounded-br-xs'
                            : 'bg-white text-slate-800 border border-slate-200 rounded-bl-xs'
                        }`}
                      >
                        <div className="text-[10px] font-bold opacity-75 mb-0.5">
                          {msg.senderName}
                        </div>
                        <p>{msg.text}</p>
                        <div
                          className={`text-[10px] mt-1 flex items-center gap-1 ${
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
                })
              )}
            </div>

            {/* Quick Inquiry Chips */}
            <div className="px-3 py-2 bg-slate-100 border-t border-slate-200/80 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
              <span className="text-[10px] text-slate-500 shrink-0 font-bold">پیشنهاد سریع:</span>
              <button
                type="button"
                onClick={() => handleSendQuickPreset('سلام، پرونده من در چه مرحله‌ای است؟')}
                className="bg-white hover:bg-slate-200 text-slate-700 text-[10px] font-medium px-2.5 py-1 rounded-lg border border-slate-200 shrink-0 transition"
              >
                وضعیت پرونده؟
              </button>
              <button
                type="button"
                onClick={() => handleSendQuickPreset('آیا مدارک ارسالی تایید شده است؟')}
                className="bg-white hover:bg-slate-200 text-slate-700 text-[10px] font-medium px-2.5 py-1 rounded-lg border border-slate-200 shrink-0 transition"
              >
                تایید مدارک؟
              </button>
              <button
                type="button"
                onClick={() => handleSendQuickPreset('تحویل مدرک حضوری است یا با پست ارسال می‌شود؟')}
                className="bg-white hover:bg-slate-200 text-slate-700 text-[10px] font-medium px-2.5 py-1 rounded-lg border border-slate-200 shrink-0 transition"
              >
                شیوه تحویل؟
              </button>
            </div>

            {/* Chat Input Bar */}
            <form onSubmit={handleSendChatMessage} className="p-2.5 sm:p-3 bg-white border-t border-slate-200 flex items-center gap-2">
              <input
                type="text"
                value={chatInputText}
                onChange={(e) => setChatInputText(e.target.value)}
                placeholder="پیام به کارشناس دفتر پیشخوان..."
                className="flex-1 bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:bg-white focus:outline-none focus:border-emerald-600 transition"
              />
              <button
                type="submit"
                disabled={!chatInputText.trim()}
                className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white p-2.5 rounded-xl transition shadow flex items-center justify-center cursor-pointer"
              >
                <Send className="w-4 h-4 rotate-180" />
              </button>
            </form>

          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODAL 2: FIXING & RE-UPLOADING DOCUMENT                                   */}
      {/* ========================================================================= */}
      {fixingCase && (
        <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-200" dir="rtl">
          <div className="bg-white text-slate-900 w-full max-w-md rounded-3xl p-5 sm:p-6 shadow-2xl border border-slate-100 relative">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
              <div className="flex items-center gap-2">
                <div className="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                  <RotateCcw className="w-4 h-4" />
                </div>
                <h3 className="text-sm font-black text-slate-800">اصلاح و بارگذاری مجدد مدرک</h3>
              </div>
              <button 
                onClick={() => setFixingCase(null)}
                className="text-slate-400 hover:text-slate-600 p-1 cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {fixSuccess ? (
              <div className="text-center py-6">
                <div className="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 animate-bounce">
                  <CheckCircle2 className="w-8 h-8" />
                </div>
                <h4 className="font-black text-slate-800 text-base">مدرک اصلاح شده با موفقیت ارسال شد</h4>
                <p className="text-xs text-slate-500 mt-1">پرونده در اولویت بررسی کارشناس دفتر قرار گرفت.</p>
              </div>
            ) : (
              <div className="space-y-4 text-xs">
                <div className="bg-amber-50 border border-amber-200 rounded-2xl p-3 text-amber-900 space-y-1">
                  <span className="font-bold block">دلیل اعلام شده توسط دفتر:</span>
                  <p className="text-[11px] leading-relaxed text-amber-800 font-medium">{fixingCase.returnReason}</p>
                </div>

                {/* Upload Area */}
                <div>
                  <label className="font-bold text-slate-700 mb-1.5 block">
                    انتخاب فایل / تصویر باکیفیت جدید:
                  </label>
                  
                  <div className="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-5 text-center bg-slate-50/70 hover:bg-emerald-50/30 transition-all cursor-pointer relative">
                    <input 
                      type="file" 
                      accept="image/*,.pdf" 
                      onChange={handleFileChange}
                      className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                    />
                    <UploadCloud className="w-8 h-8 text-slate-400 mx-auto mb-2" />
                    <span className="font-bold text-slate-700 block">برای انتخاب یا گرفتن عکس لمس کنید</span>
                    <span className="text-[10px] text-slate-400 mt-0.5 block">فرمت‌های مجاز: JPG, PNG, PDF (حداکثر ۱۰ مگابایت)</span>
                  </div>

                  {/* Fast simulation button for preview convenience */}
                  {!uploadedFileName && (
                    <button
                      type="button"
                      onClick={handleSimulateSelectDemoFile}
                      className="mt-2 text-indigo-600 hover:text-indigo-800 text-[11px] font-bold underline block cursor-pointer"
                    >
                      یا انتخاب فایل اسکن شده نمونه (دمو)
                    </button>
                  )}

                  {uploadedFileName && (
                    <div className="mt-2 bg-emerald-50 border border-emerald-200 rounded-xl p-2.5 flex items-center justify-between text-emerald-900">
                      <span className="font-semibold text-[11px] truncate">{uploadedFileName}</span>
                      <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                    </div>
                  )}
                </div>

                <div className="bg-slate-50 p-3 rounded-xl text-slate-600 text-[11px] leading-relaxed">
                  💡 <strong>توجه:</strong> نوبت شما در صف بررسی حفظ شده و مدرک جدید مستقیماً به باجه رسیدگی‌کننده ارسال می‌شود.
                </div>

                <button
                  onClick={handleExecuteFix}
                  disabled={!uploadedFileName}
                  className={`w-full py-3.5 rounded-2xl font-black text-xs sm:text-sm shadow-md transition-all ${
                    uploadedFileName
                      ? 'bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white shadow-emerald-600/25 cursor-pointer'
                      : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                  }`}
                >
                  ارسال مجدد به کارشناس دفتر
                </button>
              </div>
            )}
          </div>
        </div>
      )}

    </div>
  );
};

