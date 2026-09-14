<?php

declare(strict_types=1);

namespace App\Shared\Resilience;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * CircuitBreaker (Architecture §5.8, §8.5, TASK-075).
 * Resilient circuit breaker supporting CLOSED, OPEN, HALF_OPEN states and Queue Job Middleware.
 */
final class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';

    public const STATE_OPEN = 'open';

    public const STATE_HALF_OPEN = 'half_open';

    public function __construct(
        public readonly string $serviceName,
        public readonly int $failures = 5,
        public readonly int $cooldownSeconds = 300
    ) {}

    public function getState(): string
    {
        $openUntil = (int) Cache::get($this->openKey(), 0);
        $now = time();

        if ($openUntil > $now) {
            return self::STATE_OPEN;
        }

        if ($openUntil > 0 && $openUntil <= $now) {
            return self::STATE_HALF_OPEN;
        }

        return self::STATE_CLOSED;
    }

    public function isAvailable(): bool
    {
        return $this->getState() !== self::STATE_OPEN;
    }

    public function getFailures(): int
    {
        return (int) Cache::get($this->failureKey(), 0);
    }

    public function recordSuccess(): void
    {
        Cache::forget($this->failureKey());
        Cache::forget($this->openKey());
    }

    public function recordFailure(): void
    {
        $currentFailures = (int) Cache::get($this->failureKey(), 0) + 1;
        Cache::put($this->failureKey(), $currentFailures, $this->cooldownSeconds * 2);

        if ($currentFailures >= $this->failures) {
            Cache::put($this->openKey(), time() + $this->cooldownSeconds, $this->cooldownSeconds);
        }
    }

    public function reset(): void
    {
        Cache::forget($this->failureKey());
        Cache::forget($this->openKey());
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function execute(callable $callback): mixed
    {
        if ($this->getState() === self::STATE_OPEN) {
            throw new CircuitBreakerOpenException(
                "Circuit breaker for [{$this->serviceName}] is open. Cooldown: {$this->cooldownSeconds}s."
            );
        }

        try {
            $result = $callback();
            $this->recordSuccess();

            return $result;
        } catch (Throwable $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    /**
     * Middleware handler for Laravel Queue Jobs (§5.8).
     */
    public function handle(object $job, Closure $next): void
    {
        if ($this->getState() === self::STATE_OPEN) {
            if (method_exists($job, 'release')) {
                $job->release($this->cooldownSeconds);

                return;
            }

            throw new CircuitBreakerOpenException(
                "Circuit breaker for [{$this->serviceName}] is open. Cooldown: {$this->cooldownSeconds}s."
            );
        }

        try {
            $next($job);
            $this->recordSuccess();
        } catch (Throwable $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    private function failureKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:failures";
    }

    private function openKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:open_until";
    }
}
