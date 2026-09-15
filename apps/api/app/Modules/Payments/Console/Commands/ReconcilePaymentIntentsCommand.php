<?php

declare(strict_types=1);

namespace App\Modules\Payments\Console\Commands;

use App\Integration\Payment\PaymentGateway;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Jobs\ReconcilePaymentIntentsJob;
use Illuminate\Console\Command;

final class ReconcilePaymentIntentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reconcile {--older-than=10 : Minutes threshold for abandoned intents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile abandoned redirected payment intents with payment gateway (Architecture §5.9, §8.2)';

    public function handle(
        PaymentGateway $paymentGateway,
        LedgerService $ledgerService
    ): int {
        $olderThan = (int) $this->option('older-than');

        $this->info("Scanning redirected payment intents older than {$olderThan} minutes...");

        $job = new ReconcilePaymentIntentsJob(olderThanMinutes: $olderThan);
        $stats = $job->handle($paymentGateway, $ledgerService);

        $this->table(
            ['Reconciled', 'Failed', 'Skipped'],
            [[$stats['reconciled'], $stats['failed'], $stats['skipped']]]
        );

        $this->info("Reconciliation finished: {$stats['reconciled']} reconciled, {$stats['failed']} marked failed.");

        return Command::SUCCESS;
    }
}
