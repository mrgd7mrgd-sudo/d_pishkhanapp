import React from 'react';
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { ServiceCatalogView } from '../ui/ServiceCatalogView';
import type { ServiceCategory, ServiceItem } from '../types';

const mockCategories: ServiceCategory[] = [
  {
    id: 'cat-identity',
    title: 'هویتی و سجلی',
    slug: 'identity',
    icon_name: 'IdCard',
    color: '#059669',
    display_order: 1,
    services_count: 5,
  },
  {
    id: 'cat-driving',
    title: 'گواهینامه و خودرو',
    slug: 'driving',
    icon_name: 'Car',
    color: '#2563eb',
    display_order: 2,
    services_count: 3,
  },
];

const mockServices: ServiceItem[] = [
  {
    id: 'svc-national-id',
    title: 'صدور مجدد کارت ملی هوشمند',
    slug: 'national-id-reissue',
    category: {
      id: 'cat-identity',
      title: 'هویتی و سجلی',
      color: '#059669',
    },
    tags: ['semi-online', 'in-person'],
    description: 'درخواست صدور مجدد کارت ملی هوشمند به علت مفقودی یا آسیب‌دیدگی.',
    requirements: ['حضور متقاضی جهت ثبت اثر انگشت الزامی است', 'سن بالای ۱۵ سال تمام'],
    required_documents: [
      {
        code: 'IDENT_BIRTH_CERT',
        title: 'شناسنامه عکس‌دار',
        is_mandatory: true,
        accepts: ['image/jpeg', 'image/png'],
      },
      {
        code: 'PERSONAL_PHOTO',
        title: 'عکس پرسنلی جدید',
        is_mandatory: true,
        accepts: ['image/jpeg'],
      },
    ],
    estimated_days: {
      min: 15,
      max: 30,
      label: '۱۵ تا ۳۰ روز کاری',
    },
    fee_rials: 750000,
    department: 'سازمان ثبت احوال کشور',
    is_popular: true,
    is_new: false,
    image: {
      avif: '/img/services/national-id-640.avif',
      webp: '/img/services/national-id-640.webp',
      width: 640,
      height: 360,
      blurhash: 'L6PZfSi_.AyE',
    },
    requires_in_person: true,
    supports_delivery: false,
  },
  {
    id: 'svc-driving-license-renewal',
    title: 'تعویض گواهینامه رانندگی',
    slug: 'driving-license-renewal',
    category: {
      id: 'cat-driving',
      title: 'گواهینامه و خودرو',
      color: '#2563eb',
    },
    tags: ['online'],
    description: 'تمدید اعتبار و تعویض گواهینامه‌های رانندگی منقضی شده با ارسال پستی.',
    requirements: ['گواهی معاینه پزشکی معتبر', 'عدم سوء پیشینه'],
    required_documents: [
      {
        code: 'OLD_LICENSE',
        title: 'اصل گواهینامه قبلی',
        is_mandatory: true,
        accepts: ['image/jpeg', 'image/png'],
      },
    ],
    estimated_days: {
      min: 5,
      max: 10,
      label: '۵ تا ۱۰ روز کاری',
    },
    fee_rials: 500000,
    department: 'پلیس راهور فراجا',
    is_popular: false,
    is_new: true,
    image: {
      avif: '/img/services/license-640.avif',
      webp: '/img/services/license-640.webp',
      width: 640,
      height: 360,
    },
    requires_in_person: false,
    supports_delivery: true,
  },
];

