import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { MessageSquare, ShieldCheck, RefreshCw } from 'lucide-react';
import { Button } from '@pishkhan/ui-kit';
import { useOperatorAuthStore } from '@/features/auth/model/useOperatorAuthStore';
import { reviewsApi } from '../api/reviewsApi';
import { FeedbackTab } from '../components/FeedbackTab';
import { SlaQualityTab } from '../components/SlaQualityTab';
import { ReplyReviewModal } from '../components/ReplyReviewModal';
import type { DeskReview, ReviewFilter, ReviewSubTab } from '../types';

export const ReviewsPage: React.FC = () => {
  const [activeTab, setActiveTab] = useState<ReviewSubTab>('feedback');
  const [filter, setFilter] = useState<ReviewFilter>('all');
  const [search, setSearch] = useState<string>('');
  const [selectedReviewForReply, setSelectedReviewForReply] = useState<DeskReview | null>(null);
  const [feedbackToast, setFeedbackToast] = useState<string | null>(null);

  const queryClient = useQueryClient();
  const operator = useOperatorAuthStore((state) => state.operator);
  const isManager = operator?.role === 'manager';

  const { data: reviews = [], isLoading: isLoadingReviews, refetch: refetchReviews } = useQuery({
    queryKey: ['desk', 'reviews', filter, search],
    queryFn: () => reviewsApi.fetchReviews({ filter, search: search || undefined }),
  });

  const { data: slaStats, isLoading: isLoadingSla, refetch: refetchSla } = useQuery({
    queryKey: ['desk', 'reviews', 'sla-stats'],
    queryFn: () => reviewsApi.fetchSlaStats(),
    enabled: activeTab === 'sla_quality',
  });

  const replyMutation = useMutation({
    mutationFn: ({ id, replyText }: { id: string; replyText: string }) =>
      reviewsApi.replyToReview(id, replyText),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['desk', 'reviews'] });
      setFeedbackToast('پاسخ مدیر دفتر با موفقیت ثبت شد.');
      setSelectedReviewForReply(null);
      setTimeout(() => setFeedbackToast(null), 4000);
    },
  });

  const handleRefresh = () => {
    if (activeTab === 'feedback') {
      refetchReviews();
    } else {
      refetchSla();
    }
  };

  return (
    <div className="space-y-6 max-w-6xl mx-auto p-4 sm:p-6" data-testid="reviews-page">
      {/* Header */}
      <header className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <MessageSquare className="w-6 h-6 text-blue-600" aria-hidden="true" />
            <span>نظرات شهروندان و سنجش کیفیت SLA</span>
          </h1>
          <p className="text-xs sm:text-sm text-slate-500 mt-1">
            مشاهده بازخورد مراجعین، پاسخگویی رسمی مدیر دفتر و تحلیل روند کیفیت عملکرد
          </p>
        </div>

        <Button
          variant="secondary"
          size="sm"
          onClick={handleRefresh}
          disabled={isLoadingReviews || isLoadingSla}
          data-testid="reviews-refresh-btn"
        >
          <RefreshCw
            className={`w-4 h-4 me-1.5 ${isLoadingReviews || isLoadingSla ? 'animate-spin' : ''}`}
            aria-hidden="true"
          />
          <span>به‌روزرسانی</span>
        </Button>
      </header>

      {/* Toast Feedback */}
      {feedbackToast && (
        <div
          role="status"
          aria-live="polite"
          className="p-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs"
        >
          {feedbackToast}
        </div>
      )}

      {/* Subtabs Bar */}
      <nav role="tablist" aria-label="بخش‌های نظرات و کیفیت" className="flex border-b border-slate-200 dark:border-slate-800">
        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'feedback'}
          data-testid="subtab-feedback"
          onClick={() => setActiveTab('feedback')}
          className={`flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-colors ${
            activeTab === 'feedback'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
          }`}
        >
          <MessageSquare className="w-4 h-4" aria-hidden="true" />
          <span>دیدگاه‌ها و نظرات شهروندان</span>
        </button>

        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'sla_quality'}
          data-testid="subtab-sla-quality"
          onClick={() => setActiveTab('sla_quality')}
          className={`flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-colors ${
            activeTab === 'sla_quality'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
          }`}
        >
          <ShieldCheck className="w-4 h-4" aria-hidden="true" />
          <span>روند کیفیت و تخطی‌های SLA</span>
        </button>
      </nav>

      {/* Content Area */}
      <main>
        {activeTab === 'feedback' ? (
          <FeedbackTab
            reviews={reviews}
            isLoading={isLoadingReviews}
            isManager={isManager}
            filter={filter}
            onFilterChange={setFilter}
            onSearchChange={setSearch}
            onOpenReply={(rev) => setSelectedReviewForReply(rev)}
          />
        ) : (
          <SlaQualityTab stats={slaStats} isLoading={isLoadingSla} />
        )}
      </main>

      {/* Reply Modal */}
      {selectedReviewForReply && (
        <ReplyReviewModal
          review={selectedReviewForReply}
          onClose={() => setSelectedReviewForReply(null)}
          onSubmit={async (id, text) => {
            await replyMutation.mutateAsync({ id, replyText: text });
          }}
          isSubmitting={replyMutation.isPending}
        />
      )}
    </div>
  );
};
