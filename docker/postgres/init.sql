-- ===================================================================
-- سامانه جامع خدمات شهروندی و پیشخوان هوشمند
-- اسکریپت راه‌اندازی اولیه پایگاه داده PostgreSQL 16 + PostGIS
-- ===================================================================

CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- پیکربندی جستجوی متن فارسی مطابق §۶.۳ سند معماری
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_ts_config WHERE cfgname = 'persian'
    ) THEN
        CREATE TEXT SEARCH CONFIGURATION persian (COPY = simple);
    END IF;
END $$;
