/**
 * Jalali Persian Date & Time Formatting Utilities (Architecture §4.7)
 * Preserves raw ISO-8601 timestamps in data models while rendering
 * localized, formatted Persian date strings in the presentation layer.
 */

const toPersianDigits = (input: string): string => {
  const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  return input.replace(/\d/g, (d) => persianDigits[Number.parseInt(d, 10)] ?? d);
};

export function formatJalaliDate(isoDate: string | undefined | null): string {
  if (!isoDate) return '';
  try {
    const d = new Date(isoDate);
    if (Number.isNaN(d.getTime())) return '';
    const formatted = new Intl.DateTimeFormat('fa-IR', {
      calendar: 'persian',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    }).format(d);
    return toPersianDigits(formatted);
  } catch {
    return isoDate;
  }
}

export function formatJalaliDateTime(isoDate: string | undefined | null): string {
  if (!isoDate) return '';
  try {
    const d = new Date(isoDate);
    if (Number.isNaN(d.getTime())) return '';
    const formatted = new Intl.DateTimeFormat('fa-IR', {
      calendar: 'persian',
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(d);
    return toPersianDigits(formatted);
  } catch {
    return isoDate;
  }
}

export function formatCurrencyRials(amount: number): string {
  const formatted = amount.toLocaleString('fa-IR');
  return `${formatted} ریال`;
}
