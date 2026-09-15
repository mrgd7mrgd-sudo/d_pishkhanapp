import React from 'react';
import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { axe } from 'vitest-axe';
import { CurrencyText, formatPersianNumber, toPersianDigits } from '../CurrencyText';

describe('CurrencyText Primitive Component (§4.1, §4.4, TASK-092, TASK-092-T)', () => {
  it('converts Rials to Tomans by default and formats with Persian digits and separators', () => {
    render(<CurrencyText amountRials={2_500_000} testId="wallet-balance" />);

    const element = screen.getByTestId('wallet-balance');
    expect(element).toBeInTheDocument();
    // 2,500,000 Rials = 250,000 Tomans
    expect(screen.getByText('۲۵۰٬۰۰۰')).toBeInTheDocument();
    expect(screen.getByText('تومان')).toBeInTheDocument();
    expect(element).toHaveAttribute('aria-label', '۲۵۰٬۰۰۰ تومان');
  });

  it('renders raw Rials when unit is set to rial', () => {
    render(<CurrencyText amountRials={3_400_000} unit="rial" testId="rial-fee" />);

    const element = screen.getByTestId('rial-fee');
    expect(element).toBeInTheDocument();
    expect(screen.getByText('۳٬۴۰۰٬۰۰۰')).toBeInTheDocument();
    expect(screen.getByText('ریال')).toBeInTheDocument();
    expect(element).toHaveAttribute('aria-label', '۳٬۴۰۰٬۰۰۰ ریال');
  });

  it('hides unit label when showUnit is false', () => {
    render(<CurrencyText amountRials={500_000} showUnit={false} testId="no-unit" />);

    const element = screen.getByTestId('no-unit');
    expect(element).toBeInTheDocument();
    expect(screen.getByText('۵۰٬۰۰۰')).toBeInTheDocument();
    expect(screen.queryByText('تومان')).not.toBeInTheDocument();
    expect(element).toHaveAttribute('aria-label', '۵۰٬۰۰۰');
  });

  it('handles zero balance properly', () => {
    render(<CurrencyText amountRials={0} testId="zero-balance" />);

    expect(screen.getByText('۰')).toBeInTheDocument();
    expect(screen.getByText('تومان')).toBeInTheDocument();
  });

  it('formatPersianNumber helper correctly separates thousands', () => {
    expect(formatPersianNumber(1000)).toBe('۱٬۰۰۰');
    expect(formatPersianNumber(1000000)).toBe('۱٬۰۰۰٬۰۰۰');
    expect(formatPersianNumber(0)).toBe('۰');
    expect(toPersianDigits('1234567890')).toBe('۱۲۳۴۵۶۷۸۹۰');
  });

  it('passes axe accessibility test with zero violations', async () => {
    const { container } = render(
      <main>
        <CurrencyText amountRials={12_000_000} />
      </main>
    );

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
