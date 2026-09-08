<?php

declare(strict_types=1);

use App\Exceptions\Handler;
use App\Shared\Http\Middleware\AuditLog;
use App\Shared\Http\Middleware\ForceJson;
use App\Shared\Http\Middleware\Idempotent;
use App\Shared\Http\Middleware\MultiTierOtpRateLimiter;
use App\Shared\Http\Middleware\RequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RequestId::class);

        $middleware->api(prepend: [
            ForceJson::class,
        ]);

        $middleware->alias([
            'idempotent' => Idempotent::class,
            'audit.log' => AuditLog::class,
            'rate.limit.otp' => MultiTierOtpRateLimiter::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return Handler::render($e, $request);
            }

            return null;
        });
    })->create();
