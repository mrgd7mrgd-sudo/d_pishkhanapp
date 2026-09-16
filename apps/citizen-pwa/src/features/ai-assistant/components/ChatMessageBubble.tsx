import React from 'react';
import type { AiChatMessage, AiSuggestedAction } from '../types';
import { SuggestedActionsList } from './SuggestedActionsList';
import { Volume2, Sparkles, User } from 'lucide-react';

interface ChatMessageBubbleProps {
  message: AiChatMessage;
  onActionClick?: ((action: AiSuggestedAction) => void) | undefined;
}

export const ChatMessageBubble: React.FC<ChatMessageBubbleProps> = ({
  message,
  onActionClick,
}) => {
  const isAssistant = message.role === 'assistant';

  const handleSpeak = () => {
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
      window.speechSynthesis.cancel();
      const utterance = new SpeechSynthesisUtterance(message.content);
      utterance.lang = 'fa-IR';
      window.speechSynthesis.speak(utterance);
    }
  };

  return (
    <div
      data-testid={`chat-message-${message.role}`}
      className={`flex items-start gap-2.5 my-3.5 ${isAssistant ? 'justify-start' : 'justify-end'}`}
    >
      {isAssistant && (
        <div className="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 shrink-0">
          <Sparkles className="w-4 h-4" />
        </div>
      )}

      <div
        className={`max-w-[85%] sm:max-w-[75%] rounded-2xl p-4 shadow-sm transition-all duration-200 ${
          isAssistant
            ? 'bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-white/40 dark:border-slate-700/50 text-slate-800 dark:text-slate-100 rounded-tr-sm'
            : 'bg-gradient-to-l from-emerald-600 to-teal-600 text-white rounded-tl-sm shadow-emerald-600/20'
        }`}
      >
        <div className="text-sm leading-relaxed whitespace-pre-wrap font-normal">
          {message.content}
          {message.isStreaming && (
            <span className="inline-block w-1.5 h-4 ml-1 bg-emerald-500 animate-pulse align-middle" />
          )}
        </div>

        {isAssistant && (
          <>
            <SuggestedActionsList
              actions={message.suggested_actions}
              onActionClick={onActionClick}
            />

            {typeof window !== 'undefined' && 'speechSynthesis' in window && (
              <div className="flex justify-end mt-2">
                <button
                  type="button"
                  onClick={handleSpeak}
                  aria-label="خواندن پیام صوتی"
                  className="p-1 rounded-md text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                >
                  <Volume2 className="w-3.5 h-3.5" />
                </button>
              </div>
            )}
          </>
        )}
      </div>

      {!isAssistant && (
        <div className="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 shrink-0">
          <User className="w-4 h-4" />
        </div>
      )}
    </div>
  );
};
