import React, { useState } from 'react';
import { Button } from '@pishkhan/ui-kit';
import { X, Send, MessageSquare } from 'lucide-react';
import type { DeskReview } from '../types';

export interface ReplyReviewModalProps {
  review: DeskReview;
  onClose: () => void;
  onSubmit: (id: string, replyText: string) => Promise<void>;
  isSubmitting: boolean;
}

export const ReplyReviewModal: React.FC<ReplyReviewModalProps> = ({
  review,
  onClose,
  onSubmit,
  isSubmitting,
}) => {
  const [replyText, setReplyText] = useState(review.manager_reply ?? '');
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!replyText.trim()) {
      setError('متن پاسخ به نظر نمی‌تواند خالی باشد.');
      return;
    }
    setError(null);
    try {
      await onSubmit(review.id, replyText.trim());
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'خطا در ثبت پاسخ.';
      setError(message);
    }
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="reply-modal-title"
    >
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
        <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
          <div className="flex items-center gap-2">
            <MessageSquare className="w-5 h-5 text-blue-600" aria-hidden="true" />
            <h2 id="reply-modal-title" className="text-base font-bold text-slate-900 dark:text-white">
              پاسخ مدیر دفتر به نظر شهروند
            </h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1"
            aria-label="بستن پنجره"
          >
            <X className="w-5 h-5" aria-hidden="true" />
          </button>
        </div>

        <div className="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg text-sm space-y-1">
          <div className="flex justify-between text-xs text-slate-500">
            <span>{review.citizen_name || 'شهروند محترم'}</span>
            <span>امتیاز: {review.rating} از ۵</span>
          </div>
          <p className="text-slate-700 dark:text-slate-300 italic">
            «{review.comment}»
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="reply-text-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              متن پاسخ رسمی مدیر دفتر <span className="text-rose-500">*</span>
            </label>
            <textarea
              id="reply-text-input"
              rows={4}
              value={replyText}
              onChange={(e) => setReplyText(e.target.value)}
              placeholder="پاسخ رسمی دفتر به دیدگاه و تجربه مراجعه شهروند..."
              className="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-hidden"
              required
            />
          </div>

          {error && (
            <div role="alert" className="p-2.5 rounded-md bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs">
              {error}
            </div>
          )}

          <div className="flex items-center justify-end gap-2 pt-2">
            <Button variant="secondary" size="sm" type="button" onClick={onClose} disabled={isSubmitting}>
              انصراف
            </Button>
            <Button variant="primary" size="sm" type="submit" disabled={isSubmitting}>
              <Send className="w-4 h-4 me-1.5" aria-hidden="true" />
              <span>{isSubmitting ? 'در حال ثبت...' : 'ثبت پاسخ'}</span>
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
