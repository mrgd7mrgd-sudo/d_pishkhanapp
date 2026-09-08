# 📊 تحلیل کامل پروژه: «سامانه جامع خدمات شهروندی و پیشخوان هوشمند»

> تاریخ تحلیل: ۱۴۰۵/۰۶/۱۷ (2026-09-08) — تحلیل کل پروژه خروجی Google AI Studio

---

## ۱. هویت پروژه

این پروژه یک **خروجی استاندارد Google AI Studio** است (Applets مربوط به AI Studio) که با قالب `react-example` ساخته شده. طبق `metadata.json` و `README.md`، یک **سوپراپلیکیشن خدمات شهروندی** است با این شعار:

> «سوپراپلیکیشن جامع خدمات شهروندی، نقشه و اساین (اختصاص) دفاتر پیشخوان، رهگیری گرافیکی پرونده و مخزن مدارک»

شبیه‌سازی یک «اسنپ دکتر» اما برای خدمات دولتی/پیشخوانی.

> ⚠️ **نکته مهم:** این یک **پروتوتایپ Front-End کامل با داده‌های Mock** است — **هیچ بک‌اند، دیتابیس یا API واقعی ندارد.**

- لینک AI Studio پروژه: `https://ai.studio/apps/10277aec-ad0e-4f5e-9b3b-c8a3a272ccba`
- مجوزهای درخواستی در metadata: `geolocation` (البته در کد استفاده نشده)
- قابلیت اعلام‌شده: `MAJOR_CAPABILITY_SERVER_SIDE_GEMINI_API` (پیاده‌سازی نشده)

---

## ۲. زبان و تکنولوژی‌ها (Stack)

| لایه | تکنولوژی | نسخه | نقش |
|---|---|---|---|
| زبان اصلی | **TypeScript** | ~5.8 | کل کد تایپ‌دار (`types.ts` قوی با ~۳۵۰ خط اینترفیس) |
| کتابخانه UI | **React** | 19 | Function Components + Hooks (بدون Class) |
| بیلد‌تول | **Vite** | 6 | سرور توسعه پورت ۳۰۰۰ + بیلد پروداکشن |
| استایل | **Tailwind CSS v4** | 4.1 | پلاگین رسمی `@tailwindcss/vite` (بدون tailwind.config — رویکرد CSS-First) |
| انیمیشن | **Motion** (Framer Motion) | 12 | ترنزیشن‌های بین تب‌ها، AnimatePresence |
| نقشه | **Leaflet** | 1.9 | نقشه تعاملی OSM/CartoDB برای دفاتر پیشخوان |
| آیکون | **lucide-react** | 0.546 | تمام آیکون‌ها |
| AI (اعلامی) | **@google/genai** | 2.4 | در `package.json` هست ولی **در کد استفاده نشده!** |
| CSS Module | `LiquidTabBar.module.css` | — | افکت شیشه‌ای «Liquid Glass» برای تب‌بار پایین |

**زبان محتوایی:** کاملاً **فارسی و RTL** — `index.html` با `lang="fa" dir="rtl"` و فونت **وزیرمتن (Vazirmatn)** از Google Fonts.

**فایل قفل وابستگی:** `bun.lock` — یعنی پروژه با **Bun** ساخته/نصب شده است.

---

## ۳. ساختار فایل‌ها و بخش‌ها

```
├── index.html              → شل RTL + فونت وزیرمتن + CSS لِفلت
├── vite.config.ts          → پلاگین React + Tailwind، alias «@»
├── package.json            → وابستگی‌ها (bun.lock → با Bun ساخته شده)
├── tsconfig.json           → ES2022، bundler resolution، paths @/*
├── .env.example            → GEMINI_API_KEY + APP_URL
└── src/
    ├── main.tsx            → نقطه ورود (StrictMode + createRoot)
    ├── App.tsx             → مغز برنامه (~۶۵۰ خط) — تمام State ها اینجاست
    ├── types.ts            → مدل‌های داده (خدمت، دفتر، پرونده، مشاوره...)
    ├── index.css           → Tailwind + افکت‌های Glassmorphism دستی + رفع باگ لِفلت
    ├── data/
    │   ├── mockData.ts     → ۲,۲۱۵ خط داده شبیه‌سازی (خدمات، دفاتر، پرونده‌ها، تراکنش‌ها)
    │   └── consultationData.ts → داده مشاوران و پلن‌های اشتراک
    ├── components/         → ۲۴ کامپوننت
    └── assets/images/      → ۱۲ تصویر سه‌بعدی (~۶.۵ مگابایت!)
```

