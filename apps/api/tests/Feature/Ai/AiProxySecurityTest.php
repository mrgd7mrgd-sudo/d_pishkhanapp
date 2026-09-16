<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use Illuminate\Support\Facades\File;

it('verifies ai-egress-proxy service codebase and configuration files exist (§8.1.4, HC-7, TASK-108)', function (): void {
    $proxyDir = base_path('../../services/ai-egress-proxy');
    expect(File::isDirectory($proxyDir))->toBeTrue("Directory {$proxyDir} does not exist");

    expect(File::exists("{$proxyDir}/main.go"))->toBeTrue()
        ->and(File::exists("{$proxyDir}/go.mod"))->toBeTrue()
        ->and(File::exists("{$proxyDir}/main_test.go"))->toBeTrue()
        ->and(File::exists("{$proxyDir}/Dockerfile"))->toBeTrue()
        ->and(File::exists("{$proxyDir}/README.md"))->toBeTrue();

    // docker/proxy-compose.yml and scripts/gen-mtls-certs.sh
    $composeFile = base_path('../../docker/proxy-compose.yml');
    $mtlsScript = base_path('../../scripts/gen-mtls-certs.sh');

    expect(File::exists($composeFile))->toBeTrue()
        ->and(File::exists($mtlsScript))->toBeTrue();
});

it('verifies ai-egress-proxy enforces security invariants in source code (§8.1.4, HC-7, TASK-108-T)', function (): void {
    $mainGo = File::get(base_path('../../services/ai-egress-proxy/main.go'));

    // 1. Mandatory mTLS support & TLS 1.3
    expect($mainGo)->toContain('tls.RequireAndVerifyClientCert')
        ->and($mainGo)->toContain('tls.VersionTLS13');

    // 2. Strict whitelist routes
    expect($mainGo)->toContain('/v1/chat/completions')
        ->and($mainGo)->toContain('/v1/audio/transcriptions')
        ->and($mainGo)->toContain('/healthz');

    // 3. Strict metadata-only logging (never prompt, never response)
    expect($mainGo)->toContain('type MetaLogEntry struct')
        ->and($mainGo)->not->toContain('Prompt string')
        ->and($mainGo)->not->toContain('Response string')
        ->and($mainGo)->not->toContain('Messages string');

    // 4. API Key injected by proxy, not exposed to client
    expect($mainGo)->toContain('OPENROUTER_API_KEY');
});

it('verifies OPENROUTER_API_KEY is never placed in Iran backend .env or repository files (HC-5, HC-7, TASK-108-T)', function (): void {
    $envExample = File::get(base_path('.env.example'));
    expect($envExample)->not->toContain('OPENROUTER_API_KEY=sk-');

    $rootEnvExample = File::get(base_path('../../.env.example'));
    expect($rootEnvExample)->not->toContain('OPENROUTER_API_KEY=sk-');

    // Check compose file uses env interpolation, not hardcoded key
    $proxyCompose = File::get(base_path('../../docker/proxy-compose.yml'));
    expect($proxyCompose)->toContain('OPENROUTER_API_KEY: ${OPENROUTER_API_KEY}')
        ->and($proxyCompose)->not->toContain('sk-or-');
});
