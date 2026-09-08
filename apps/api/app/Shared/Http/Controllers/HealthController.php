<?php

declare(strict_types=1);

namespace App\Shared\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class HealthController
{
    /**
     * Check system health across DB, Redis, and Object Storage.
     *
     * @response 200 {
     *   "status": "ok",
     *   "db": "ok",
     *   "redis": "ok",
     *   "storage": "ok",
     *   "version": "1.0.0"
     * }
     * @response 503 {
     *   "status": "unhealthy",
     *   "db": "fail",
     *   "redis": "ok",
     *   "storage": "ok",
     *   "version": "1.0.0"
     * }
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'db' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
        ];

        $isHealthy = ! in_array('fail', $checks, true);

        return new JsonResponse([
            'status' => $isHealthy ? 'ok' : 'unhealthy',
            'db' => $checks['db'],
            'redis' => $checks['redis'],
            'storage' => $checks['storage'],
            'version' => (string) config('scramble.info.version', '1.0.0'),
        ], $isHealthy ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::select('SELECT 1');

            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function checkRedis(): string
    {
        try {
            Cache::put('healthcheck_ping', 'pong', 5);
            $val = Cache::get('healthcheck_ping');

            return $val === 'pong' ? 'ok' : 'fail';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function checkStorage(): string
    {
        try {
            Storage::disk('local')->put('.healthcheck', 'ok');
            $read = Storage::disk('local')->get('.healthcheck');
            Storage::disk('local')->delete('.healthcheck');

            return $read === 'ok' ? 'ok' : 'fail';
        } catch (Throwable) {
            return 'fail';
        }
    }
}
