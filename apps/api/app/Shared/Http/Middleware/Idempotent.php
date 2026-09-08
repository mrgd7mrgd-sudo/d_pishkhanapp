<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class Idempotent
{
    public const HEADER_NAME = 'Idempotency-Key';

    public const TTL_SECONDS = 86400; // 24 hours

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header(self::HEADER_NAME);

        // If no idempotency key provided, proceed normally
        if (! is_string($idempotencyKey) || trim($idempotencyKey) === '') {
            return $next($request);
        }

        $idempotencyKey = trim($idempotencyKey);
        $requestHash = hash('sha256', $request->getContent());
        $cacheKey = 'idem:'.$idempotencyKey;
        $lockKey = 'lock:'.$cacheKey;

        // 1. Check Redis cache first
        /** @var array{status: int, body: array<string, mixed>, hash: string}|null $cached */
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            if ($cached['hash'] !== $requestHash) {
                return $this->buildConflictResponse($request);
            }

            return new JsonResponse($cached['body'], $cached['status']);
        }

        // 2. Check Database record if not in Redis
        $record = DB::table('idempotency_keys')
            ->where('key', $idempotencyKey)
            ->where('expires_at', '>', CarbonImmutable::now())
            ->first();

        if ($record !== null) {
            if ($record->request_hash !== $requestHash) {
                return $this->buildConflictResponse($request);
            }

            $body = json_decode($record->response_body, true);
            $status = (int) $record->response_status;

            // Warm up Redis
            Cache::put($cacheKey, [
                'status' => $status,
                'body' => $body,
                'hash' => $record->request_hash,
            ], self::TTL_SECONDS);

            return new JsonResponse($body, $status);
        }

        // 3. Acquire distributed atomic lock to prevent concurrent executions
        $lock = Cache::lock($lockKey, 30);

        if (! $lock->get()) {
            // Another concurrent request is currently processing with this key
            // Wait up to 5 seconds or return 409
            $acquired = $lock->block(5);
            if (! $acquired) {
                return $this->buildConflictResponse($request);
            }

            // Check if prior finished while we were waiting
            $cachedAfterWait = Cache::get($cacheKey);
            if ($cachedAfterWait !== null) {
                $lock->release();
                if ($cachedAfterWait['hash'] !== $requestHash) {
                    return $this->buildConflictResponse($request);
                }

                return new JsonResponse($cachedAfterWait['body'], $cachedAfterWait['status']);
            }
        }

        try {
            $response = $next($request);

            if ($response instanceof JsonResponse) {
                $status = $response->getStatusCode();
                $body = $response->getData(true);

                // Cache in Redis for 24 hours
                Cache::put($cacheKey, [
                    'status' => $status,
                    'body' => $body,
                    'hash' => $requestHash,
                ], self::TTL_SECONDS);

                // Persist in DB
                DB::table('idempotency_keys')->updateOrInsert(
                    ['key' => $idempotencyKey],
                    [
                        'actor_id' => $request->user()?->getAuthIdentifier(),
                        'endpoint' => $request->path(),
                        'request_hash' => $requestHash,
                        'response_body' => json_encode($body, JSON_THROW_ON_ERROR),
                        'response_status' => $status,
                        'expires_at' => CarbonImmutable::now()->addSeconds(self::TTL_SECONDS),
                        'created_at' => CarbonImmutable::now(),
                        'updated_at' => CarbonImmutable::now(),
                    ]
                );
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    private function buildConflictResponse(Request $request): JsonResponse
    {
        $requestId = (string) $request->header(RequestId::HEADER_NAME, 'req_conflict');

        return new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/idempotency-conflict',
            'title' => 'تداخل در کلید یکتا (Idempotency Key)',
            'status' => 409,
            'code' => 'IDEMPOTENCY_KEY_PAYLOAD_MISMATCH',
            'detail' => 'این کلید یکتا قبلاً با محتوای درخواست متفاوتی ارسال شده است.',
            'instance' => $request->path(),
            'request_id' => $requestId,
            'errors' => null,
        ], 409);
    }
}
