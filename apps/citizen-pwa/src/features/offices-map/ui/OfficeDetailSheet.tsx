import React from 'react';
import { useTranslation } from 'react-i18next';
import { Star, MapPin, Phone, Clock, Users, Award, Sparkles, CheckCircle2 } from 'lucide-react';
import { Sheet, Button } from '@pishkhan/ui-kit';
import { getOfficeMembershipStatusMeta } from '@pishkhan/domain';
import type { OfficeItem } from '../types';

interface OfficeDetailSheetProps {
  office: OfficeItem | null;
  isOpen: boolean;
  onClose: () => void;
  onDirectAssign?: ((office: OfficeItem) => void) | undefined;
  onBookAppointment?: ((office: OfficeItem) => void) | undefined;
}

const OfficeStatsRow: React.FC<{ office: OfficeItem }> = ({ office }) => {
  const { t } = useTranslation();
  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 bg-slate-50 rounded-xl text-xs">
      <div className="flex items-center gap-1.5 text-slate-700">
        <Users className="w-4 h-4 text-blue-600 shrink-0" aria-hidden="true" />
        <span>{t('offices.active_counters', { count: office.active_counters })}</span>
      </div>
      <div className="flex items-center gap-1.5 text-slate-700">
        <Clock className="w-4 h-4 text-amber-600 shrink-0" aria-hidden="true" />
        <span>{t('offices.waiting_queue', { count: office.current_waiting_queue })}</span>
      </div>
      <div className="col-span-2 sm:col-span-1 text-slate-600">
        {t('offices.estimated_wait', { minutes: office.estimated_wait_minutes })}
      </div>
    </div>
  );
};

const OfficeInfoList: React.FC<{ office: OfficeItem }> = ({ office }) => {
  return (
    <div className="space-y-2.5 text-xs text-slate-600">
      <div className="flex items-start gap-2">
        <MapPin className="w-4 h-4 text-slate-400 shrink-0 mt-0.5" aria-hidden="true" />
        <span className="leading-relaxed">{office.address}</span>
      </div>
      {office.phone && (
        <div className="flex items-center gap-2">
          <Phone className="w-4 h-4 text-slate-400 shrink-0" aria-hidden="true" />
          <a href={`tel:${office.phone}`} className="text-blue-600 hover:underline font-mono text-xs" dir="ltr">
            {office.phone}
          </a>
        </div>
      )}
      <div className="flex items-center gap-2">
        <Clock className="w-4 h-4 text-slate-400 shrink-0" aria-hidden="true" />
        <span>{office.working_hours.label}</span>
      </div>
    </div>
  );
};

const OfficeBadgesHeader: React.FC<{ office: OfficeItem }> = ({ office }) => {
  const { t } = useTranslation();
  const statusMeta = getOfficeMembershipStatusMeta(office.membership_status);
  const statusColors = {
    registered_online: { bg: '#ecfdf5', text: '#065f46' },
    registered_offline: { bg: '#fffbeb', text: '#92400e' },
    unregistered: { bg: '#f1f5f9', text: '#334155' },
  }[office.membership_status];

  return (
    <div className="flex items-center justify-between gap-2 pb-2 border-b border-slate-100">
      <div className="flex items-center gap-2 flex-wrap">
        <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
          {t('offices.office_code', { code: office.code })}
        </span>
        <span
          className="px-2.5 py-0.5 rounded-full text-xs font-medium"
          style={{ backgroundColor: statusColors.bg, color: statusColors.text }}
        >
          {statusMeta.label}
        </span>
      </div>
      <div className="flex items-center gap-1 text-amber-500 font-bold text-xs">
        <Star className="w-3.5 h-3.5 fill-amber-400" aria-hidden="true" />
        <span>{office.rating}</span>
        <span className="text-slate-400 font-normal">
          {t('offices.rating_count', { count: office.review_count })}
        </span>
      </div>
    </div>
  );
};

const OfficeSpecialtiesAndMedals: React.FC<{ office: OfficeItem }> = ({ office }) => {
  const { t } = useTranslation();
  return (
    <>
      {office.specialties.length > 0 && (
        <div className="space-y-1.5">
          <h4 className="text-xs font-semibold text-slate-700 flex items-center gap-1">
            <Sparkles className="w-3.5 h-3.5 text-blue-500" aria-hidden="true" />
            {t('offices.specialties')}
          </h4>
          <div className="flex flex-wrap gap-1.5">
            {office.specialties.map((s, idx) => (
              <span key={idx} className="px-2 py-0.5 rounded-lg text-xs bg-blue-50 text-blue-700 border border-blue-100">
                {s}
              </span>
            ))}
          </div>
        </div>
      )}
      {office.medals.length > 0 && (
        <div className="space-y-1.5">
          <h4 className="text-xs font-semibold text-slate-700 flex items-center gap-1">
            <Award className="w-3.5 h-3.5 text-amber-500" aria-hidden="true" />
            {t('offices.medals')}
          </h4>
          <div className="flex flex-wrap gap-1.5">
            {office.medals.map((m, idx) => (
              <span key={idx} className="px-2 py-0.5 rounded-lg text-xs bg-amber-50 text-amber-700 border border-amber-100 flex items-center gap-1">
                <CheckCircle2 className="w-3 h-3" aria-hidden="true" />
                {m}
              </span>
            ))}
          </div>
        </div>
      )}
    </>
  );
};

const OfficeActionsRow: React.FC<{
  office: OfficeItem;
  onDirectAssign?: ((office: OfficeItem) => void) | undefined;
  onBookAppointment?: ((office: OfficeItem) => void) | undefined;
}> = ({ office, onDirectAssign, onBookAppointment }) => {
  const { t } = useTranslation();
  return (
    <div className="flex items-center gap-2 pt-2 border-t border-slate-100">
      {office.membership_status === 'registered_online' && onDirectAssign && (
        <Button type="button" variant="primary" className="flex-1 text-xs py-2" onClick={() => onDirectAssign(office)}>
          {t('offices.direct_assign')}
        </Button>
      )}
      {onBookAppointment && (
        <Button
          type="button"
          variant={office.membership_status === 'registered_online' ? 'secondary' : 'primary'}
          className="flex-1 text-xs py-2"
          onClick={() => onBookAppointment(office)}
        >
          {t('offices.book_appointment')}
        </Button>
      )}
    </div>
  );
};

export const OfficeDetailSheet: React.FC<OfficeDetailSheetProps> = ({
  office,
  isOpen,
  onClose,
  onDirectAssign,
  onBookAppointment,
}) => {
  const { t } = useTranslation();
  if (!office) return null;

  return (
    <Sheet isOpen={isOpen} onClose={onClose} title={office.name} closeAriaLabel={t('offices.close_details')}>
      <div className="space-y-4">
        <OfficeBadgesHeader office={office} />
        <OfficeStatsRow office={office} />
        <OfficeInfoList office={office} />
        <OfficeSpecialtiesAndMedals office={office} />
        <OfficeActionsRow office={office} onDirectAssign={onDirectAssign} onBookAppointment={onBookAppointment} />
      </div>
    </Sheet>
  );
};
