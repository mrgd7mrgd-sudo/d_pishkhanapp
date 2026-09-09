import React from 'react';
import { useTranslation } from 'react-i18next';
import { MapPin } from 'lucide-react';
import { Dialog } from '@pishkhan/ui-kit';
import type { CityOption } from '../types';
import { IRAN_CITIES } from '../constants';

interface CityPickerModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSelectCity: (city: CityOption) => void;
}

export const CityPickerModal: React.FC<CityPickerModalProps> = ({
  isOpen,
  onClose,
  onSelectCity,
}) => {
  const { t } = useTranslation();

  return (
    <Dialog
      isOpen={isOpen}
      onClose={onClose}
      title={t('offices.city_picker_title')}
      closeAriaLabel={t('offices.close_details')}
    >
      <div className="space-y-4">
        <p className="text-sm text-slate-600 leading-relaxed">
          {t('offices.city_picker_desc')}
        </p>

        <div
          className="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-60 overflow-y-auto p-1"
          role="group"
          aria-label={t('offices.city_picker_select')}
        >
          {IRAN_CITIES.map((city) => (
            <button
              key={city.code}
              type="button"
              onClick={() => onSelectCity(city)}
              className="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-800 hover:border-blue-500 hover:bg-blue-50/50 transition-colors text-start"
            >
              <MapPin className="w-4 h-4 text-blue-600 shrink-0" aria-hidden="true" />
              <span>{city.name}</span>
            </button>
          ))}
        </div>
      </div>
    </Dialog>
  );
};
