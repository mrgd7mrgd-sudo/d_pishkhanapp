import type { CityOption, Coordinates, MapTheme } from './types';

export interface MapThemeConfig {
  readonly id: MapTheme;
  readonly nameKey: string;
  readonly tileUrl: string;
  readonly attribution: string;
}

export const MAP_THEMES: Record<MapTheme, MapThemeConfig> = {
  'standard-day': {
    id: 'standard-day',
    nameKey: 'offices.theme_standard_day',
    tileUrl: '/tiles/standard-day/{z}/{x}/{y}.png',
    attribution: '© Neshan / پیشخوان هوشمند',
  },
  neshan: {
    id: 'neshan',
    nameKey: 'offices.theme_neshan',
    tileUrl: '/tiles/neshan/{z}/{x}/{y}.png',
    attribution: '© Neshan / پیشخوان هوشمند',
  },
  dreamy: {
    id: 'dreamy',
    nameKey: 'offices.theme_dreamy',
    tileUrl: '/tiles/dreamy/{z}/{x}/{y}.png',
    attribution: '© Neshan / پیشخوان هوشمند',
  },
} as const;

export const DEFAULT_COORDINATES: Coordinates = {
  lat: 35.6892,
  lng: 51.389,
};

export const GEOLOCATION_TIMEOUT_MS = 8000;

export const IRAN_CITIES: readonly CityOption[] = [
  {
    code: 'tehran',
    name: 'تهران',
    provinceCode: 'THR',
    coords: { lat: 35.6892, lng: 51.389 },
  },
  {
    code: 'mashhad',
    name: 'مشهد',
    provinceCode: 'KHD',
    coords: { lat: 36.2972, lng: 59.6067 },
  },
  {
    code: 'isfahan',
    name: 'اصفهان',
    provinceCode: 'ESF',
    coords: { lat: 32.6546, lng: 51.668 },
  },
  {
    code: 'tabriz',
    name: 'تبریز',
    provinceCode: 'EAZ',
    coords: { lat: 38.0962, lng: 46.2738 },
  },
  {
    code: 'shiraz',
    name: 'شیراز',
    provinceCode: 'FRS',
    coords: { lat: 29.5918, lng: 52.5837 },
  },
  {
    code: 'karaj',
    name: 'کرج',
    provinceCode: 'ABZ',
    coords: { lat: 35.84, lng: 50.9391 },
  },
  {
    code: 'ahvaz',
    name: 'اهواز',
    provinceCode: 'KHZ',
    coords: { lat: 31.3183, lng: 48.6706 },
  },
  {
    code: 'qom',
    name: 'قم',
    provinceCode: 'QOM',
    coords: { lat: 34.6416, lng: 50.8746 },
  },
  {
    code: 'kermanshah',
    name: 'کرمانشاه',
    provinceCode: 'KSH',
    coords: { lat: 34.3142, lng: 47.065 },
  },
  {
    code: 'rasht',
    name: 'رشت',
    provinceCode: 'GIL',
    coords: { lat: 37.2808, lng: 49.5832 },
  },
] as const;
