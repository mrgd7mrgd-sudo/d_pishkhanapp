import React from 'react';
import { useTranslation } from 'react-i18next';
import type { CaseMessage } from '../types';

export interface ChatMessageBubbleProps {
  message: CaseMessage;
  onRetry?: ((id: string) => void) | undefined;
  onDismiss?: ((id: string) => void) | undefined;
}

export function ChatMessageBubble({
  message,
  onRetry,
  onDismiss,
}: ChatMessageBubbleProps): React.JSX.Element {
  const { t } = useTranslation();
  const isCitizen = message.sender_type === 'citizen';
  const isSystem = message.sender_type === 'system';

  if (isSystem) {
    return (
      <div className="flex justify-center my-3">
        <div
          role="status"
          className="text-xs px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 text-center max-w-sm"
        >
          {message.body}
        </div>
      </div>
    );
  }

  return (
    <div className={`flex flex-col mb-3 ${isCitizen ? 'items-end' : 'items-start'}`}>
      <div className="flex items-center gap-2 mb-1 px-1">
        <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
          {message.sender_name}
        </span>
        <span className="text-[10px] text-slate-400 dark:text-slate-500">
          {new Date(message.created_at).toLocaleTimeString('fa-IR', {
            hour: '2-digit',
            minute: '2-digit',
          })}
        </span>
      </div>

      <div
        className={`max-w-[85%] sm:max-w-md px-4 py-2.5 rounded-2xl text-sm leading-relaxed ${
          isCitizen
            ? 'bg-emerald-600 text-white rounded-tr-xs shadow-xs'
            : 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 border border-slate-200 dark:border-slate-700 rounded-tl-xs shadow-xs'
        }`}
      >
        <p className="whitespace-pre-wrap break-words">{message.body}</p>
      </div>

      {message.status && (
        <div className="flex items-center gap-1.5 mt-1 px-1 text-[11px]">
          {message.status === 'sending' && (
            <span className="text-slate-400 flex items-center gap-1 animate-pulse">
              <span>⏳</span>
              <span>{t('messaging.sending')}</span>
            </span>
          )}

          {message.status === 'pending_offline' && (
            <span
              data-testid="status-pending-offline"
              className="text-amber-600 dark:text-amber-400 flex items-center gap-1 font-medium"
            >
              <span>⌛</span>
              <span>{t('messaging.pending_offline')}</span>
            </span>
          )}

          {message.status === 'failed' && (
            <div className="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-medium">
              <span data-testid="status-failed">⚠️ {t('messaging.failed_send')}</span>
              {onRetry && (
                <button
                  type="button"
                  onClick={() => onRetry(message.id)}
                  className="underline hover:text-rose-700 text-[11px] font-semibold cursor-pointer"
                >
                  {t('messaging.retry')}
                </button>
              )}
              {onDismiss && (
                <button
                  type="button"
                  onClick={() => onDismiss(message.id)}
                  className="text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"
                  aria-label="حذف پیام ناموفق"
                >
                  ✕
                </button>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
