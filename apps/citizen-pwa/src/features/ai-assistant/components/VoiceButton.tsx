import React from 'react';
import { Mic, MicOff } from 'lucide-react';

interface VoiceButtonProps {
  isSupported: boolean;
  isRecording: boolean;
  duration: number;
  onToggle: () => void;
  disabled?: boolean;
}

export const VoiceButton: React.FC<VoiceButtonProps> = ({
  isSupported,
  isRecording,
  duration,
  onToggle,
  disabled = false,
}) => {
  // Fallback §8.1.5: If MediaRecorder is not supported, hide button and show text fallback
  if (!isSupported) {
    return (
      <div
        data-testid="voice-fallback"
        className="text-[11px] text-slate-400 dark:text-slate-500 px-2 py-1 select-none"
      >
        ضبط صدا در مرورگر شما پشتیبانی نمی‌شود، لطفاً پیام خود را بنویسید.
      </div>
    );
  }

  return (
    <button
      type="button"
      data-testid="voice-record-btn"
      onClick={onToggle}
      disabled={disabled}
      aria-label={isRecording ? 'توقف ضبط صدا' : 'شروع ضبط صدا'}
      className={`relative flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium transition-all duration-300 ${
        isRecording
          ? 'bg-rose-500/20 text-rose-600 border border-rose-500/40 shadow-lg shadow-rose-500/20 animate-pulse'
          : 'bg-white/40 dark:bg-slate-800/40 hover:bg-emerald-500/10 hover:text-emerald-600 border border-white/20 dark:border-slate-700/40 text-slate-700 dark:text-slate-200 backdrop-blur-md'
      }`}
    >
      {isRecording ? (
        <>
          <MicOff className="w-4 h-4 text-rose-500" />
          <span className="text-xs tabular-nums text-rose-600 font-bold">
            {duration} ثانیه
          </span>
        </>
      ) : (
        <Mic className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
      )}
    </button>
  );
};