### ۳.۱. بخش‌های اصلی (Views) در حالت «شهروند»

| تب | کامپوننت | حجم | کارکرد |
|---|---|---|---|
| خانه | `Header` + `WalletCard` + `CategoryFilter` + `CtaSlider` | — | داشبورد، کیف پول، دسته‌بندی خدمات، بنر تبلیغاتی |
| خدمات | `ServicesCatalogView` + `ServiceCard` | 27KB | کاتالوگ کامل خدمات (هویتی، خودرو، سلامت، بانک، پست...) با فیلتر و جستجو |
| نقشه | `OfficesMap.tsx` | **71KB** | نقشه Leaflet با ۳ تم (OSM / CartoDB Voyager / Positron)، مارکرهای SVG سفارشی، فیلتر امتیاز/مسافت، رزرو نوبت و اساین مستقیم |
| پرونده‌ها | `CaseTrackingView.tsx` | **39KB** | رهگیری گرافیکی پرونده با تایم‌لاین ۸ مرحله‌ای + کدهای علّت بازگشت (مدرک تار، منقضی...) |
| پروفایل | `UserProfileView.tsx` | **103KB** | مخزن مدارک، سوابق، انتصابات، نمایندگی حقوقی |
| مشاوره | `ConsultationHubView` + `ConsultationDetailModal` + `ConsultationLiveSessionModal` | ~70KB | هاب مشاوره تخصصی (مالیاتی، بیمه، حقوقی، شهرداری) با انتخاب مشاور، تعرفه دقیقه‌ای، جلسه زنده |

### ۳.۲. حالت «میز کار دفتر پیشخوان» (Operator Mode)

- `OfficePortalView.tsx` → **بزرگ‌ترین فایل پروژه: ۲۴۷ کیلوبایت / ۴,۲۳۱ خط!** پنل کامل اپراتور دفتر: مدیریت صف، بررسی مدارک، اعلام نقص، تغییر وضعیت پرونده‌ها.
- `OfficeLoginView.tsx` → ورود دفاتر.

### ۳.۳. مودال‌ها (۹ عدد)

1. `SmartChatbotModal` — چت‌بات «هوشمند» ✅ اما **قانونی/Rule-based است، نه Gemini واقعی** (پاسخ‌ها با تطبیق کلمات کلیدی روی mockData تولید می‌شوند)
2. `VoiceAssistantModal` — دستیار صوتی ⚠️ **شبیه‌سازی‌شده** — نه Web Speech API واقعی، فقط دکمه‌های نمونه از پیش تعریف‌شده
3. `ServiceRequestModal` (28KB) — جریان درخواست خدمت به سبک اسنپ: انتخاب دفتر، آپلود مدارک، امضای تعهد
4. `AppointmentModal` — نوبت‌دهی حضوری
5. `LegalDelegationModal` — نمایندگی حقوقی/خانوادگی
6. `AdvisorRegistrationModal` — ثبت‌نام مشاور
7. `BusinessSubscriptionModal` — پلن‌های اشتراک کسب‌وکار
8. `CitizenLoginView` (21KB) — ورود شهروند (کد ملی + OTP شبیه‌سازی‌شده)
9. `ClientQualityCheckModal` — کنترل کیفیت مدارک

---

## ۴. مدل داده (types.ts)

اینترفیس‌های کلیدی:

