<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Application\Actions;

use App\Modules\Consultation\Domain\Enums\SubscriptionStatus;
use App\Modules\Consultation\Domain\Models\QuotaUsage;
use App\Modules\Consultation\Domain\Models\Subscription;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ConsumeQuotaAction
{
    /**
     * Consume quota units with pessimistic row locking ensuring Invariant: quota never becomes negative or exceeds limit.
     */
    public function execute(Citizen $citizen, string $quotaKey, int $amount = 1): QuotaUsage
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('مقدار مصرف سهمیه باید مثبت باشد.');
        }

        return DB::transaction(function () use ($citizen, $quotaKey, $amount): QuotaUsage {
            // Find active subscription
            $activeSub = Subscription::query()
                ->where('citizen_id', $citizen->id)
                ->where('status', SubscriptionStatus::Active)
                ->where('expires_on', '>=', now()->toDateString())
                ->latest()
                ->first();

            if ($activeSub === null) {
                throw new InvalidArgumentException('شما اشتراک فعال معتبری ندارید.');
            }

            // Lock the quota row FOR UPDATE to prevent race conditions (§5.3 Invariant)
            /** @var QuotaUsage|null $quota */
            $quota = QuotaUsage::query()
                ->where('subscription_id', $activeSub->id)
                ->where('quota_key', $quotaKey)
                ->lockForUpdate()
                ->first();

            if ($quota === null) {
                throw new InvalidArgumentException("سهمیه مورد نظر ({$quotaKey}) در پلن اشتراک شما یافت نشد.");
            }

            if ($quota->used + $amount > $quota->limit) {
                throw new InvalidArgumentException("سقف سهمیه {$quotaKey} شما به پایان رسیده است.");
            }

            $quota->increment('used', $amount);

            AuditLogger::record(
                action: 'quota.consumed',
                subject: $quota,
                changes: [
                    'subscription_id' => $activeSub->id,
                    'quota_key' => $quotaKey,
                    'consumed_amount' => $amount,
                    'new_used' => $quota->used,
                    'limit' => $quota->limit,
                ],
                actorType: Citizen::class,
                actorId: $citizen->id
            );

            return $quota->fresh();
        });
    }
}
