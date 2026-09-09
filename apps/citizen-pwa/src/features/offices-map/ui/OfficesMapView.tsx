import React, { useState, useEffect, useMemo, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import { useParams, useNavigate } from 'react-router-dom';
import { Locate, AlertCircle } from 'lucide-react';
import type { OfficeMembershipStatus } from '@pishkhan/domain';
import type { OfficeItem, MapTheme, Coordinates, CityOption } from '../types';
import { officesApi } from '../api/officesApi';
import { useUserLocation } from '../model/useUserLocation';
import { LeafletMapContainer } from './LeafletMapContainer';
import { OfficesFilterBar } from './OfficesFilterBar';
import { MapThemeSelector } from './MapThemeSelector';
import { CityPickerModal } from './CityPickerModal';
import { OfficeDetailSheet } from './OfficeDetailSheet';

const filterOffices = (
  items: OfficeItem[],
  query: string,
  status: OfficeMembershipStatus | 'all',
  minRating: number,
): OfficeItem[] => {
  return items.filter((o) => {
    const matchesSearch = !query || o.name.includes(query) || o.code.includes(query) || o.address.includes(query);
    const matchesStatus = status === 'all' || o.membership_status === status;
    const matchesRating = o.rating >= minRating;
    return matchesSearch && matchesStatus && matchesRating;
  });
};

const useOfficesData = (
  userCoords: Coordinates,
  officeId: string | undefined,
): { offices: OfficeItem[]; selectedOffice: OfficeItem | null; setSelectedOffice: (o: OfficeItem | null) => void } => {
  const [offices, setOffices] = useState<OfficeItem[]>([]);
  const [selectedOffice, setSelectedOffice] = useState<OfficeItem | null>(null);

  useEffect(() => {
    let isCancelled = false;
    officesApi.getNearby({ coords: userCoords, radiusKm: 25 }).then((res) => {
      if (!isCancelled && res.data) setOffices(res.data);
    }).catch(() => {});
    return () => { isCancelled = true; };
  }, [userCoords]);

  useEffect(() => {
    if (!officeId || offices.length === 0) return;
    const found = offices.find((o) => o.id === officeId || o.code === officeId);
    if (found) setSelectedOffice(found);
  }, [officeId, offices]);

  return { offices, selectedOffice, setSelectedOffice };
};

const MapTopControls: React.FC<{
  theme: MapTheme;
  onSelectTheme: (t: MapTheme) => void;
  selectedCity: CityOption | null;
  onRequestLocation: () => void;
  children: React.ReactNode;
}> = ({ theme, onSelectTheme, selectedCity, onRequestLocation, children }) => {
  const { t } = useTranslation();
  return (
    <div className="absolute top-3 inset-x-3 z-10 space-y-2 pointer-events-none">
      <div className="flex items-center justify-between gap-2 pointer-events-auto flex-wrap">
        <MapThemeSelector currentTheme={theme} onSelectTheme={onSelectTheme} />
        <button
          type="button"
          onClick={onRequestLocation}
          className="flex items-center gap-1.5 px-3 py-1.5 bg-white/95 backdrop-blur-xs text-xs font-medium text-slate-700 hover:text-blue-600 rounded-xl shadow-md border border-slate-200 transition-colors"
        >
          <Locate className="w-3.5 h-3.5 text-blue-600" aria-hidden="true" />
          <span>{selectedCity ? selectedCity.name : t('offices.my_location')}</span>
        </button>
      </div>
      <div className="pointer-events-auto">{children}</div>
    </div>
  );
};

const EmptyOfficesNotice: React.FC<{ onClear: () => void }> = ({ onClear }) => {
  const { t } = useTranslation();
  return (
    <div className="absolute bottom-6 inset-x-4 z-10 max-w-sm mx-auto p-3 bg-white/95 backdrop-blur-xs rounded-2xl shadow-lg border border-slate-200 text-center text-xs">
      <div className="flex items-center justify-center gap-1.5 text-amber-600 font-semibold mb-1">
        <AlertCircle className="w-4 h-4" aria-hidden="true" />
        <span>{t('offices.empty_title')}</span>
      </div>
      <p className="text-slate-500 mb-2">{t('offices.empty_desc')}</p>
      <button type="button" onClick={onClear} className="text-blue-600 font-medium hover:underline">
        {t('offices.clear_filters')}
      </button>
    </div>
  );
};

export const OfficesMapView: React.FC = () => {
  const { officeId } = useParams<{ officeId?: string }>();
  const navigate = useNavigate();
  const [theme, setTheme] = useState<MapTheme>('standard-day');
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<OfficeMembershipStatus | 'all'>('all');
  const [minRating, setMinRating] = useState<number>(0);

  const { coords, status: locStatus, selectedCity, showCityPicker, requestLocation, selectCity, dismissCityPicker } = useUserLocation();
  const { offices, selectedOffice, setSelectedOffice } = useOfficesData(coords, officeId);

  const filteredOffices = useMemo(
    () => filterOffices(offices, searchQuery, statusFilter, minRating),
    [offices, searchQuery, statusFilter, minRating],
  );

  const handleSelect = useCallback((off: OfficeItem) => { setSelectedOffice(off); navigate(`/map/offices/${off.id}`); }, [navigate, setSelectedOffice]);
  const handleClose = useCallback(() => { setSelectedOffice(null); navigate('/map'); }, [navigate, setSelectedOffice]);
  const clearFilters = useCallback(() => { setSearchQuery(''); setStatusFilter('all'); setMinRating(0); }, []);

  return (
    <div className="relative w-full h-[calc(100vh-64px)] flex flex-col bg-slate-100 overflow-hidden">
      <MapTopControls theme={theme} onSelectTheme={setTheme} selectedCity={selectedCity} onRequestLocation={requestLocation}>
        <OfficesFilterBar
          searchQuery={searchQuery}
          onSearchChange={setSearchQuery}
          selectedStatus={statusFilter}
          onStatusChange={setStatusFilter}
          minRating={minRating}
          onMinRatingChange={setMinRating}
        />
      </MapTopControls>

      <div className="flex-1 w-full h-full">
        <LeafletMapContainer
          center={coords}
          userCoords={locStatus === 'resolved' ? coords : null}
          offices={filteredOffices}
          selectedOffice={selectedOffice}
          theme={theme}
          onSelectOffice={handleSelect}
        />
      </div>

      {filteredOffices.length === 0 && offices.length > 0 && <EmptyOfficesNotice onClear={clearFilters} />}
      <CityPickerModal isOpen={showCityPicker} onClose={dismissCityPicker} onSelectCity={selectCity} />
      <OfficeDetailSheet
        office={selectedOffice}
        isOpen={selectedOffice !== null}
        onClose={handleClose}
        onDirectAssign={(off) => navigate(`/request/create?officeId=${off.id}`)}
        onBookAppointment={(off) => navigate(`/profile/appointments?officeId=${off.id}`)}
      />
    </div>
  );
};
