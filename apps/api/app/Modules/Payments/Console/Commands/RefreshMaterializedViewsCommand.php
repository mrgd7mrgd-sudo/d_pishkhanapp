<?php

declare(strict_types=1);

namespace App\Modules\Payments\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RefreshMaterializedViewsCommand (Architecture §5.9, §6.7, TASK-091).
 * Runs weekly on Sunday at 01:00 to refresh materialized views, snapshots, and warm report caches.
 */
final class RefreshMaterializedViewsCommand extends Command
{
    protected $signature = 'pishkhan:refresh-materialized-views';

    protected $description = 'Refresh database materialized views, ledger balance snapshots, and reporting caches';

    public function handle(): int
    {
        $this->info('Starting weekly refresh of materialized views and snapshots...');

        // 1. Refresh balance snapshots
        $exitCode = Artisan::call('payments:snapshot-balances');
        $this->info("Refreshed balance snapshots (Exit code: {$exitCode}).");

        // 2. Refresh PostgreSQL materialized views if running on Postgres
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            $this->refreshPgsqlMaterializedViews();
        }

        Log::info('Successfully refreshed all materialized views and balance caches.');
        $this->info('Materialized views refresh completed successfully.');

        return self::SUCCESS;
    }

    private function refreshPgsqlMaterializedViews(): void
    {
        // Query any pgsql materialized views if present
        $views = DB::select("SELECT matviewname FROM pg_matviews WHERE schemaname = 'public'");

        foreach ($views as $view) {
            $viewName = $view->matviewname;
            $this->line("Refreshing materialized view: {$viewName}...");
            DB::statement("REFRESH MATERIALIZED VIEW CONCURRENTLY {$viewName};");
        }
    }
}
