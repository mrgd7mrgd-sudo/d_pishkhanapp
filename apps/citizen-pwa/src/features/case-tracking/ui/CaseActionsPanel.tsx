import React from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@pishkhan/ui-kit';

export interface CaseActionsPanelProps {
  availableActions: string[];
  trackingCode: string;
  onActionClick: (action: string) => void;
}

const ACTION_KEY_MAP: Record<string, { labelKey: string; variant: 'primary' | 'secondary' | 'danger' | 'ghost' }> = {
  upload_fix_document: { labelKey: 'cases.action_upload_fix_document', variant: 'primary' },
  open_chat: { labelKey: 'cases.action_open_chat', variant: 'secondary' },
  cancel_case: { labelKey: 'cases.action_cancel_case', variant: 'danger' },
  view_receipt: { labelKey: 'cases.action_view_receipt', variant: 'secondary' },
  track_courier: { labelKey: 'cases.action_track_courier', variant: 'primary' },
  download_result: { labelKey: 'cases.action_download_result', variant: 'primary' },
  rate_service: { labelKey: 'cases.action_rate_service', variant: 'secondary' },
  view_rejection_reason: { labelKey: 'cases.action_view_rejection_reason', variant: 'secondary' },
  submit_objection: { labelKey: 'cases.action_submit_objection', variant: 'primary' },
  view_cancellation_details: { labelKey: 'cases.action_view_cancellation_details', variant: 'secondary' },
};

/**
 * CaseActionsPanel renders buttons strictly and only from available_actions (Architecture D-19)
 */
export function CaseActionsPanel({
  availableActions,
  onActionClick,
}: CaseActionsPanelProps): React.JSX.Element | null {
  const { t } = useTranslation();

  if (!availableActions || availableActions.length === 0) {
    return null;
  }

  return (
    <div
      role="group"
      aria-label={t('cases.actions_panel_label')}
      className="p-4 rounded-2xl bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 shadow-sm flex flex-wrap items-center gap-3"
    >
      <span className="text-xs font-semibold text-slate-700 dark:text-slate-300 me-2">
        {t('cases.actions_available_title')}
      </span>

      {availableActions.map((action) => {
        const config = ACTION_KEY_MAP[action] ?? {
          labelKey: `cases.action_${action}`,
          variant: 'secondary' as const,
        };
        const label = t(config.labelKey);

        return (
          <Button
            key={action}
            variant={config.variant}
            size="sm"
            onClick={() => onActionClick(action)}
            aria-label={label}
          >
            {label}
          </Button>
        );
      })}
    </div>
  );
}
