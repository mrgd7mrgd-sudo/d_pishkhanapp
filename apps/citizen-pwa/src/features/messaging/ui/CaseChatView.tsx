import React, { useRef, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Skeleton } from '@pishkhan/ui-kit';
import { useCaseChat } from '../model/useCaseChat';
import { ChatMessageBubble } from './ChatMessageBubble';
import { ChatInputBar } from './ChatInputBar';

export interface CaseChatViewProps {
  caseIdOverride?: string | undefined;
}

export function CaseChatView({ caseIdOverride }: CaseChatViewProps): React.JSX.Element {
  const { trackingCode } = useParams<{ trackingCode: string }>();
  const { t } = useTranslation();
  const caseId = caseIdOverride || trackingCode || '';

  const {
    messages,
    isLoading,
    sendMessage,
    retryMessage,
    removeFailedMessage,
    isSlowUpdateMode,
    pendingOfflineCount,
  } = useCaseChat({ caseId });

  const messagesEndRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView?.({ behavior: 'smooth' });
  }, [messages.length]);

  return (
    <div className="flex flex-col h-[calc(100dvh-4rem)] max-w-2xl mx-auto bg-slate-50/50 dark:bg-slate-950/50 border-x border-slate-200 dark:border-slate-800">
      {/* Header */}
      <header className="px-4 py-3 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Link
            to={trackingCode ? `/cases/${trackingCode}` : '/cases'}
            className="p-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium transition-colors"
          >
            ← {t('messaging.back_to_case')}
          </Link>
          <div>
            <h1 className="text-sm font-bold text-slate-900 dark:text-slate-100">
              {t('messaging.chat_title')}
            </h1>
            {trackingCode && (
              <p className="text-[11px] font-mono text-slate-500">
                {t('messaging.case_label')}: {trackingCode}
              </p>
            )}
          </div>
        </div>

        {isSlowUpdateMode && (
          <span
            data-testid="chat-slow-update-badge"
            className="text-[11px] px-2 py-0.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-medium"
          >
            {t('cases.slow_update_mode')}
          </span>
        )}
      </header>

      {/* Offline Alert Banner */}
      {pendingOfflineCount > 0 && (
        <div
          role="status"
          aria-live="polite"
          data-testid="chat-offline-banner"
          className="px-4 py-2 bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 text-xs flex items-center gap-2"
        >
          <span>⏳</span>
          <span>{t('messaging.offline_notice')}</span>
          <span className="font-mono font-bold">({pendingOfflineCount})</span>
        </div>
      )}

      {/* Message Stream */}
      <main
        aria-label={t('messaging.chat_title')}
        className="flex-1 overflow-y-auto p-4 space-y-2"
      >
        <div role="log" aria-live="polite" aria-label={t('messaging.chat_title')}>
          {isLoading ? (
            <div className="space-y-4 py-4">
              <Skeleton className="h-12 w-3/4 rounded-2xl" />
              <Skeleton className="h-12 w-2/3 mr-auto rounded-2xl" />
              <Skeleton className="h-14 w-4/5 rounded-2xl" />
            </div>
          ) : messages.length === 0 ? (
            <div className="h-full flex items-center justify-center text-center p-8 text-slate-400 dark:text-slate-500 text-sm">
              <p>{t('messaging.empty_messages')}</p>
            </div>
          ) : (
            messages.map((msg) => (
              <ChatMessageBubble
                key={msg.id}
                message={msg}
                onRetry={retryMessage}
                onDismiss={removeFailedMessage}
              />
            ))
          )}
          <div ref={messagesEndRef} />
        </div>
      </main>

      {/* Input Bar */}
      <ChatInputBar onSendMessage={sendMessage} />
    </div>
  );
}
