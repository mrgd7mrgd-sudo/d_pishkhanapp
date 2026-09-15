<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            $this->createPgsqlTable();
            $this->createPgsqlIndexes();
        } else {
            $this->createSqliteTable();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_balance_snapshots');
    }

    private function createPgsqlTable(): void
    {
        DB::statement('
            CREATE TABLE ledger_balance_snapshots (
                id uuid PRIMARY KEY,
                account_id uuid NOT NULL,
                balance_rials bigint NOT NULL,
                as_of_transaction_id uuid NULL,
                snapshot_at timestamp with time zone NOT NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_snapshots_account FOREIGN KEY (account_id) REFERENCES ledger_accounts(id) ON DELETE CASCADE,
                CONSTRAINT fk_snapshots_tx FOREIGN KEY (as_of_transaction_id) REFERENCES ledger_transactions(id) ON DELETE SET NULL
            );
        ');
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE INDEX idx_snapshots_account_time ON ledger_balance_snapshots (account_id, snapshot_at DESC);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('ledger_balance_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->bigInteger('balance_rials');
            $table->uuid('as_of_transaction_id')->nullable();
            $table->timestamp('snapshot_at');
            $table->timestamps();

            $table->index(['account_id', 'snapshot_at'], 'idx_snapshots_account_time');

            $table->foreign('account_id')->references('id')->on('ledger_accounts')->cascadeOnDelete();
            $table->foreign('as_of_transaction_id')->references('id')->on('ledger_transactions')->nullOnDelete();
        });
    }
};