describe('Citizen Service Catalog Slice (§4.1, §4.3, §4.8, TASK-045, TASK-045-T)', () => {
  let queryClient: QueryClient;
  const mockFetch = vi.fn();

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    queryClient = new QueryClient({
      defaultOptions: {
        queries: {
          retry: false,
          staleTime: 0,
        },
      },
    });

    mockFetch.mockImplementation(async (url: string) => {
      if (url.includes('/categories')) {
        return {
          ok: true,
          json: async () => ({ data: mockCategories }),
        };
      }

      if (url.includes('/services')) {
        const parsedUrl = new URL(url, 'http://localhost');
        const tag = parsedUrl.searchParams.get('tag');
        const search = parsedUrl.searchParams.get('search');
        const catId = parsedUrl.searchParams.get('category_id');

        let filtered = [...mockServices];
        if (catId) {
          filtered = filtered.filter((s) => s.category.id === catId);
        }
        if (tag) {
          filtered = filtered.filter((s) => s.tags.includes(tag as 'online'));
        }
        if (search) {
          filtered = filtered.filter(
            (s) => s.title.includes(search) || s.description.includes(search),
          );
        }

        return {
          ok: true,
          json: async () => ({
            data: filtered,
            meta: {
              next_cursor: null,
              prev_cursor: null,
              per_page: 15,
            },
          }),
        };
      }

      return {
        ok: false,
        status: 404,
        json: async () => ({ detail: 'Not Found' }),
      };
    });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    queryClient.clear();
  });

  const renderComponent = () => {
    return render(
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/services']}>
          <ServiceCatalogView />
        </MemoryRouter>
      </QueryClientProvider>,
    );
  };

  it('renders categories pills and loads service cards with images and zero CLS attributes', async () => {
    renderComponent();

    await screen.findByTestId('category-pill-cat-identity');
    expect(screen.getByTestId('category-pill-cat-driving')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByText('صدور مجدد کارت ملی هوشمند')).toBeInTheDocument();
      expect(screen.getByText('تعویض گواهینامه رانندگی')).toBeInTheDocument();
    });

    const images = screen.getAllByRole('img');
    expect(images.length).toBeGreaterThanOrEqual(2);

    for (const img of images) {
      expect(img).toHaveAttribute('width', '640');
      expect(img).toHaveAttribute('height', '360');
      expect(img).toHaveAttribute('loading', 'lazy');
      expect(img).toHaveAttribute('decoding', 'async');
    }
  });

  it('normalizes Arabic characters to Persian (كارت ملي -> کارت ملی) and filters services', async () => {
    renderComponent();

    await waitFor(() => {
      expect(screen.getByText('صدور مجدد کارت ملی هوشمند')).toBeInTheDocument();
    });

    const searchInput = screen.getByRole('searchbox');

    await act(async () => {
      fireEvent.change(searchInput, { target: { value: 'كارت ملي' } });
    });

    await waitFor(() => {
      expect(searchInput).toHaveValue('کارت ملی');
    });

    await waitFor(() => {
      expect(screen.getByText('صدور مجدد کارت ملی هوشمند')).toBeInTheDocument();
      expect(screen.queryByText('تعویض گواهینامه رانندگی')).not.toBeInTheDocument();
    });
  });

  it('filters services by tag (online / semi-online / in-person)', async () => {
    renderComponent();

    await waitFor(() => {
      expect(screen.getByText('صدور مجدد کارت ملی هوشمند')).toBeInTheDocument();
    });

    const onlineFilterBtn = screen.getByRole('button', { name: 'غیرحضوری' });
    await act(async () => {
      fireEvent.click(onlineFilterBtn);
    });

    await waitFor(() => {
      expect(screen.getByText('تعویض گواهینامه رانندگی')).toBeInTheDocument();
      expect(screen.queryByText('صدور مجدد کارت ملی هوشمند')).not.toBeInTheDocument();
    });
  });

  it('filters services by category pill selection', async () => {
    renderComponent();

    const categoryBtn = await screen.findByTestId('category-pill-cat-identity');
    await act(async () => {
      fireEvent.click(categoryBtn);
    });

    await waitFor(() => {
      expect(screen.getByText('صدور مجدد کارت ملی هوشمند')).toBeInTheDocument();
      expect(screen.queryByText('تعویض گواهینامه رانندگی')).not.toBeInTheDocument();
    });
  });

  it('opens service detail sheet upon selecting a service card and shows required documents', async () => {
    renderComponent();

    await waitFor(() => {
      expect(screen.getByText('صدور مجدد کارت ملی هوشمند')).toBeInTheDocument();
    });

    const serviceCard = screen.getByTestId('service-card-svc-national-id');
    await act(async () => {
      fireEvent.click(serviceCard);
    });

    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeInTheDocument();
      expect(screen.getByText('مدارک لازم')).toBeInTheDocument();
      expect(screen.getByText('شناسنامه عکس‌دار')).toBeInTheDocument();
      expect(screen.getByText('عکس پرسنلی جدید')).toBeInTheDocument();
      expect(screen.getByText('شرایط و پیش‌نیازها')).toBeInTheDocument();
      expect(screen.getByText('حضور متقاضی جهت ثبت اثر انگشت الزامی است')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'ثبت درخواست خدمت' })).toBeInTheDocument();
    });

    const closeBtn = screen.getByRole('button', { name: 'بستن' });
    await act(async () => {
      fireEvent.click(closeBtn);
    });

    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });

  it('displays empty state with clear filters button when search returns no matches', async () => {
    renderComponent();

    await waitFor(() => {
      expect(screen.getByText('صدور مجدد کارت ملی هوشمند')).toBeInTheDocument();
    });

    const searchInput = screen.getByRole('searchbox');
    await act(async () => {
      fireEvent.change(searchInput, { target: { value: 'عبارت ناموجود نامعتبر' } });
    });

    await waitFor(() => {
      expect(screen.getByTestId('catalog-empty-state')).toBeInTheDocument();
      expect(screen.getByText('خدمتی با این مشخصات یافت نشد')).toBeInTheDocument();
    });

    const clearFiltersBtn = screen.getByRole('button', { name: 'حذف فیلترها' });
    await act(async () => {
      fireEvent.click(clearFiltersBtn);
    });

    await waitFor(() => {
      expect(screen.getByText('صدور مجدد کارت ملی هوشمند')).toBeInTheDocument();
    });
  });

  it('satisfies accessibility requirements with zero axe violations across all states', async () => {
    const { container } = renderComponent();

    await waitFor(() => {
      expect(screen.getByText('صدور مجدد کارت ملی هوشمند')).toBeInTheDocument();
    });

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
