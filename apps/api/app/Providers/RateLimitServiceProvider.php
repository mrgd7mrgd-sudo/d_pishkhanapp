<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap rate limiting services defined in Architecture §7.5.
     */
    public function boot(): void
    {
        $this->configureGlobalLimiter();
        $this->configureOtpLimiters();
        $this->configureOperatorLimiters();
        $this->configureCitizenLimiters();
        $this->configureOperationalLimiters();
    }

    /**
     * Global per-IP rate limiter: 300 requests / minute (§7.5).
     */
    private function configureGlobalLimiter(): void
    {
        RateLimiter::for('global', function (Request $request) {
            $ip = $request->ip() ?? '127.0.0.1';

            return Limit::perMinute(300)->by($ip);
        });
    }

    /**
     * Multi-tier OTP request & verification limiters (§7.5).
     */
    private function configureOtpLimiters(): void
    {
        RateLimiter::for('otp.request', function (Request $request) {
            $mobile = (string) $request->input('mobile', '');
            $ip = $request->ip() ?? '127.0.0.1';
            $subnet = $this->extractIpv4Subnet($ip);

            $limits = [
                Limit::perMinutes(15, 30)->by('ip:'.$ip),
            ];

            if ($mobile !== '') {
                $limits[] = Limit::perMinutes(15, 3)->by('mobile:'.sha1($mobile));
            }

            if ($subnet !== null) {
                $limits[] = Limit::perMinutes(15, 300)->by('subnet:'.$subnet);
            }

            return $limits;
        });

        RateLimiter::for('otp.verify', function (Request $request) {
            $challengeId = (string) $request->input('challenge_id', '');
            $ip = $request->ip() ?? '127.0.0.1';

            $limits = [
                Limit::perHour(20)->by('ip:'.$ip),
            ];

            if ($challengeId !== '') {
                $limits[] = Limit::perMinutes(15, 5)->by('challenge:'.$challengeId);
            }

            return $limits;
        });
    }

    /**
     * Operator desk rate limiters (§7.5): 600 requests / min across API, 60 returns / hour.
     */
    private function configureOperatorLimiters(): void
    {
        RateLimiter::for('operator.api', function (Request $request) {
            $user = $request->user();
            $key = $user !== null ? 'op:'.$user->getAuthIdentifier() : 'ip:'.($request->ip() ?? '127.0.0.1');

            return Limit::perMinute(600)->by($key);
        });

        RateLimiter::for('cases.return', function (Request $request) {
            $user = $request->user();
            $key = $user !== null ? 'op:'.$user->getAuthIdentifier() : 'ip:'.($request->ip() ?? '127.0.0.1');

            return Limit::perHour(60)->by($key);
        });
    }

    /**
     * Citizen action limiters (§7.5): cases, documents, AI chat, wallet.
     */
    private function configureCitizenLimiters(): void
    {
        RateLimiter::for('cases.store', function (Request $request) {
            $user = $request->user();
            $key = $user !== null ? 'citizen:'.$user->getAuthIdentifier() : 'ip:'.($request->ip() ?? '127.0.0.1');

            return [
                Limit::perHour(10)->by($key.':hour'),
                Limit::perDay(30)->by($key.':day'),
            ];
        });

        RateLimiter::for('documents.upload', function (Request $request) {
            $user = $request->user();
            $key = $user !== null ? 'citizen:'.$user->getAuthIdentifier() : 'ip:'.($request->ip() ?? '127.0.0.1');

            return Limit::perHour(50)->by($key);
        });

        RateLimiter::for('ai.chat', function (Request $request) {
            $user = $request->user();
            $key = $user !== null ? 'citizen:'.$user->getAuthIdentifier() : 'ip:'.($request->ip() ?? '127.0.0.1');

            return [
                Limit::perHour(30)->by($key.':hour'),
                Limit::perDay(200)->by($key.':day'),
            ];
        });

        RateLimiter::for('wallet.topup', function (Request $request) {
            $user = $request->user();
            $key = $user !== null ? 'citizen:'.$user->getAuthIdentifier() : 'ip:'.($request->ip() ?? '127.0.0.1');

            return Limit::perHour(5)->by($key);
        });
    }

    /**
     * Operational limiters (§7.5): offices/nearby (60/min), offers/accept (120/min).
     */
    private function configureOperationalLimiters(): void
    {
        RateLimiter::for('offices.nearby', function (Request $request) {
            $user = $request->user();
            $key = $user !== null ? 'citizen:'.$user->getAuthIdentifier() : 'ip:'.($request->ip() ?? '127.0.0.1');

            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('offers.accept', function (Request $request) {
            $user = $request->user();
            $officeKey = $user instanceof Operator
                ? 'office:'.$user->office_id
                : ($user !== null ? 'user:'.$user->getAuthIdentifier() : 'ip:'.($request->ip() ?? '127.0.0.1'));

            return Limit::perMinute(120)->by($officeKey);
        });
    }

    /**
     * Extract IPv4 /24 subnet (e.g., 192.168.1.55 -> 192.168.1.0_24).
     */
    private function extractIpv4Subnet(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0].'.'.$parts[1].'.'.$parts[2].'.0_24';
            }
        }

        return null;
    }
}
