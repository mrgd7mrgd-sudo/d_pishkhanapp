<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Application\Actions;

use App\Modules\Consultation\Domain\Enums\SubscriptionStatus;
use App\Modules\Consultation\Domain\Models\Subscription;
use App\Modules\Consultation\Domain\Models\SubscriptionPlan;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SubscribeAction
{
    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * Subscribe citizen to a business subscription plan (§5.3, TASK-120).
     * Deducts fee from wallet and initializes quota usages.
     */
    public function execute(Citizen $citizen, string $planId): Subscription
    {
        /** @var SubscriptionPlan|null $plan */
        $plan = SubscriptionPlan::query()->where('id', $planId)->orWhere('plan_key', $planId)->first();
        if ($plan === null || ! $plan->is_active) {
            throw new InvalidArgumentException('پلن اشتراک مورد نظر معتبر یا فعال نیست.');
        }

        // Cancel previous active subscriptions for the same citizen
        $existing = Subscription::query()
            ->where('citizen_id', $citizen->id)
            ->where('status', SubscriptionStatus::Active)
            ->first();

        if ($existing !== null && $existing->expires_on->isFuture()) {
            throw new InvalidArgumentException('شما در حال حاضر یک اشتراک فعال دارید.');
        }

        $citizenWallet = $this->ledgerService->getOrCreateAccount(
            LedgerOwnerType::CITIZEN,
            $citizen->id,
            LedgerAccountKind::WALLET
        );

        if (! $this->ledgerService->hasSufficientBalance($citizenWallet, $plan->price_monthly_rials)) {
            throw new InvalidArgumentException('موجودی کیف پول برای خرید این پلن اشتراک کافی نیست.');
        }

        return DB::transaction(function () use ($citizen, $plan, $citizenWallet): Subscription {
            $now = CarbonImmutable::now();
            $expiresOn = $now->addMonth()->toDateString();
            $startedOn = $now->toDateString();

            // Debit wallet, Credit platform revenue
            $platformRevenue = $this->ledgerService->getOrCreateAccount(
                LedgerOwnerType::PLATFORM,
                null,
                LedgerAccountKind::REVENUE
            );

            $this->ledgerService->recordTransaction(
                reference: 'SUB-'.Str::upper(Str::random(10)),
                type: LedgerTransactionType::SERVICE_FEE,
                entries: [
                    new LedgerEntryData($citizenWallet, LedgerDirection::DEBIT, $plan->price_monthly_rials),
                    new LedgerEntryData($platformRevenue, LedgerDirection::CREDIT, $plan->price_monthly_rials),
                ],
                description: "خرید اشتراک {$plan->title}",
                postedAt: $now
            );

            /** @var Subscription $subscription */
            $subscription = Subscription::query()->create([
                'id' => (string) Str::uuid(),
                'plan_id' => $plan->id,
                'citizen_id' => $citizen->id,
                'status' => SubscriptionStatus::Active,
                'started_on' => $startedOn,
                'expires_on' => $expiresOn,
            ]);

            // Initialize quotas based on plan definition
            $periodStart = $now->startOfMonth()->toDateString();
            foreach ($plan->quota as $quotaKey => $limit) {
                if (is_numeric($limit)) {
                    $subscription->quotaUsages()->create([
                        'id' => (string) Str::uuid(),
                        'quota_key' => (string) $quotaKey,
                        'used' => 0,
                        'limit' => (int) $limit,
                        'period_start' => $periodStart,
                    ]);
                }
            }

            AuditLogger::record(
                action: 'subscription.created',
                subject: $subscription,
                changes: [
                    'subscription_id' => $subscription->id,
                    'citizen_id' => $citizen->id,
                    'plan_id' => $plan->id,
                    'price_rials' => $plan->price_monthly_rials,
                ],
                actorType: Citizen::class,
                actorId: $citizen->id
            );

            return $subscription->fresh(['plan', 'quotaUsages']);
        });
    }
}
