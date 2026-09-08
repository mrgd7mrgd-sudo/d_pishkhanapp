#!/usr/bin/env bash
set -euo pipefail

echo "==> Exporting OpenAPI 3.1 specification from Laravel backend..."
(cd apps/api && php artisan scramble:export)

echo "==> Generating TypeScript TanStack Query client using Orval..."
pnpm generate:client

echo "==> Client generated successfully!"
