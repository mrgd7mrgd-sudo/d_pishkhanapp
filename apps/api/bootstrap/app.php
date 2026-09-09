<?php

declare(strict_types=1);

use App\Exceptions\Handler;
use App\Modules\Identity\Http\Middleware\EnsureOperatorSessionValid;
use App\Modules\Identity\Http\Middleware\EnsureTokenNotExpiringSoon;
use App\Shared\Http\Middleware\AuditLog;
use App\Shared\Http\Middleware\EnsureOfficeScope;
use App\Shared\Http\Middleware\ForceJson;
use App\Shared\Http\Middleware\Idempotent;
use App\Shared\Http\Middleware\MultiTierOtpRateLimiter;
use App\Shared\Http\Middleware\RequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

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
        ], append: [
            'throttle:global',
        ]);

        $middleware->alias([
            'idempotent' => Idempotent::class,
            'audit.log' => AuditLog::class,
            'rate.limit.otp' => MultiTierOtpRateLimiter::class,
            'token.slide' => EnsureTokenNotExpiringSoon::class,
            'operator.session.valid' => EnsureOperatorSessionValid::class,
            'office.scope' => EnsureOfficeScope::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
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
