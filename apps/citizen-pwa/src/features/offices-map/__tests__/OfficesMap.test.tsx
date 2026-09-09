import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { axe } from 'vitest-axe';
import {
  createOfficeMarkerIcon,
  createUserLocationIcon,
} from '../ui/CustomOfficeMarkers';
import { CityPickerModal } from '../ui/CityPickerModal';
import { MapThemeSelector } from '../ui/MapThemeSelector';
import { OfficeDetailSheet } from '../ui/OfficeDetailSheet';
import { OfficesFilterBar } from '../ui/OfficesFilterBar';
import { useUserLocation } from '../model/useUserLocation';
import { renderHook } from '@testing-library/react';
import type { OfficeItem } from '../types';
import { IRAN_CITIES, MAP_THEMES } from '../constants';
import '@/shared/i18n';

const MOCK_OFFICE: OfficeItem = {
  id: 'off-teh-mehregan',
  code: '1402',
  name: 'پیشخوان مهرگان ونک',
  manager_name: 'مهندس سهراب صامتی',
  membership_status: 'registered_online',
  is_online: true,
  rating: 4.8,
  review_count: 428,
  medals: ['عضو طلایی سامانه ملی', 'پاسخگویی زیر ۱۵ دقیقه'],
  specialties: ['ثبت احوال و شناسنامه VIP', 'خدمات خودرو و راهور'],
  address: 'تهران، خیابان ولی‌عصر، بالاتر از میدان ونک، پلاک ۲۱۴',
  province_code: 'THR',
  city: 'تهران',
  region: null,
  coords: { lat: 35.7592, lng: 51.4083 },
  distance_km: 1.2,
  phone: '021-88776655',
  working_hours: {
    label: '۰۷:۳۰ الی ۱۹:۳۰ (یکسره)',
    is_open_now: true,
  },
  active_counters: 8,
  current_waiting_queue: 2,
  estimated_wait_minutes: 1,
  supported_category_ids: ['identity', 'vehicle'],
  smart_score: 95.0,
};

