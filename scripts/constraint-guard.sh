#!/usr/bin/env bash
set -eo pipefail

echo "🛡️ Running Constraint Guard (Architecture §10.5, HC-1, HC-5)..."

# Check 1: HC-1 Native technologies prohibition
echo "1. Checking Native tech references (HC-1)..."
if grep -rniE '(react-native|flutter|\.kt\b|\.swift\b|capacitor|cordova|expo-)' \
   --include='*.json' --include='*.ts' --include='*.tsx' --include='*.php' \
   --exclude-dir=node_modules --exclude-dir=vendor --exclude-dir=prototype --exclude-dir=.git . ; then
  echo "::error::نقض HC-1 — ارجاع به فناوری Native یافت شد"
  exit 1
fi
echo "   ✅ HC-1 passed: No native app dependencies or code found."

# Check 2: HC-5 Client-side AI prohibition
echo "2. Checking Client AI calls/keys (HC-5)..."
if grep -rniE '(openrouter|openai|anthropic|generativelanguage|@google/genai)' apps/citizen-pwa/src apps/operator-desk/src ; then
  echo "::error::نقض HC-5 — ارجاع به سرویس AI در کد کلاینت"
  exit 1
fi
echo "   ✅ HC-5 passed: Zero AI keys or direct AI calls in client code."

# Check 3: Dead prototype dependencies
echo "3. Checking dead prototype dependencies..."
if grep -qE '"(@google/genai|express|dotenv)"' apps/*/package.json ; then
  echo "::error::وابستگی مرده پروتوتایپ بازگشته است"
  exit 1
fi
echo "   ✅ Dead dependencies check passed."

echo "🛡️ All Constraint Guard checks passed successfully!"