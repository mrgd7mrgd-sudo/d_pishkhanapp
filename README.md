# 🏛️ سامانه جامع خدمات شهروندی و پیشخوان هوشمند (Pishkhan Monorepo)

> مخزن یکپارچه (Monorepo) سامانه بر پایه **pnpm Workspaces** و **Turborepo** مطابق با سند معماری نسخه ۱.۰.۱ (`ARCHITECTURE.md`).

---

## 📁 ساختار مخزن (Monorepo Structure — §۴.۱)

```text
pishkhan/
├── pnpm-workspace.yaml         # پیکربندی ورک‌اسپیس pnpm
├── turbo.json                  # پایپ‌لاین وظایف Turborepo
├── package.json                # اسکریپت‌های ریشه (بدون وابستگی برنامه‌ای)
├── .editorconfig               # استانداردهای فرمت کد
├── .nvmrc                      # نسخه مصوب Node.js (v22)
│
├── apps/                       # اپلیکیشن‌های مستقل
│   ├── citizen-pwa/            # 📱 اپلیکیشن پیش‌رونده شهروند (Vite + React 19 + PWA + Offline)
│   ├── operator-desk/          # 🖥️ میز کار اپراتور پیشخوان (Desktop-First)
│   └── api/                    # 🐘 بک‌اند و API مرکزی (Laravel 12 + Octane)
│
├── packages/                   # پکیج‌ها و کتابخانه‌های مشترک
│   ├── ui-kit/                 # سیستم طراحی Liquid Glass
│   ├── api-client/             # کلاینت TypeScript تولیدشده از قرارداد OpenAPI
│   ├── domain/                 # تعاریف Enumها، تایپ‌ها و منطق خالص دامنه
│   ├── config-eslint/          # تنظیمات مشترک ESLint با سقف ۴۰۰ خط
│   ├── config-typescript/      # تنظیمات مشترک تایپ‌اسکریپت (Strict)
│   └── testing/                # کتابخانه‌های تست مشترک، Fixtureها و شبیه‌سازها
│
└── prototype/                  # آرشیو پروتوتایپ اولیه و مخزن داده‌های مرجع (mockData)
```

---

## 🚀 دستورات اصلی

```bash
# نصب وابستگی‌ها در کل مخزن
pnpm install

# اجرای وظایف با Turborepo
pnpm build        # بیلد تمام بسته‌ها
pnpm dev          # اجرای محیط توسعه
pnpm test         # اجرای تست‌ها
pnpm lint         # بررسی لینتر
pnpm typecheck    # بررسی انواع تایپ‌اسکریپت
```

---

## 📋 نیازمندی‌های سیستم

- **Node.js:** `>= 20.0.0` (توصیه‌شده: `22.23.1`)
- **pnpm:** `>= 9.0.0`
- **Docker & Docker Compose**