- **`CitizenService`** — خدمت شهروندی: تگ‌های `online / semi-online / in-person`، مدارک لازم، هزینه (تومان)، مدارک الزامی با کد (`requiredDocCodes`)، وزارت‌خانه مربوطه
- **`PishkhanOffice`** — دفتر پیشخوان: کد، مدیر، وضعیت عضویت (`registered_online / registered_offline / unregistered`)، امتیاز، مدال‌ها، تخصص‌ها، مختصات جغرافیایی، شمارنده‌های فعال، صف لحظه‌ای
- **`CaseRequest` + `CaseTimelineStep`** — پرونده با ۸ وضعیت: `searching_office → assigned_to_office → expert_review → government_inquiry → action_required → ready_for_issue → completed / rejected`
- **`StepTurnOwner`** — نوبتِ اقدام با: `citizen / office / government / postal / system` (مدل واقعی گردش کار دولتی)
- **`ReturnReasonDefinition`** — دیکشنری ۱۰ کدی علل بازگشت مدارک (`DOC_BLUR`, `DOC_EXPIRED`, `ELIGIBILITY_FAIL`, `PRESENCE_REQUIRED`...)
- **`ConsultationAdvisor` / `ConsultationSession` / `BusinessSubscriptionPlan`** — اکوسیستم مشاوره با امتیاز تفکیکی (دقت، فن بیان، صبوری) و تعرفه سه‌گانه (چت / دقیقه / بررسی عمیق پرونده)
- **`LegalDelegation` / `ChatMessage` / `WalletTransaction` / `Appointment`** — نمایندگی حقوقی، پیام‌رسان، کیف پول و نوبت‌ها

---

## ۵. معماری و جریان داده

- **مدیریت State:** ۱۰۰٪ با `useState` در `App.tsx` — هیچ Redux/Zustand/Context/Router وجود ندارد. Navigation با `activeTab` و `AnimatePresence` و جهت‌گیری RTL انیمیت می‌شود (جهت ترنزیشن بر اساس ترتیب تب‌ها محاسبه می‌شود).
- **Authentication:** دو حالت (`citizen` / `office`) با فلگ‌های boolean — پروفایل ورودی از `INITIAL_CITIZEN_PROFILE` در mockData.
- **داده:** تماماً Hardcoded در `mockData.ts` (۲,۲۱۵ خط) — شامل ۱۰+ دسته خدمت، دفاتر پیشخوان تهران با مختصات واقعی، پرونده‌ها، تراکنش‌های کیف پول، دیکشنری علل بازگشت مدارک.
- **نقشه:** لوکیشن کاربر هاردکد روی میدان ولیعصر تهران (`lat: 35.7480, lng: 51.4120`) — با اینکه `metadata.json` مجوز geolocation خواسته، `navigator.geolocation` هیچ‌جا صدا زده نمی‌شود.
- **هیچ:** `fetch` واقعی، `localStorage`، `sessionStorage`، WebSocket، Router یا Error Boundary وجود ندارد. تنها درخواست‌های شبکه، تایل‌های نقشه OSM/CartoDB و فونت گوگل هستند.

---

## ۶. نقاط قوت 💪

1. **UI فوق‌سفارشی:** افکت Liquid Glass سه‌بعدی (dock شیشه‌ای، لنز محدب با پراکندگی رنگین‌کمانی)، انیمیشن‌های روان RTL، TabBar مولتی‌فایلی با CSS Module.
2. **تایپینگ قوی:** دامنه‌های اتحادیه (Union Types) دقیق که مدل واقعی گردش کار دولتی را شبیه‌سازی می‌کند.
3. **عمق محصول:** جزئیات واقع‌گرایانه مثل دیکشنری ۱۰ کدی علل نقص مدرک با پیام پیش‌فرض، مدال‌های دفاتر، صف لحظه‌ای، شمارشگر مهلت، امتیاز تفکیک‌شده مشاوران (دقت/فن بیان/صبوری).
4. **RTL بی‌نقص** و طراحی Mobile-First با Navbar پایین ثابت + Badge وضعیت پرونده‌ها روی تب‌بار.

---

## ۷. نقاط ضعف و ریسک‌ها ⚠️

