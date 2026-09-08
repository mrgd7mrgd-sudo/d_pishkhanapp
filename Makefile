.PHONY: help up down restart ps logs psql redis minio test lint typecheck clean

help:
	@echo "سامانه جامع خدمات شهروندی و پیشخوان هوشمند"
	@echo "دستورات:"
	@echo "  make up         - اجرای سرویس‌های داکر (Postgres, Redis, MinIO, Mailpit)"
	@echo "  make down       - توقف سرویس‌های داکر"
	@echo "  make ps         - وضعیت کانتینرها"
	@echo "  make logs       - مشاهده لاگ‌ها"
	@echo "  make psql       - اتصال به شل Postgres"
	@echo "  make redis      - اتصال به redis-cli"
	@echo "  make test       - اجرای تست‌های پروژه"
	@echo "  make lint       - اجرای لینتر پروژه"
	@echo "  make typecheck  - اعتبارسنجی تایپ‌ها"

up:
	docker compose -f docker/compose.yml --env-file docker/.env.example up -d

down:
	docker compose -f docker/compose.yml down

restart: down up

ps:
	docker compose -f docker/compose.yml ps

logs:
	docker compose -f docker/compose.yml logs -f

psql:
	docker compose -f docker/compose.yml exec postgres psql -U pishkhan -d pishkhan

redis:
	docker compose -f docker/compose.yml exec redis redis-cli

test:
	pnpm turbo run test

lint:
	pnpm turbo run lint

typecheck:
	pnpm turbo run typecheck

clean:
	pnpm turbo run clean
