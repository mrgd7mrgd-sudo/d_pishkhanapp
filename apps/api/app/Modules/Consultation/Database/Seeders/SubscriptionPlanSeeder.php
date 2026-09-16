<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Database\Seeders;

use App\Modules\Consultation\Domain\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Seed the 3 business subscription plans from prototype (BUSINESS_SUBSCRIPTION_PLANS, TASK-120).
     */
    public function run(): void
    {
        $plans = [
            [
                'plan_key' => 'plan-guild-basic',
                'title' => 'اشتراک پایه اصناف و کسب‌وکارهای خرد',
                'badge' => 'ویژه فروشگاه‌ها و اصناف',
                'is_popular' => false,
                'price_monthly_rials' => 4900000, // 490,000 Tomans = 4,900,000 Rials
                'target_audience' => 'مغازه‌داران، خرده‌فروشان، اصناف پوشاک، رستوران‌ها و خدمات',
                'features' => [
                    'بررسی ماهانه کارپوشه سامانه مودیان و صورتحساب‌های الکترونیکی',
                    '۴۵ دقیقه مکالمه تلفنی امن با مشاور اختصاصی مالیاتی و بیمه در ماه',
                    'مشاوره متنی نامحدود جهت رفع خطاهای سامانه‌ای',
                    'تنظیم ۱ لایحه اعتراضی مالیاتی یا اداره کار در ماه',
                    'ارتباط مستقیم جهت انجام پرونده در دفاتر پیشخوان بدون نوبت',
                ],
                'quota' => [
                    'monthly_tax_review' => 1,
                    'labor_dispute_defense' => 1,
                    'phone_minutes' => 45,
                    'text_chats' => 999, // unlimited representation
                ],
                'is_active' => true,
            ],
            [
                'plan_key' => 'plan-business-pro',
                'title' => 'اشتراک طلایی شرکت‌ها و استارتاپ‌ها',
                'badge' => 'پیشنهاد پیشخوانو',
                'is_popular' => true,
                'price_monthly_rials' => 12900000, // 1,290,000 Tomans = 12,900,000 Rials
                'target_audience' => 'شرکت‌های مسئولیت محدود و سهامی، استارتاپ‌ها و شرکت‌های بازرگانی',
                'features' => [
                    'پشتیبانی جامع مالیات، ارزش افزوده، گزارشات فصلی و سامانه مودیان',
                    '۱۲۰ دقیقه مکالمه تلفنی مستقیم با وکلای پایه یک و حسابداران رسمی',
                    'تنظیم تا ۳ لایحه دفاعیه مالیاتی، هیئت حل اختلاف یا ماده ۱۰۰',
                    'بازبینی قراردادهای پرسنلی و چک‌لیست بیمه‌ای جهت جلوگیری از جرایم بازرسی',
                    'پنل اختصاصی مدیریت پرونده‌ها و ارجاع آنی به باجه‌های پیشخوان',
                ],
                'quota' => [
                    'monthly_tax_review' => 3,
                    'labor_dispute_defense' => 3,
                    'phone_minutes' => 120,
                    'text_chats' => 999,
                ],
                'is_active' => true,
            ],
            [
                'plan_key' => 'plan-enterprise',
                'title' => 'اشتراک سازمانی و کارگاه‌های تولیدی',
                'badge' => 'جامع‌ترین',
                'is_popular' => false,
                'price_monthly_rials' => 28900000, // 2,890,000 Tomans = 28,900,000 Rials
                'target_audience' => 'کارخانجات، پیمانکاران عمرانی، شرکت‌های واردات/صادرات و هلدینگ‌ها',
                'features' => [
                    'حسابرس و وکیل مقیم اختصاصی برای پیگیری تمامی امور اداری و دولتی',
                    '۳۰۰ دقیقه تماس مستقیم با تیم کارشناسان ارشد',
                    'بررسی نامحدود پرونده‌ها، تنظیم لوایح شورای عالی مالیاتی و دیوان عدالت',
                    'پشتیبانی اسناد مناقصات ستاد، گواهی‌های صلاحیت و درگاه ملی مجوزها',
                    'تخفیف ۲۰ درصدی بر روی تمامی کارمزدهای خدمات دفاتر پیشخوان کشور',
                ],
                'quota' => [
                    'monthly_tax_review' => 100,
                    'labor_dispute_defense' => 100,
                    'phone_minutes' => 300,
                    'text_chats' => 999,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::query()->updateOrCreate(
                ['plan_key' => $p['plan_key']],
                array_merge($p, ['id' => (string) Str::uuid()])
            );
        }
    }
}