1. **فایل‌های هیولا:** `OfficePortalView` (۴,۲۳۱ خط)، `UserProfileView` (۱,۸۷۴ خط)، `mockData` (۲,۲۱۵ خط)، `OfficesMap` (۷۱KB) — نیاز جدی به شکستن به کامپوننت‌های کوچک‌تر و Custom Hooks.
2. **وابستگی‌های مرده:** `@google/genai`، `express`، `dotenv` نصب شده‌اند ولی **هیچ جا استفاده نمی‌شوند** (metadata ادعای `SERVER_SIDE_GEMINI_API` دارد ولی پیاده‌سازی نشده).
3. **هیچ Persistence‌ای نیست** — رفرش صفحه همه‌چیز (پرونده‌ها، کیف پول) ریست می‌شود.
4. **بدون تست، بدون Router، بدون Error Boundary** و حجم تصاویر (~۶.۵MB) بدون بهینه‌سازی (Lazy Loading ندارند).
5. **ورود دفتر Fake است:** `setCurrentOfficeDesk(MOCK_OFFICES[0])` — همیشه دفتر اول لاگین می‌شود.
6. **چت‌بات و دستیار صوتی:** اسمش «هوشمند/AI» است ولی منطق آن تطبیق ساده کلیدواژه روی داده Mock است — هیچ مدل زبانی واقعی پشتش نیست.

---

## ۸. نحوه اجرا

```bash
# ۱. نصب وابستگی‌ها
npm install          # یا bun install (فایل bun.lock موجود است)

# ۲. ساخت فایل .env.local و قرار دادن کلید
#    GEMINI_API_KEY="YOUR_KEY"   (الان در کد استفاده نمی‌شود ولی قالب می‌خواهد)

# ۳. اجرا
npm run dev          # http://localhost:3000
npm run build        # بیلد پروداکشن
npm run preview      # پیش‌نمایش بیلد
npm run lint         # چک TypeScript (tsc --noEmit)
```

> ⚠️ **توجه:** `node_modules` در این پوشه موجود نیست، پس قبل از اجرا حتماً `npm install` بزنید.

---

## ۹. جدول خلاصه آماری

| شاخص | مقدار |
|---|---|
| زبان | TypeScript (100%) |
| فریم‌ورک | React 19 + Vite 6 |
| استایل | Tailwind CSS v4 |
| تعداد کامپوننت | ۲۴ فایل در `src/components` |
| بزرگ‌ترین فایل | `OfficePortalView.tsx` — ۲۴۷KB / ۴,۲۳۱ خط |
| بزرگ‌ترین فایل داده | `mockData.ts` — ۹۵KB / ۲,۲۱۵ خط |
| حجم تصاویر | ~۶.۵ مگابایت (۱۲ تصویر JPG سه‌بعدی) |
| بک‌اند | ❌ ندارد (کاملاً Mock) |
| هوش مصنوعی واقعی | ❌ ندارد (چت‌بات Rule-based) |
| ذخیره‌سازی | ❌ ندارد |
| تست | ❌ ندارد |

---

## ۱۰. جمع‌بندی

یک **پروتوتایپ ظاهری بسیار حرفه‌ای و «دمو-آماده»** با TypeScript / React 19 / Tailwind 4 که کل چرخه عمر خدمت شهروندی (کشف خدمت ← اساین دفتر ← آپلود مدارک ← رهگیری ← تحویل) + پنل اپراتور دفتر + هاب مشاوره را در قالب یک اپ موبایل‌گونه فارسی RTL شبیه‌سازی می‌کند.

اما از نظر مهندسی، یک **فقط-Front-End با داده Mock** است: هوش مصنوعی (Gemini) که در قالب ادعا شده به کد پیاده نشده؛ داده‌ای ذخیره نمی‌شود؛ و برای تبدیل به محصول واقعی به موارد زیر نیاز دارد:

1. **بک‌اند واقعی** (API + دیتابیس) جایگزین `mockData.ts`
2. **اتصال چت‌بات به Gemini API** (کتابخانه `@google/genai` همین الان نصب است!)
3. **State Management** (Zustand/Redux) و **Router** برای ناوبری واقعی
4. **Persistence** با localStorage در کوتاه‌مدت و API در بلندمدت
5. **بازآرایی (Refactoring)** فایل‌های غول‌پیکر به کامپوننت‌ها و هوک‌های کوچک‌تر
6. **بهینه‌سازی تصاویر** (WebP + Lazy Loading)
7. **افزودن تست‌ها** (Vitest + React Testing Library)

