<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('security headers configuration contains all mandatory §7.8 headers', function () {
    $confPath = base_path('../../docker/nginx/security-headers.conf');
    expect(File::exists($confPath))->toBeTrue();

    $content = File::get($confPath);
    expect($content)->toContain('Strict-Transport-Security');
    expect($content)->toContain('X-Content-Type-Options');
    expect($content)->toContain('X-Frame-Options');
    expect($content)->toContain('Referrer-Policy');
    expect($content)->toContain('Permissions-Policy');
    expect($content)->toContain('Content-Security-Policy');

    // Architecture HC-5 enforcement: Content-Security-Policy must not allow external AI endpoints in connect-src
    expect($content)->not->toContain('openrouter.ai');
    expect($content)->not->toContain('generativelanguage.googleapis.com');
    expect($content)->not->toContain('api.openai.com');
});

test('docker compose prod config enforces APP_DEBUG false in production', function () {
    $composePath = base_path('../../docker/compose.prod.yml');
    expect(File::exists($composePath))->toBeTrue();

    $content = File::get($composePath);
    expect($content)->toContain('APP_DEBUG: "false"');
    expect($content)->toContain('APP_ENV: production');
});
