import React from 'react';
import { Badge, Button } from '@pishkhan/ui-kit';
import { Star, MessageCircle, CheckCircle2, CornerDownLeft } from 'lucide-react';
import type { DeskReview } from '../types';

export interface ReviewItemCardProps {
  review: DeskReview;
  isManager: boolean;
  onOpenReply: (review: DeskReview) => void;
}

export const ReviewItemCard: React.FC<ReviewItemCardProps> = ({
  review,
  isManager,
  onOpenReply,
}) => {
  return (
    <article
      data-testid={`review-card-${review.id}`}
      className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-3"
    >
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
        <div className="flex items-center gap-2.5">
          <div className="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center font-bold text-xs">
            {review.citizen_name ? review.citizen_name.slice(0, 1) : 'ش'}
          </div>
          <div>
            <div className="flex items-center gap-1.5">
              <span className="text-sm font-semibold text-slate-900 dark:text-white">
                {review.citizen_name || 'شهروند محترم'}
              </span>
              {review.is_verified_citizen && (
                <span className="inline-flex items-center text-[10px] text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded-sm">
                  <CheckCircle2 className="w-3 h-3 me-0.5" aria-hidden="true" />
                  مراجعه تأییدشده
                </span>
              )}
            </div>
            {review.service_title && (
              <p className="text-xs text-slate-500">{review.service_title}</p>
            )}
          </div>
        </div>

        <div className="flex items-center gap-2 self-start sm:self-auto">
          <div
            role="img"
            aria-label={`امتیاز ${review.rating} از ۵`}
            className="flex items-center gap-0.5 text-amber-500"
          >
            {[1, 2, 3, 4, 5].map((star) => (
              <Star
                key={star}
                className={`w-4 h-4 ${star <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300 dark:text-slate-700'}`}
                aria-hidden="true"
              />
            ))}
          </div>
          <span className="text-xs font-mono text-slate-500">
            {review.created_at ? new Date(review.created_at).toLocaleDateString('fa-IR') : ''}
          </span>
        </div>
      </div>

      <p className="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
        {review.comment}
      </p>

      {review.tags && review.tags.length > 0 && (
        <div className="flex flex-wrap gap-1.5 pt-1">
          {review.tags.map((tag) => (
            <span
              key={tag}
              className="text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded-md"
            >
              #{tag}
            </span>
          ))}
        </div>
      )}

      {review.manager_reply && (
        <div className="mt-3 p-3 rounded-lg bg-blue-50/70 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/50 space-y-1">
          <div className="flex items-center gap-1.5 text-xs font-semibold text-blue-700 dark:text-blue-400">
            <CornerDownLeft className="w-3.5 h-3.5" aria-hidden="true" />
            <span>پاسخ مدیر دفتر</span>
            {review.manager_replied_at && (
              <span className="text-[10px] text-slate-400 font-mono">
                ({new Date(review.manager_replied_at).toLocaleDateString('fa-IR')})
              </span>
            )}
          </div>
          <p className="text-xs text-slate-700 dark:text-slate-300 leading-relaxed ps-5">
            {review.manager_reply}
          </p>
        </div>
      )}

      <div className="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
        <div>
          {review.manager_reply ? (
            <Badge variant="success">پاسخ داده شده</Badge>
          ) : (
            <Badge variant="warning">در انتظار پاسخ</Badge>
          )}
        </div>

        {isManager && (
          <Button
            variant="secondary"
            size="sm"
            onClick={() => onOpenReply(review)}
            data-testid={`reply-btn-${review.id}`}
          >
            <MessageCircle className="w-3.5 h-3.5 me-1" aria-hidden="true" />
            <span>{review.manager_reply ? 'ویرایش پاسخ' : 'پاسخ مدیر'}</span>
          </Button>
        )}
      </div>
    </article>
  );
};
