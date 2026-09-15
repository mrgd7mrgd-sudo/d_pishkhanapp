<?php

declare(strict_types=1);

namespace App\Modules\Payments;

use App\Modules\Payments\Console\Commands\GenerateOfficePayoutsCommand;
use App\Modules\Payments\Console\Commands\ReconcilePaymentIntentsCommand;
use App\Modules\Payments\Console\Commands\RefreshMaterializedViewsCommand;
use App\Modules\Payments\Console\Commands\SnapshotBalancesCommand;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerAccount;
use App\Modules\Payments\Domain\Models\LedgerBalanceSnapshot;
use App\Modules\Payments\Domain\Models\LedgerEntry;
use App\Modules\Payments\Domain\Models\LedgerTransaction;
use App\Modules\Payments\Domain\Models\PaymentIntent;
use App\Modules\Payments\Domain\Models\Payout;
use App\Modules\Payments\Infrastructure\BalanceCache;
use App\Modules\Payments\Infrastructure\Policies\LedgerAccountPolicy;
use App\Modules\Payments\Infrastructure\Policies\LedgerBalanceSnapshotPolicy;
use App\Modules\Payments\Infrastructure\Policies\LedgerEntryPolicy;
use App\Modules\Payments\Infrastructure\Policies\LedgerTransactionPolicy;
use App\Modules\Payments\Infrastructure\Policies\PaymentIntentPolicy;
use App\Modules\Payments\Infrastructure\Policies\PayoutPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LedgerService::class, fn (): LedgerService => new LedgerService);
        $this->app->singleton(BalanceCache::class, fn ($app): BalanceCache => new BalanceCache($app->make(LedgerService::class)));
    }

    public function boot(): void
    {
        Gate::policy(LedgerAccount::class, LedgerAccountPolicy::class);
        Gate::policy(LedgerTransaction::class, LedgerTransactionPolicy::class);
        Gate::policy(LedgerEntry::class, LedgerEntryPolicy::class);
        Gate::policy(PaymentIntent::class, PaymentIntentPolicy::class);
        Gate::policy(Payout::class, PayoutPolicy::class);
        Gate::policy(LedgerBalanceSnapshot::class, LedgerBalanceSnapshotPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ReconcilePaymentIntentsCommand::class,
                GenerateOfficePayoutsCommand::class,
                SnapshotBalancesCommand::class,
                RefreshMaterializedViewsCommand::class,
            ]);
        }
    }
}
