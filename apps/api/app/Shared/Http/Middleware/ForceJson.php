<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceJson
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! str_contains((string) $request->header('Accept'), 'text/event-stream')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
