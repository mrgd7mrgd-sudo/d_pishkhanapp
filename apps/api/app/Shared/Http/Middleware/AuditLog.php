<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use App\Shared\Audit\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuditLog
{
    /**
     * Handle an incoming request and audit sensitive mutating API operations.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only audit mutating actions (POST, PUT, PATCH, DELETE) that succeed
        if ($request->isMethodSafe() || ! $response->isSuccessful()) {
            return $response;
        }

        $path = $request->path();
        $action = 'api.'.strtolower($request->method()).'.'.str_replace('/', '.', trim($path, '/'));

        AuditLogger::record(
            action: $action,
            subject: null,
            changes: [
                'method' => $request->method(),
                'path' => $path,
                'status' => $response->getStatusCode(),
            ],
            context: [
                'query' => $request->query(),
            ]
        );

        return $response;
    }
}
