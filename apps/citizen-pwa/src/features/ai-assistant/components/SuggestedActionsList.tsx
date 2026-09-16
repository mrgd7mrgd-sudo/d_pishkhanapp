import React from 'react';
import type { AiSuggestedAction } from '../types';
import { ArrowLeft, MapPin, FileText, CheckCircle2 } from 'lucide-react';

interface SuggestedActionsListProps {
  actions?: AiSuggestedAction[] | undefined;
  onActionClick?: ((action: AiSuggestedAction) => void) | undefined;
}

export const SuggestedActionsList: React.FC<SuggestedActionsListProps> = ({
  actions,
  onActionClick,
}) => {
  if (!actions || actions.length === 0) return null;

  const getActionIcon = (type: string) => {
    switch (type) {
      case 'open_office':
        return <MapPin className="w-3.5 h-3.5 text-indigo-500" />;
      case 'track_case':
        return <CheckCircle2 className="w-3.5 h-3.5 text-amber-500" />;
      default:
        return <FileText className="w-3.5 h-3.5 text-emerald-500" />;
    }
  };

  return (
    <div
      data-testid="suggested-actions"
      className="flex flex-wrap gap-2 mt-3 pt-2 border-t border-slate-200/40 dark:border-slate-700/40"
    >
      {actions.map((action, idx) => (
        <button
          key={idx}
          type="button"
          onClick={() => onActionClick?.(action)}
          className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 transition-all duration-200 active:scale-95"
        >
          {getActionIcon(action.type)}
          <span>{action.label}</span>
          <ArrowLeft className="w-3 h-3 text-emerald-500/70" />
        </button>
      ))}
    </div>
  );
};
