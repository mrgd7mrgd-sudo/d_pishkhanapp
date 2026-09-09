import { useState, useEffect, useCallback, useRef } from 'react';
import type { Coordinates, CityOption } from '../types';
import {
  DEFAULT_COORDINATES,
  GEOLOCATION_TIMEOUT_MS,
  IRAN_CITIES,
} from '../constants';

export type LocationSource = 'geolocation' | 'city_picker' | 'province_default';
export type LocationStatus = 'idle' | 'requesting' | 'prompt_city' | 'resolved';

export interface UseUserLocationOptions {
  userProvinceCode?: string | undefined;
  timeoutMs?: number | undefined;
  autoRequest?: boolean | undefined;
}

export interface UserLocationResult {
  coords: Coordinates;
  status: LocationStatus;
  source: LocationSource;
  selectedCity: CityOption | null;
  showCityPicker: boolean;
  requestLocation: () => void;
  selectCity: (city: CityOption) => void;
  dismissCityPicker: () => void;
}

const findProvinceCenter = (provinceCode?: string): Coordinates => {
  if (!provinceCode) return DEFAULT_COORDINATES;
  const match = IRAN_CITIES.find((c) => c.provinceCode === provinceCode);
  return match ? match.coords : DEFAULT_COORDINATES;
};

const performGeolocation = (
  timeoutMs: number,
  onSuccess: (coords: Coordinates) => void,
  onError: () => void,
): void => {
  if (typeof navigator === 'undefined' || !navigator.geolocation) {
    onError();
    return;
  }
  let timerId: ReturnType<typeof setTimeout> | null = null;
  let didFinish = false;

  const handleSuccess = (pos: GeolocationPosition): void => {
    if (didFinish) return;
    didFinish = true;
    if (timerId) clearTimeout(timerId);
    onSuccess({ lat: pos.coords.latitude, lng: pos.coords.longitude });
  };

  const handleError = (): void => {
    if (didFinish) return;
    didFinish = true;
    if (timerId) clearTimeout(timerId);
    onError();
  };

  timerId = setTimeout(handleError, timeoutMs);
  navigator.geolocation.getCurrentPosition(handleSuccess, handleError, {
    enableHighAccuracy: true,
    timeout: timeoutMs,
    maximumAge: 60000,
  });
};

export const useUserLocation = (
  options: UseUserLocationOptions = {},
): UserLocationResult => {
  const { userProvinceCode, timeoutMs = GEOLOCATION_TIMEOUT_MS, autoRequest = true } = options;
  const [coords, setCoords] = useState<Coordinates>(() => findProvinceCenter(userProvinceCode));
  const [source, setSource] = useState<LocationSource>('province_default');
  const [status, setStatus] = useState<LocationStatus>('idle');
  const [selectedCity, setSelectedCity] = useState<CityOption | null>(null);
  const [showCityPicker, setShowCityPicker] = useState<boolean>(false);
  const isMountedRef = useRef<boolean>(true);

  const requestLocation = useCallback(() => {
    setStatus('requesting');
    performGeolocation(
      timeoutMs,
      (c) => {
        if (!isMountedRef.current) return;
        setCoords(c);
        setSource('geolocation');
        setStatus('resolved');
        setShowCityPicker(false);
      },
      () => {
        if (!isMountedRef.current) return;
        setStatus('prompt_city');
        setShowCityPicker(true);
      },
    );
  }, [timeoutMs]);

  const selectCity = useCallback((city: CityOption) => {
    setSelectedCity(city);
    setCoords(city.coords);
    setSource('city_picker');
    setStatus('resolved');
    setShowCityPicker(false);
  }, []);

  const dismissCityPicker = useCallback(() => {
    setCoords(findProvinceCenter(userProvinceCode));
    setSource('province_default');
    setStatus('resolved');
    setShowCityPicker(false);
  }, [userProvinceCode]);

  useEffect(() => {
    isMountedRef.current = true;
    if (autoRequest) requestLocation();
    return () => {
      isMountedRef.current = false;
    };
  }, [autoRequest, requestLocation]);

  return { coords, status, source, selectedCity, showCityPicker, requestLocation, selectCity, dismissCityPicker };
};
