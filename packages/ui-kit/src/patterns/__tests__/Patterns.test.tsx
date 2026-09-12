import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { render, screen, act } from '@testing-library/react';
import { axe } from 'vitest-axe';
import { TurnOwnerChip } from '../TurnOwnerChip';
import { CountdownTimer } from '../CountdownTimer';
import { Timeline, type TimelineItem } from '../Timeline';

describe('UI Kit Patterns (§4.7, TASK-063, TASK-063-T)', () => {
  describe('TurnOwnerChip', () => {
    it('renders citizen owner with Persian label and accessibility attributes', () => {
      render(<TurnOwnerChip owner="citizen" />);
      const chip = screen.getByRole('status');
      expect(chip).toBeInTheDocument();
      expect(screen.getByText('شهروند (متقاضی)')).toBeInTheDocument();
    });

    it('renders with custom labelOverride', () => {
      render(<TurnOwnerChip owner="citizen" labelOverride="نوبت شماست" />);
      expect(screen.getByText('نوبت شماست')).toBeInTheDocument();
    });

    it('renders all 5 turn owners without error', () => {
      const owners = ['citizen', 'office', 'government', 'postal', 'system'] as const;
      const { container } = render(
        <div>
          {owners.map((owner) => (
            <TurnOwnerChip key={owner} owner={owner} />
          ))}
        </div>
      );
      expect(container.querySelectorAll('[role="status"]')).toHaveLength(5);
    });

    it('passes axe accessibility checks for TurnOwnerChip', async () => {
      const { container } = render(<TurnOwnerChip owner="office" />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });
  });

  describe('CountdownTimer', () => {
    it('renders initial hours, minutes, and seconds in Persian numerals', () => {
      render(<CountdownTimer initialSeconds={3665} />);
      const timer = screen.getByRole('timer');
      expect(timer).toBeInTheDocument();
      // 3665 sec = 1 hour, 1 minute, 5 seconds
      expect(screen.getByTestId('hours')).toHaveTextContent('۰۱');
      expect(screen.getByTestId('minutes')).toHaveTextContent('۰۱');
      expect(screen.getByTestId('seconds')).toHaveTextContent('۰۵');
    });

    it('counts down and triggers onExpire when reaches zero', () => {
      vi.useFakeTimers();
      const onExpire = vi.fn();
      render(<CountdownTimer initialSeconds={2} onExpire={onExpire} />);

      act(() => {
        vi.advanceTimersByTime(1000);
      });
      expect(screen.getByTestId('seconds')).toHaveTextContent('۰۱');

      act(() => {
        vi.advanceTimersByTime(1000);
      });
      expect(screen.getByTestId('seconds')).toHaveTextContent('۰۰');
      expect(onExpire).toHaveBeenCalledTimes(1);

      vi.useRealTimers();
    });

    it('renders expired label when initialSeconds is zero', () => {
      render(<CountdownTimer initialSeconds={0} />);
      const timer = screen.getByRole('timer');
      expect(timer).toHaveAttribute('aria-label', 'مهلت اقدام به پایان رسیده است');
    });
  });

  describe('Timeline', () => {
    const mockItems: TimelineItem[] = [
      {
        id: '1',
        title: 'ثبت پرونده و پرداخت',
        status: 'done',
        turnOwner: 'system',
        occurredAt: '2026-09-10T10:00:00Z',
      },
      {
        id: '2',
        title: 'بررسی کارشناس دفتر',
        status: 'warning',
        turnOwner: 'citizen',
        turnOwnerLabel: 'نوبت شماست',
        description: 'تصویر کارت ملی ناخواناست.',
        officeNote: 'لطفاً تصویر جدید واضح با کادر مشخص بارگذاری شود.',
        occurredAt: '2026-09-11T12:00:00Z',
      },
      {
        id: '3',
        title: 'استعلام دولتی',
        status: 'pending',
        turnOwner: 'government',
      },
    ];

    it('renders timeline steps with accessible list semantics', () => {
      render(<Timeline items={mockItems} />);
      const list = screen.getByRole('list', { name: 'تایم‌لاین پیشرفت پرونده' });
      expect(list).toBeInTheDocument();
      expect(screen.getAllByRole('listitem')).toHaveLength(3);
      expect(screen.getByText('ثبت پرونده و پرداخت')).toBeInTheDocument();
      expect(screen.getByText('بررسی کارشناس دفتر')).toBeInTheDocument();
      expect(screen.getByText(/تصویر کارت ملی ناخواناست/)).toBeInTheDocument();
      expect(screen.getByText(/لطفاً تصویر جدید واضح/)).toBeInTheDocument();
    });

    it('uses custom renderDate function when provided', () => {
      const renderDate = vi.fn((d: string) => `تاریخ: ${d.slice(0, 10)}`);
      render(<Timeline items={mockItems} renderDate={renderDate} />);
      expect(renderDate).toHaveBeenCalled();
      expect(screen.getByText('تاریخ: 2026-09-10')).toBeInTheDocument();
    });

    it('passes axe accessibility checks for Timeline', async () => {
      const { container } = render(<Timeline items={mockItems} />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });
  });
});
