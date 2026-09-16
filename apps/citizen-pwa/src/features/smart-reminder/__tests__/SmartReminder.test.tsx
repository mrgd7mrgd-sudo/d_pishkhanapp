import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { axe } from 'vitest-axe';
import { SmartReminderPage } from '../ui/SmartReminderPage';

describe('Citizen Smart Reminder Slice (§4.4, §10.2, TASK-106, TASK-106-T)', () => {
  it('renders reminder list with due dates and required document checklists', () => {
    render(<SmartReminderPage />);

    expect(screen.getByText('یادآورهای هوشمند مراجعات و مدارک')).toBeInTheDocument();
    expect(
      screen.getByText('مراجعه حضوری تعویض کارت ملی هوشمند')
    ).toBeInTheDocument();
    expect(screen.getByText('اصل شناسنامه عکس‌دار')).toBeInTheDocument();
    expect(screen.getByText('کد پستی ۱۰ رقمی محل سکونت')).toBeInTheDocument();
    expect(screen.getByText('تمدید گواهی پایان خدمت و مدارک نظام وظیفه')).toBeInTheDocument();
  });

  it('allows toggling reminder active status', () => {
    render(<SmartReminderPage />);

    const toggleBtn1 = screen.getByTestId('toggle-rem-rem_1');
    expect(toggleBtn1).toHaveTextContent('یادآوری فعال');

    fireEvent.click(toggleBtn1);

    expect(toggleBtn1).toHaveTextContent('غیرفعال');
    expect(
      screen.getByText('تنظیمات یادآور هوشمند با موفقیت به‌روزرسانی شد.')
    ).toBeInTheDocument();
  });

  it('allows updating lead time minutes', () => {
    render(<SmartReminderPage />);

    const selectElements = screen.getAllByLabelText('زمان پیش از موعد برای یادآوری');
    const firstSelect = selectElements[0] as HTMLSelectElement;

    expect(firstSelect.value).toBe('120');
    fireEvent.change(firstSelect, { target: { value: '60' } });
    expect(firstSelect.value).toBe('60');
  });

  it('passes axe accessibility scan with zero violations', async () => {
    const { container } = render(<SmartReminderPage />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
