import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { Button } from '../primitives/Button';
import { Input } from '../primitives/Input';
import { Badge } from '../primitives/Badge';
import { StatusPill } from '../primitives/StatusPill';
import { Skeleton } from '../primitives/Skeleton';
import { Toast } from '../primitives/Toast';
import { Dialog } from '../primitives/Dialog';
import { Sheet } from '../primitives/Sheet';
import { CASE_STATUSES } from '@pishkhan/domain';

describe('UI-Kit 8 Base Primitives (§4.7)', () => {
  it('renders Button with accessibility and touch target size (≥ 44px)', () => {
    render(<Button>ارسال مدارک</Button>);
    const btn = screen.getByRole('button', { name: 'ارسال مدارک' });
    expect(btn).toBeInTheDocument();
    expect(btn.className).toContain('min-h-[44px]');
  });

  it('renders Input with accessible label and error state', () => {
    render(<Input id="test-input" label="کد ملی" error="کد ملی نامعتبر است" />);
    expect(screen.getByLabelText('کد ملی')).toBeInTheDocument();
    expect(screen.getByText('کد ملی نامعتبر است')).toBeInTheDocument();
  });

  it('renders Badge with custom variants', () => {
    render(<Badge variant="success">تأیید شده</Badge>);
    expect(screen.getByText('تأیید شده')).toBeInTheDocument();
  });

  it('renders StatusPill correctly for every single 11 CaseStatus domain values', () => {
    CASE_STATUSES.forEach((status) => {
      const { unmount } = render(<StatusPill status={status} />);
      const pill = screen.getByRole('status');
      expect(pill).toBeInTheDocument();
      unmount();
    });
  });

  it('renders Skeleton with aria-hidden', () => {
    const { container } = render(<Skeleton className="w-24 h-6" />);
    expect(container.firstChild).toHaveAttribute('aria-hidden', 'true');
  });

  it('renders Toast with alert role and close button', () => {
    const handleClose = vi.fn();
    render(<Toast title="موفقیت" onClose={handleClose}>پیام با موفقیت ثبت شد.</Toast>);
    expect(screen.getByRole('alert')).toBeInTheDocument();
    const closeBtn = screen.getByRole('button', { name: 'بستن' });
    fireEvent.click(closeBtn);
    expect(handleClose).toHaveBeenCalledOnce();
  });

  it('renders Dialog modal and responds to Escape key', () => {
    const handleClose = vi.fn();
    render(
      <Dialog isOpen={true} onClose={handleClose} title="عنوان دیالوگ">
        محتوای تستی دیالوگ
      </Dialog>,
    );

    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText('عنوان دیالوگ')).toBeInTheDocument();

    fireEvent.keyDown(window, { key: 'Escape' });
    expect(handleClose).toHaveBeenCalledOnce();
  });

  it('renders Sheet modal and responds to Escape key', () => {
    const handleClose = vi.fn();
    render(
      <Sheet isOpen={true} onClose={handleClose} title="عنوان شیت">
        محتوای شیت
      </Sheet>,
    );

    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText('عنوان شیت')).toBeInTheDocument();

    fireEvent.keyDown(window, { key: 'Escape' });
    expect(handleClose).toHaveBeenCalledOnce();
  });
});
