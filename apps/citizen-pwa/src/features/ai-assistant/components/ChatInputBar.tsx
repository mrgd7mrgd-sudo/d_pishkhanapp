import React from 'react';
import { VoiceButton } from './VoiceButton';
import { Send } from 'lucide-react';

interface ChatInputBarProps {
  inputValue: string;
  onInputChange: (val: string) => void;
  onSend: () => void;
  isProcessing: boolean;
  isSupported: boolean;
  isRecording: boolean;
  duration: number;
  onVoiceToggle: () => void;
}

export const ChatInputBar: React.FC<ChatInputBarProps> = ({
  inputValue,
  onInputChange,
  onSend,
  isProcessing,
  isSupported,
  isRecording,
  duration,
  onVoiceToggle,
}) => {
  return (
    <footer className="mt-2 p-2 rounded-2xl bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xl border border-white/40 dark:border-slate-800 shadow-lg">
      <div className="flex items-center gap-2">
        <VoiceButton
          isSupported={isSupported}
          isRecording={isRecording}
          duration={duration}
          onToggle={onVoiceToggle}
          disabled={isProcessing}
        />

        <input
          type="text"
          data-testid="ai-chat-input"
          value={inputValue}
          onChange={(e) => onInputChange(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
              e.preventDefault();
              onSend();
            }
          }}
          placeholder="پرسش خود را بنویسید (مثلاً: مدارک کارت ملی)..."
          disabled={isProcessing || isRecording}
          className="flex-1 bg-transparent px-3 py-2 text-sm text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none"
        />

        <button
          type="button"
          data-testid="ai-send-btn"
          onClick={onSend}
          aria-label="ارسال پیام"
          disabled={!inputValue.trim() || isProcessing || isRecording}
          className="flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white transition-all duration-200 active:scale-95 shadow-md shadow-emerald-600/20"
        >
          <Send className="w-4 h-4" />
        </button>
      </div>
    </footer>
  );
};
