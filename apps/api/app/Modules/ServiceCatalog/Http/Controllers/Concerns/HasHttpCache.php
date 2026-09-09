<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait HasHttpCache
{
    /**
     * Return JSON response with ETag, Cache-Control, and 304 Not Modified support (§5.6, TASK-041).
     *
     * @param  array<string, mixed>  $data
     */
    protected function cachedJsonResponse(Request $request, array $data, int $status = 200): JsonResponse|Response
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $etag = '"'.md5($encoded !== false ? $encoded : '').'"';

        $headers = [
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
        ];

        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch !== null && trim($ifNoneMatch) === $etag) {
            return new Response('', 304, $headers);
        }

        return new JsonResponse($data, $status, $headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
