import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import {
  StatusPill,
  TurnOwnerChip,
  CountdownTimer,
  Timeline,
  ServiceTagBadge,
  Skeleton,
} from '@pishkhan/ui-kit';
import type { CaseDetail } from '../types';
import { caseTrackingApi } from '../api/caseTrackingApi';
import { formatJalaliDateTime } from '../utils/formatters';
import { CaseStatusBanner } from './CaseStatusBanner';
import { CaseActionsPanel } from './CaseActionsPanel';

interface OfficeInfoCardProps {
  office: NonNullable<CaseDetail['assigned_office']>;
}

function OfficeInfoCard({ office }: OfficeInfoCardProps): React.JSX.Element {
  const { t } = useTranslation();
  return (
    <div className="p-4 rounded-2xl bg-white/70 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 space-y-2">
      <h3 className="text-xs font-semibold text-slate-500">{t('cases.office_assigned_title')}</h3>
      <div className="flex items-center justify-between">
        <span className="text-sm font-bold text-slate-800 dark:text-slate-200">{office.name}</span>
        {office.phone ? (
          <span className="text-xs text-slate-500 font-mono" dir="ltr">{office.phone}</span>
        ) : null}
      </div>
    </div>
  );
}

function CaseDetailHeader({ detail }: { detail: CaseDetail }): React.JSX.Element {
  const { t } = useTranslation();
  return (
    <div className="flex flex-wrap items-center justify-between gap-3">
      <div className="flex items-center gap-3">
        <Link
          to="/cases"
          className="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 transition-colors text-xs font-medium"
        >
          ← {t('cases.back_to_list')}
        </Link>
        <h1 className="text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
          <span>{detail.service.title}</span>
          <ServiceTagBadge tag={detail.service.tag as 'online' | 'semi-online' | 'in-person'} />
        </h1>
      </div>

      <div className="flex items-center gap-2">
        <span className="font-mono text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700">
          {detail.tracking_code}
        </span>
        <StatusPill status={detail.status} />
      </div>
    </div>
  );
}

function TurnAndSlaCard({ detail }: { detail: CaseDetail }): React.JSX.Element {
  const { t } = useTranslation();
  return (
    <div className="p-4 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
      <div className="flex items-center gap-3">
        <span className="text-xs text-slate-500">{t('cases.turn_owner_status_label')}:</span>
        <TurnOwnerChip owner={detail.turn_owner} labelOverride={detail.turn_owner_label} />
      </div>

      {detail.sla && detail.sla.remaining_seconds > 0 ? (
        <div className="flex items-center gap-2">
          <span className="text-xs text-slate-500">{t('cases.sla_remaining_label')}:</span>
          <CountdownTimer initialSeconds={detail.sla.remaining_seconds} />
        </div>
      ) : null}
    </div>
  );
}

function CaseTimelineSection({ detail }: { detail: CaseDetail }): React.JSX.Element {
  const { t } = useTranslation();
  const timelineItems = detail.timeline.map((st) => ({
    id: st.id,
    title: st.title,
    description: st.description,
    status: st.status,
    turnOwner: st.turn_owner,
    turnOwnerLabel: st.turn_owner_label,
    occurredAt: st.occurred_at,
    officeNote: st.office_note,
  }));

  return (
    <div className="p-5 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
      <h2 className="text-sm font-bold text-slate-900 dark:text-slate-100">{t('cases.timeline_title')}</h2>
      <Timeline
        items={timelineItems}
        renderDate={(iso) => formatJalaliDateTime(iso)}
        ariaLabel={t('cases.timeline_aria_label')}
        officeNoteLabel={t('cases.office_note_label')}
      />
    </div>
  );
}

function DetailLoadingOrError({ isLoading, detail }: { isLoading: boolean; detail: CaseDetail | null }): React.JSX.Element | null {
  const { t } = useTranslation();
  if (isLoading) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-6 space-y-6">
        <Skeleton className="h-10 w-48 rounded-xl" />
        <Skeleton className="h-32 w-full rounded-2xl" />
        <Skeleton className="h-64 w-full rounded-2xl" />
      </div>
    );
  }
  if (!detail) {
    return (
      <div className="max-w-md mx-auto px-4 py-16 text-center space-y-4">
        <h2 className="text-lg font-bold text-slate-800 dark:text-slate-200">{t('cases.not_found_title')}</h2>
        <Link to="/cases" className="text-xs text-emerald-600 hover:underline">{t('cases.back_to_list')}</Link>
      </div>
    );
  }
  return null;
}

export function CaseDetailPage(): React.JSX.Element {
  const { trackingCode } = useParams<{ trackingCode: string }>();
  const { t } = useTranslation();
  const [detail, setDetail] = useState<CaseDetail | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [actionMessage, setActionMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!trackingCode) return;
    let mounted = true;
    caseTrackingApi
      .getCaseByTrackingCode(trackingCode)
      .then((data) => {
        if (mounted) {
          setDetail(data);
          setIsLoading(false);
        }
      })
      .catch(() => {
        if (mounted) setIsLoading(false);
      });
    return () => {
      mounted = false;
    };
  }, [trackingCode]);

  const earlyView = DetailLoadingOrError({ isLoading, detail });
  if (earlyView || !detail) return earlyView ?? <div />;

  return (
    <div className="max-w-4xl mx-auto px-4 py-6 space-y-6">
      <CaseDetailHeader detail={detail} />
      <TurnAndSlaCard detail={detail} />
      {actionMessage && <div className="p-3 rounded-xl bg-blue-50 text-blue-800 text-xs border border-blue-200">{actionMessage}</div>}
      {detail.status === 'action_required' && detail.return_reason && (
        <CaseStatusBanner returnReason={detail.return_reason} onFixAction={() => setActionMessage(`${t('cases.action_executed_prefix')}: upload_fix_document`)} />
      )}
      <CaseTimelineSection detail={detail} />
      {detail.assigned_office ? <OfficeInfoCard office={detail.assigned_office} /> : null}
      <CaseActionsPanel availableActions={detail.available_actions} trackingCode={detail.tracking_code} onActionClick={(a) => setActionMessage(`${t('cases.action_executed_prefix')}: ${a}`)} />
    </div>
  );
}
