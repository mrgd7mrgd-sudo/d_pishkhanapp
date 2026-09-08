import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { App } from '../App';
import { deskRoutes } from '../routes';
import { ErrorBoundary } from '../shared/ui/ErrorBoundary';
import fs from 'fs';
import path from 'path';

describe('Operator Desk Shell & Architecture', () => {
  it('registers all 10 operator desk routes defined in Architecture §4.4', () => {
    const requiredPaths = [
      '/login',
      '/offers',
      '/workspace',
      '/workspace/:caseId',
      '/queue',
      '/delivery',
      '/delivery/:deliveryId/waybill',
      '/finance',
      '/reviews',
      '/office-profile',
    ];

    const registeredPaths = deskRoutes.map((r) => r.path);
    requiredPaths.forEach((p) => {
      expect(registeredPaths).toContain(p);
    });
  });

  it('renders root operator desk app cleanly without errors', () => {
    const { container } = render(<App />);
    expect(container).toBeDefined();
  });

  it('verifies that no service worker is registered for operator desk (Desktop-First)', () => {
    const swPath = path.resolve(__dirname, '../../public/sw.js');
    expect(fs.existsSync(swPath)).toBe(false);
  });

  it('handles component level errors in ErrorBoundary', () => {
    function BrokenComponent(): React.JSX.Element {
      throw new Error('Test desk error');
    }

    render(
      <ErrorBoundary>
        <BrokenComponent />
      </ErrorBoundary>,
    );

    expect(screen.getByText('خطای سیستمی')).toBeInTheDocument();
  });
});
