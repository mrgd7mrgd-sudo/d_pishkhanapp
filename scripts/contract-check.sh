#!/usr/bin/env bash
set -euo pipefail

echo "==> Running API Contract Check (Architecture §4.5)..."

# 1. Export OpenAPI spec
(cd apps/api && php artisan scramble:export)

# 2. Generate client
pnpm generate:client

# 3. Check for uncommitted changes in generated client directory
if ! git diff --exit-code packages/api-client/src/generated; then
    echo "❌ CONTRACT CHECK FAILED!"
    echo "The generated API client is out of sync with openapi.json."
    echo "Please run 'pnpm generate:client' and commit the resulting changes in packages/api-client/src/generated."
    exit 1
fi

echo "✅ CONTRACT CHECK PASSED: API Client is perfectly in sync with OpenAPI specification."