describe('Offices Map Slice (§4.1, §4.4, §8.4, TASK-047, TASK-047-T)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Custom SVG Markers & Themes (§8.4)', () => {
    it('creates distinct SVG markers for each office membership status with correct colors', () => {
      const onlineMarker = createOfficeMarkerIcon('registered_online', false);
      const offlineMarker = createOfficeMarkerIcon('registered_offline', false);
      const unregMarker = createOfficeMarkerIcon('unregistered', true);

      expect(onlineMarker.options.html).toContain('data-status="registered_online"');
      expect(onlineMarker.options.html).toContain('#059669'); // emerald-600

      expect(offlineMarker.options.html).toContain('data-status="registered_offline"');
      expect(offlineMarker.options.html).toContain('#d97706'); // amber-600

      expect(unregMarker.options.html).toContain('data-status="unregistered"');
      expect(unregMarker.options.html).toContain('#64748b'); // slate-500
      expect(unregMarker.options.html).toContain('data-selected="true"');
    });

    it('creates a pulsating user location marker', () => {
      const userMarker = createUserLocationIcon();
      expect(userMarker.options.html).toContain('data-testid="user-location-marker"');
      expect(userMarker.options.html).toContain('#2563eb');
    });

    it('renders all 3 map themes (standard-day, neshan, dreamy) and allows selection', () => {
      const onSelectTheme = vi.fn();
      render(
        <MapThemeSelector currentTheme="standard-day" onSelectTheme={onSelectTheme} />,
      );

      expect(screen.getByText('استاندارد روز')).toBeInTheDocument();
      expect(screen.getByText('نشان اختصاصی')).toBeInTheDocument();
      expect(screen.getByText('مینیمال ملایم')).toBeInTheDocument();

      fireEvent.click(screen.getByText('نشان اختصاصی'));
      expect(onSelectTheme).toHaveBeenCalledWith('neshan');
    });
  });

  describe('3-Stage User Location Fallback (§8.4, TASK-047-T)', () => {
    it('Stage 1: resolves coordinates when geolocation succeeds', async () => {
      const mockGetCurrentPosition = vi.fn((success) => {
        success({
          coords: { latitude: 35.75, longitude: 51.42 },
        });
      });

      vi.stubGlobal('navigator', {
        geolocation: { getCurrentPosition: mockGetCurrentPosition },
      });

      const { result } = renderHook(() =>
        useUserLocation({ autoRequest: true, timeoutMs: 5000 }),
      );

      await waitFor(() => {
        expect(result.current.status).toBe('resolved');
        expect(result.current.source).toBe('geolocation');
        expect(result.current.coords).toEqual({ lat: 35.75, lng: 51.42 });
      });
    });

    it('Stage 2: opens city picker when geolocation is denied or rejected', async () => {
      const mockGetCurrentPosition = vi.fn((_success, error) => {
        error(new Error('User denied Geolocation'));
      });

      vi.stubGlobal('navigator', {
        geolocation: { getCurrentPosition: mockGetCurrentPosition },
      });

      const { result } = renderHook(() =>
        useUserLocation({ autoRequest: true }),
      );

      await waitFor(() => {
        expect(result.current.status).toBe('prompt_city');
        expect(result.current.showCityPicker).toBe(true);
      });

      // Citizen selects a city (Mashhad)
      const mashhad = IRAN_CITIES.find((c) => c.code === 'mashhad')!;
      act(() => {
        result.current.selectCity(mashhad);
      });

      expect(result.current.status).toBe('resolved');
      expect(result.current.source).toBe('city_picker');
      expect(result.current.coords).toEqual(mashhad.coords);
      expect(result.current.showCityPicker).toBe(false);
    });

    it('Stage 2/3: falls back to city picker then province center on 8-second timeout', async () => {
      vi.useFakeTimers();

      const mockGetCurrentPosition = vi.fn(); // Never calls callback (hangs)
      vi.stubGlobal('navigator', {
        geolocation: { getCurrentPosition: mockGetCurrentPosition },
      });

      const { result } = renderHook(() =>
        useUserLocation({ userProvinceCode: 'THR', timeoutMs: 8000, autoRequest: true }),
      );

      expect(result.current.status).toBe('requesting');

      // Fast-forward 8 seconds
      act(() => {
        vi.advanceTimersByTime(8000);
      });

      expect(result.current.status).toBe('prompt_city');
      expect(result.current.showCityPicker).toBe(true);

      // Citizen skips or dismisses city picker -> falls back to province center
      act(() => {
        result.current.dismissCityPicker();
      });

      expect(result.current.status).toBe('resolved');
      expect(result.current.source).toBe('province_default');
      expect(result.current.coords).toEqual({ lat: 35.6892, lng: 51.389 });

      vi.useRealTimers();
    });
  });

  describe('UI Components & Accessibility', () => {
    it('renders CityPickerModal and fires onSelectCity', () => {
      const onSelectCity = vi.fn();
      const onClose = vi.fn();

      render(
        <CityPickerModal
          isOpen={true}
          onClose={onClose}
          onSelectCity={onSelectCity}
        />,
      );

      expect(screen.getByText('انتخاب شهر برای نمایش دفاتر')).toBeInTheDocument();
      expect(screen.getByText('اصفهان')).toBeInTheDocument();

      fireEvent.click(screen.getByText('اصفهان'));
      expect(onSelectCity).toHaveBeenCalledWith(
        expect.objectContaining({ code: 'isfahan' }),
      );
    });

    it('renders OfficeDetailSheet with full details, counters, wait time and actions', () => {
      const onDirectAssign = vi.fn();
      const onBookAppointment = vi.fn();
      const onClose = vi.fn();

      render(
        <OfficeDetailSheet
          office={MOCK_OFFICE}
          isOpen={true}
          onClose={onClose}
          onDirectAssign={onDirectAssign}
          onBookAppointment={onBookAppointment}
        />,
      );

      expect(screen.getByText('پیشخوان مهرگان ونک')).toBeInTheDocument();
      expect(screen.getByText('کد دفتر: 1402')).toBeInTheDocument();
      expect(screen.getByText('8 باجه فعال')).toBeInTheDocument();
      expect(screen.getByText('2 نفر در صف')).toBeInTheDocument();
      expect(screen.getByText('زمان انتظار تقریبی: 1 دقیقه')).toBeInTheDocument();
      expect(screen.getByText('ثبت پرونده در این دفتر')).toBeInTheDocument();
      expect(screen.getByText('رزرو نوبت حضوری')).toBeInTheDocument();

      fireEvent.click(screen.getByText('ثبت پرونده در این دفتر'));
      expect(onDirectAssign).toHaveBeenCalledWith(MOCK_OFFICE);

      fireEvent.click(screen.getByText('رزرو نوبت حضوری'));
      expect(onBookAppointment).toHaveBeenCalledWith(MOCK_OFFICE);
    });

    it('filters offices with OfficesFilterBar', () => {
      const onSearchChange = vi.fn();
      const onStatusChange = vi.fn();
      const onMinRatingChange = vi.fn();

      render(
        <OfficesFilterBar
          searchQuery=""
          onSearchChange={onSearchChange}
          selectedStatus="all"
          onStatusChange={onStatusChange}
          minRating={0}
          onMinRatingChange={onMinRatingChange}
        />,
      );

      const input = screen.getByPlaceholderText('جستجوی نام یا کد دفتر...');
      fireEvent.change(input, { target: { value: '1402' } });
      expect(onSearchChange).toHaveBeenCalledWith('1402');

      fireEvent.click(screen.getByText('دفاتر فعال و آنلاین'));
      expect(onStatusChange).toHaveBeenCalledWith('registered_online');

      fireEvent.click(screen.getByText('برترین‌ها'));
      expect(onMinRatingChange).toHaveBeenCalledWith(4.5);
    });

    it('has zero axe accessibility violations on components', async () => {
      const { container } = render(
        <div>
          <MapThemeSelector currentTheme="standard-day" onSelectTheme={vi.fn()} />
          <OfficesFilterBar
            searchQuery="ونک"
            onSearchChange={vi.fn()}
            selectedStatus="registered_online"
            onStatusChange={vi.fn()}
            minRating={4.5}
            onMinRatingChange={vi.fn()}
          />
          <OfficeDetailSheet
            office={MOCK_OFFICE}
            isOpen={true}
            onClose={vi.fn()}
          />
        </div>,
      );

      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });
  });
});
