import { describe, expect, it } from 'vitest';
import {
  extractTrigrams,
  normalizeDigits,
  normalizePersianText,
  removeDiacritics,
  trigramSimilarity,
} from './persian-text.js';

describe('persian-text domain helper', () => {
  it('normalizes Arabic Yeh and Kaf to Persian equivalents', () => {
    expect(normalizePersianText('كارت ملي')).toBe('کارت ملی');
    expect(normalizePersianText('حتي')).toBe('حتی');
  });

  it('removes diacritics (tashkeel/harakat)', () => {
    expect(removeDiacritics('کِتَابٌ')).toBe('کتاب');
    expect(normalizePersianText('مُحَمَّد')).toBe('محمد');
  });

  it('converts half-space (ZWNJ) to standard space', () => {
    expect(normalizePersianText('پیش‌خوان هوشمند')).toBe('پیش خوان هوشمند');
  });

  it('normalizes Persian and Arabic digits to ASCII digits', () => {
    expect(normalizeDigits('۰۹۱۲۳۴۵۶۷۸۹')).toBe('09123456789');
    expect(normalizeDigits('٠١٢٣٤٥٦٧٨٩')).toBe('0123456789');
    expect(normalizePersianText('تلفن: ۰۲۱-۸۸۸۸')).toBe('تلفن: 021-8888');
  });

  it('extracts trigrams with standard padding', () => {
    const trigrams = extractTrigrams('ملی');
    // '  ملی '
    expect(trigrams).toContain('  م');
    expect(trigrams).toContain(' مل');
    expect(trigrams).toContain('ملی');
    expect(trigrams).toContain('لی ');
  });

  it('calculates fuzzy trigram similarity', () => {
    const exactSim = trigramSimilarity('کارت ملی', 'کارت ملی');
    expect(exactSim).toBe(1.0);

    const typoSim = trigramSimilarity('کارت ملی', 'کارط ملی');
    expect(typoSim).toBeGreaterThanOrEqual(0.5);

    const noSpaceSim = trigramSimilarity('کارت ملی', 'کارتملی');
    expect(noSpaceSim).toBeGreaterThanOrEqual(0.5);

    const differentSim = trigramSimilarity('گذرنامه', 'شناسنامه');
    expect(differentSim).toBeLessThan(0.4);
  });

  it('normalizes 20 sample strings with exact parity to PHP implementation', () => {
    const samples = [
      { raw: 'كارت ملي', expected: 'کارت ملی' },
      { raw: 'ثبتِ اَحْوَال', expected: 'ثبت احوال' },
      { raw: 'پیش‌خوان', expected: 'پیش خوان' },
      { raw: 'گذرنامهٔ فوری', expected: 'گذرنامه فوری' },
      { raw: 'شناسنامه ۰۱۲۳۴۵۶۷۸۹', expected: 'شناسنامه 0123456789' },
      { raw: 'ماليات بر ارزش افزوده', expected: 'مالیات بر ارزش افزوده' },
      { raw: 'شماره پرونده: ٠١٢٣٤', expected: 'شماره پرونده: 01234' },
      { raw: 'وكالت‌نامه رسمى', expected: 'وکالت نامه رسمی' },
      { raw: 'شهردارى منطقه ۲', expected: 'شهرداری منطقه 2' },
      { raw: 'پروانه كسب و كار', expected: 'پروانه کسب و کار' },
      { raw: 'بيمه تأمين اجتماعى', expected: 'بیمه تامین اجتماعی' },
      { raw: 'استعلامِ گواهينامه', expected: 'استعلام گواهینامه' },
      { raw: 'سندِ ملكيِ تك‌برگ', expected: 'سند ملکی تک برگ' },
      { raw: 'كارت هوشمند   سوخت  ', expected: 'کارت هوشمند سوخت' },
      { raw: 'تعويض كارت پايان خدمت', expected: 'تعویض کارت پایان خدمت' },
      { raw: 'نظام وظيفه عمومى', expected: 'نظام وظیفه عمومی' },
      { raw: 'عوارضِ خودرو ١٤٠٣', expected: 'عوارض خودرو 1403' },
      { raw: 'خلافي خودرو و موتور', expected: 'خلافی خودرو و موتور' },
      { raw: 'تأییدیهٔ تحصیلی', expected: 'تاییدیه تحصیلی' },
      { raw: 'صدورِ مجدد شناسنامه', expected: 'صدور مجدد شناسنامه' },
    ];

    expect(samples.length).toBe(20);

    for (const { raw, expected } of samples) {
      expect(normalizePersianText(raw)).toBe(expected);
    }
  });
});

