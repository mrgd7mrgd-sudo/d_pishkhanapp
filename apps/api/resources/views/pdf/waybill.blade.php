<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بارنامه رسمی مرسوله پیشخوان</title>
    <style>
        body {
            font-family: 'vazirmatn', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 10pt;
            color: #1a202c;
            line-height: 1.5;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2b6cb0;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            color: #2b6cb0;
            text-align: center;
        }
        .subtitle {
            font-size: 9pt;
            color: #718096;
            text-align: center;
        }
        .section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .section-table th, .section-table td {
            border: 1px solid #cbd5e0;
            padding: 6px 8px;
            font-size: 9pt;
        }
        .section-header {
            background-color: #ebf8ff;
            color: #2b6cb0;
            font-weight: bold;
            font-size: 10pt;
        }
        .field-label {
            background-color: #f7fafc;
            font-weight: bold;
            width: 25%;
            color: #4a5568;
        }
        .field-value {
            width: 25%;
        }
        .security-box {
            background-color: #fffaf0;
            border: 1px solid #dd6b20;
            padding: 8px;
            margin-top: 10px;
            font-size: 8.5pt;
        }
        .signature-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 33.33%;
            border: 1px dashed #a0aec0;
            height: 60px;
            vertical-align: top;
            padding: 5px;
            font-size: 8.5pt;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 25%; font-size: 8.5pt; color: #4a5568;">
                تاریخ صدور: {{ $delivery->created_at->format('Y/m/d H:i') }}<br>
                شماره پیگیری: {{ $delivery->tracking_barcode }}
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="title">سامانه ملی پیشخوان هوشمند</div>
                <div class="subtitle">بارنامه و رسید رسمی تحویل مرسوله مدارک دولتی</div>
            </td>
            <td style="width: 25%; text-align: left; font-size: 8.5pt;">
                وضعیت: {{ $delivery->delivery_status->label() }}<br>
                نوع ارسال: {{ $delivery->courier_type->label() }}
            </td>
        </tr>
    </table>

    <!-- اطلاعات مبدأ و مقصد -->
    <table class="section-table">
        <tr>
            <th colspan="4" class="section-header">مشخصات دفتر مبدأ و گیرنده مرسوله</th>
        </tr>
        <tr>
            <td class="field-label">دفتر پیشخوان مبدأ:</td>
            <td class="field-value">{{ $delivery->office->name ?? 'دفتر پیشخوان' }} (کد {{ $delivery->office->code ?? '—' }})</td>
            <td class="field-label">نام متقاضی / گیرنده:</td>
            <td class="field-value">{{ $delivery->case->citizen->full_name ?? 'متقاضی محترم' }}</td>
        </tr>
        <tr>
            <td class="field-label">استان و شهر مبدأ:</td>
            <td class="field-value">{{ $delivery->office->province_code ?? '—' }} - {{ $delivery->office->city ?? '—' }}</td>
            <td class="field-label">کد پستی مقصد:</td>
            <td class="field-value" style="direction: ltr; text-align: right;">{{ $delivery->destination_postal_code }}</td>
        </tr>
        <tr>
            <td class="field-label">نشانی کامل مقصد:</td>
            <td colspan="3" class="field-value">{{ $delivery->destination_address }}</td>
        </tr>
    </table>

    <!-- مشخصات مرسوله و سند -->
    <table class="section-table">
        <tr>
            <th colspan="4" class="section-header">مشخصات سند و بسته‌بندی</th>
        </tr>
        <tr>
            <td class="field-label">نوع مدرک:</td>
            <td class="field-value">{{ $delivery->doc_type->label() }}</td>
            <td class="field-label">عنوان سند:</td>
            <td class="field-value">{{ $delivery->doc_type_name }}</td>
        </tr>
        <tr>
            <td class="field-label">شماره سریال سند:</td>
            <td class="field-value">{{ $delivery->doc_serial_number ?? 'ثبت نشده' }}</td>
            <td class="field-label">بسته پلمب امنیتی:</td>
            <td class="field-value">{{ $delivery->is_sealed_pack ? 'بله (دارای هولوگرام و پلمب)' : 'خیر' }}</td>
        </tr>
        <tr>
            <td class="field-label">الزام عودت لاشه سند:</td>
            <td class="field-value">{{ $delivery->require_old_doc_return ? 'الزامی (دریافت قبل از تحویل)' : 'اختیاری / بدون عودت' }}</td>
            <td class="field-label">روش پرداخت کرایه:</td>
            <td class="field-value">{{ $delivery->payment_method->label() }}</td>
        </tr>
        <tr>
            <td class="field-label">کرایه ارسال (ریال):</td>
            <td class="field-value">{{ number_format($delivery->shipping_fee_rials) }} ریال</td>
            <td class="field-label">اطلاعات سفیر / پیک:</td>
            <td class="field-value">{{ $delivery->courier_name ? $delivery->courier_name . ' (' . $delivery->courier_phone . ')' : 'در انتظار تخصیص سفیر' }}</td>
        </tr>
    </table>

    <!-- هشدارهای امنیتی -->
    <div class="security-box">
        <strong>نکات امنیتی و تحویل:</strong><br>
        ۱. تحویل این مرسوله صرفاً پس از دریافت و ثبت کد تأیید ۶ رقمی یک‌بارمصرف (OTP) از متقاضی امکان‌پذیر است.<br>
        @if($delivery->require_old_doc_return)
        ۲. <strong>توجه ویژه سفیر:</strong> قبل از تحویل سند جدید، دریافت لاشه یا مدرک قدیمی منقضی‌شده الزامی است.<br>
        @endif
        @if($delivery->security_note)
        ۳. یادداشت امنیتی دفتر: {{ $delivery->security_note }}
        @endif
    </div>

    <!-- بخش امضا و مهر -->
    <table class="signature-table">
        <tr>
            <td>
                <strong>مهر و امضای دفتر پیشخوان مبدأ</strong><br><br>
                متصدی ارسال: ....................
            </td>
            <td>
                <strong>امضا و اثر انگشت سفیر تحویل</strong><br><br>
                سفیر: ....................
            </td>
            <td>
                <strong>امضا و تأیید دریافت توسط متقاضی</strong><br><br>
                گیرنده: ....................
            </td>
        </tr>
    </table>
</body>
</html>
