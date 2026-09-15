import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Skeleton } from '@pishkhan/ui-kit';
import { messagingApi } from '../api/messagingApi';
import type { NotificationItem } from '../types';

type NotificationFilter = 'all' | 'unread' | 'notices';

export function NotificationsView(): React.JSX.Element {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [filter, setFilter] = useState<NotificationFilter>('all');

  const { data, isLoading } = useQuery({
    queryKey: ['notifications'],
    queryFn: () => messagingApi.getNotifications(),
  });

  const markReadMutation = useMutation({
    mutationFn: (id: string) => messagingApi.markNotificationRead(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['notifications'] });
    },
  });

  const notifications = data?.items ?? [];
  const unreadCount = data?.unread_count ?? 0;

  const filteredItems = notifications.filter((item) => {
    if (filter === 'unread') return !item.read_at;
    if (filter === 'notices') return item.type.includes('notice') || item.type.includes('official');
    return true;
  });

  return (
    <div className="max-w-3xl mx-auto px-4 py-6 space-y-6">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-xl font-bold text-slate-900 dark:text-slate-100">
              {t('messaging.notifications_title')}
            </h1>
            {unreadCount > 0 && (
              <span
                data-testid="unread-badge"
                className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-xs"
              >
                {unreadCount} {t('messaging.tab_unread')}
              </span>
            )}
          </div>
          <p className="text-xs text-slate-500 mt-1">
            {t('messaging.notifications_subtitle')}
          </p>
        </div>
      </div>

      {/* Tabs */}
      <div
        role="tablist"
        aria-label={t('messaging.notifications_title')}
        className="flex items-center gap-2 p-1 rounded-2xl bg-slate-100 dark:bg-slate-800"
      >
        <button
          type="button"
          role="tab"
          aria-selected={filter === 'all'}
          onClick={() => setFilter('all')}
          className={`flex-1 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer ${
            filter === 'all'
              ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
          }`}
        >
          {t('messaging.tab_all')}
        </button>

        <button
          type="button"
          role="tab"
          aria-selected={filter === 'unread'}
          onClick={() => setFilter('unread')}
          className={`flex-1 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
            filter === 'unread'
              ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
          }`}
        >
          <span>{t('messaging.tab_unread')}</span>
          {unreadCount > 0 && (
            <span className="w-2 h-2 rounded-full bg-emerald-500" />
          )}
        </button>

        <button
          type="button"
          role="tab"
          aria-selected={filter === 'notices'}
          onClick={() => setFilter('notices')}
          className={`flex-1 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer ${
            filter === 'notices'
              ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
          }`}
        >
          {t('messaging.tab_notices')}
        </button>
      </div>

      {/* Notification List */}
      <div className="space-y-3">
        {isLoading ? (
          <div className="space-y-3">
            <Skeleton className="h-20 w-full rounded-2xl" />
            <Skeleton className="h-20 w-full rounded-2xl" />
            <Skeleton className="h-20 w-full rounded-2xl" />
          </div>
        ) : filteredItems.length === 0 ? (
          <div className="p-12 text-center rounded-2xl bg-white/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800">
            <p className="text-sm text-slate-400 dark:text-slate-500">
              {t('messaging.empty_notifications')}
            </p>
          </div>
        ) : (
          filteredItems.map((item: NotificationItem) => (
            <article
              key={item.id}
              className={`p-4 rounded-2xl border transition-all ${
                item.read_at
                  ? 'bg-white/60 dark:bg-slate-900/60 border-slate-200/80 dark:border-slate-800'
                  : 'bg-white dark:bg-slate-900 border-emerald-500/40 shadow-xs ring-1 ring-emerald-500/10'
              }`}
            >
              <div className="flex items-start justify-between gap-3">
                <div className="space-y-1">
                  <div className="flex items-center gap-2">
                    {!item.read_at && (
                      <span className="w-2 h-2 rounded-full bg-emerald-500 shrink-0" />
                    )}
                    <h2 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                      {item.title}
                    </h2>
                    {(item.type.includes('notice') || item.type.includes('official')) && (
                      <span className="text-[10px] px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 font-semibold border border-blue-200 dark:border-blue-800">
                        {t('messaging.official_notice_badge')}
                      </span>
                    )}
                  </div>
                  <p className="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    {item.body}
                  </p>
                  <time className="text-[10px] text-slate-400 block pt-1">
                    {new Date(item.created_at).toLocaleDateString('fa-IR')}
                  </time>
                </div>

                {!item.read_at && (
                  <button
                    type="button"
                    onClick={() => markReadMutation.mutate(item.id)}
                    className="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-semibold whitespace-nowrap cursor-pointer hover:underline"
                  >
                    {t('messaging.mark_as_read')}
                  </button>
                )}
              </div>
            </article>
          ))
        )}
      </div>
    </div>
  );
}
