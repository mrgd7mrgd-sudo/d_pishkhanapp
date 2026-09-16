import os
import sys
import re

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

print("🛡️ Running Constraint Guard (Architecture §10.5, HC-1, HC-5)...")

# Check 1: HC-1 Native technologies prohibition
native_pat = re.compile(r'(react-native|flutter|\.kt\b|\.swift\b|capacitor|cordova|expo-)', re.IGNORECASE)
for root, dirs, files in os.walk('.'):
    dirs[:] = [d for d in dirs if d not in ('node_modules', 'vendor', 'prototype', '.git', 'dist')]
    for file in files:
        if file.endswith(('.json', '.ts', '.tsx', '.php')):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
                if native_pat.search(content):
                    print(f"::error::نقض HC-1 — ارجاع به فناوری Native در {filepath}")
                    sys.exit(1)
print("   ✅ HC-1 passed: No native app dependencies or code found.")

# Check 2: HC-5 Client-side AI prohibition
ai_pat = re.compile(r'(openrouter|openai|anthropic|generativelanguage|@google/genai)', re.IGNORECASE)
for client_dir in ('apps/citizen-pwa/src', 'apps/operator-desk/src'):
    for root, dirs, files in os.walk(client_dir):
        dirs[:] = [d for d in dirs if d not in ('node_modules', 'dist')]
        for file in files:
            # exclude test assertion files that explicitly test for absence
            if 'AiAssistant.test.tsx' in file:
                continue
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
                if ai_pat.search(content):
                    print(f"::error::نقض HC-5 — ارجاع به سرویس AI در کد کلاینت در {filepath}")
                    sys.exit(1)
print("   ✅ HC-5 passed: Zero AI keys or direct AI calls in client code.")

# Check 3: Dead prototype dependencies
dead_pat = re.compile(r'"(@google/genai|express|dotenv)"')
for app in ('apps/citizen-pwa', 'apps/operator-desk'):
    pkg_file = os.path.join(app, 'package.json')
    if os.path.exists(pkg_file):
        with open(pkg_file, 'r', encoding='utf-8') as f:
            if dead_pat.search(f.read()):
                print(f"::error::وابستگی مرده پروتوتایپ بازگشته است در {pkg_file}")
                sys.exit(1)
print("   ✅ Dead dependencies check passed.")

print("🛡️ All Constraint Guard checks passed successfully!")
