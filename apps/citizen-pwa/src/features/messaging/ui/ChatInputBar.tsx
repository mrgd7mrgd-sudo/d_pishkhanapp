import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';

export interface ChatInputBarProps {
  onSendMessage: (text: string) => void;
  disabled?: boolean | undefined;
}

export function ChatInputBar({
  onSendMessage,
  disabled = false,
}: ChatInputBarProps): React.JSX.Element {
  const { t } = useTranslation();
  const [text, setText] = useState<string>('');

  const handleSend = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    const trimmed = text.trim();
    if (!trimmed || disabled) return;
    onSendMessage(trimmed);
    setText('');
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  return (
    <form
      onSubmit={handleSend}
      className="p-3 bg-white/90 dark:bg-slate-900/90 border-t border-slate-200 dark:border-slate-800 flex items-end gap-2"
    >
      <div className="flex-1 relative">
        <label htmlFor="chat-message-input" className="sr-only">
          {t('messaging.input_placeholder')}
        </label>
        <textarea
          id="chat-message-input"
          value={text}
          onChange={(e) => setText(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder={t('messaging.input_placeholder')}
          rows={1}
          disabled={disabled}
          className="w-full resize-none max-h-24 px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all placeholder:text-slate-400"
        />
      </div>

      <button
        type="submit"
        disabled={disabled || !text.trim()}
        data-testid="chat-send-button"
        aria-label={t('messaging.send_button')}
        className="px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold transition-colors flex items-center gap-1.5 shadow-sm cursor-pointer"
      >
        <span>{t('messaging.send_button')}</span>
        <span aria-hidden="true">↑</span>
      </button>
    </form>
  );
}
