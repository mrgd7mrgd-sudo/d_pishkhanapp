import React from 'react';
import { Sparkles, RefreshCw } from 'lucide-react';

interface ChatHeaderProps {
  onNewConversation: () => void;
}

export const ChatHeader: React.FC<ChatHeaderProps> = ({ onNewConversation }) => {
  return (
    <header className="flex items-center justify-between px-4 py-3 rounded-2xl bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl border border-white/30 dark:border-slate-800 shadow-sm mb-3">
      <div className="flex items-center gap-2.5">
        <div className="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-600 flex items-center justify-center">
          <Sparkles className="w-4 h-4" />
        </div>
        <div>
          <h1 className="text-sm font-bold text-slate-800 dark:text-slate-100">
            دستیار هوشمند پیشخوان
          </h1>
          <p className="text-[10px] text-slate-500 dark:text-slate-400">
            پاسخگوی ۲۴ ساعته قوانین، خدمات و نوبت‌دهی
          </p>
        </div>
      </div>

      <button
        type="button"
        onClick={onNewConversation}
        title="گفتگو جدید"
        aria-label="شروع گفتگو جدید"
        className="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
      >
        <RefreshCw className="w-4 h-4" />
      </button>
    </header>
  );
};
