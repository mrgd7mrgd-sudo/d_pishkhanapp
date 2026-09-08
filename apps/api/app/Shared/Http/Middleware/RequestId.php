<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RequestId
{
    public const HEADER_NAME = 'X-Request-Id';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header(self::HEADER_NAME);

        if (! is_string($requestId) || trim($requestId) === '') {
            $requestId = 'req_'.Str::ulid();
        }

        $request->headers->set(self::HEADER_NAME, $requestId);

        $response = $next($request);

        $response->headers->set(self::HEADER_NAME, $requestId);

        return $response;
    }
}
