import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { App } from '../App';
import { routes } from '../routes';
import { ErrorBoundary } from '../shared/ui/ErrorBoundary';
import fs from 'fs';
import path from 'path';

describe('Citizen PWA Shell & Routing Architecture', () => {
  it('App.tsx satisfies line limit requirement (≤ 80 lines)', () => {
    const appPath = path.resolve(__dirname, '../App.tsx');
    const content = fs.readFileSync(appPath, 'utf8');
    const lineCount = content.split('\n').length;
    expect(lineCount).toBeLessThanOrEqual(80);
  });

  it('registers all 27 distinct citizen routes defined in Architecture §4.4', () => {
    expect(routes).toHaveLength(27);

    const paths = routes.map((r) => r.path);
    const expectedPaths = [
      '/',
      '/services',
      '/services/:categoryId',
      '/services/:categoryId/:serviceId',
      '/request/:serviceId',
      '/map',
      '/map/offices/:officeId',
      '/cases',
      '/cases/:trackingCode',
      '/cases/:trackingCode/chat',
      '/consultation',
      '/consultation/advisors/:advisorId',
      '/consultation/sessions/:sessionId',
      '/profile',
      '/profile/personal-info',
      '/profile/documents',
      '/profile/appointments',
      '/profile/reminders',
      '/profile/messages',
      '/profile/delegations',
      '/profile/settings',
      '/profile/support',
      '/profile/about',
      '/wallet',
      '/wallet/transactions',
      '/login',
      '/offline',
    ];

    expectedPaths.forEach((p) => {
      expect(paths).toContain(p);
    });
  });

  it('renders root App shell cleanly without errors', () => {
    const { container } = render(<App />);
    expect(container).toBeDefined();
  });

  it('catches route-level or component error in ErrorBoundary without crashing the whole app', () => {
    function Bomb(): React.JSX.Element {
      throw new Error('Simulated route crash');
    }

    render(
      <ErrorBoundary>
        <Bomb />
      </ErrorBoundary>,
    );

    expect(screen.getByText('خطایی رخ داده است')).toBeInTheDocument();
    expect(screen.getByText('تلاش مجدد')).toBeInTheDocument();
  });
});
