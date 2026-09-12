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
            $this->createPgsqlEnums();
            $this->createPgsqlTable();
            $this->createPgsqlIndexes();
            $this->createPgsqlImmutabilityTrigger();
        } else {
            $this->createSqliteTable();
            $this->createSqliteImmutabilityTriggers();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP FUNCTION IF EXISTS prevent_ledger_entries_mutation CASCADE;');
            DB::statement('DROP TYPE IF EXISTS ledger_direction;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ledger_direction') THEN
                    CREATE TYPE ledger_direction AS ENUM ('debit', 'credit');
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement('
            CREATE TABLE ledger_entries (
                id uuid PRIMARY KEY,
                transaction_id uuid NOT NULL REFERENCES ledger_transactions(id) ON DELETE RESTRICT,
                account_id uuid NOT NULL REFERENCES ledger_accounts(id) ON DELETE RESTRICT,
                direction ledger_direction NOT NULL,
                amount_rials bigint NOT NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT chk_ledger_entry_amount_positive CHECK (amount_rials > 0)
            );
        ');
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE INDEX idx_entries_account ON ledger_entries (account_id, created_at DESC);');
        DB::statement('CREATE INDEX idx_entries_transaction ON ledger_entries (transaction_id);');
        DB::statement('CREATE INDEX idx_entries_direction ON ledger_entries (account_id, direction);');
    }

    private function createPgsqlImmutabilityTrigger(): void
    {
        DB::statement("
            CREATE OR REPLACE FUNCTION prevent_ledger_entries_mutation()
            RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Ledger entries are strictly immutable. UPDATE and DELETE operations are prohibited.';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_protect_ledger_entries
            BEFORE UPDATE OR DELETE ON ledger_entries
            FOR EACH ROW EXECUTE FUNCTION prevent_ledger_entries_mutation();
        ");
    }

    private function createSqliteTable(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id');
            $table->uuid('account_id');
            $table->string('direction', 16);
            $table->unsignedBigInteger('amount_rials');
            $table->timestamp('created_at')->nullable();

            $table->foreign('transaction_id')->references('id')->on('ledger_transactions')->restrictOnDelete();
            $table->foreign('account_id')->references('id')->on('ledger_accounts')->restrictOnDelete();

            $table->index(['account_id', 'created_at'], 'idx_entries_account');
            $table->index('transaction_id', 'idx_entries_transaction');
            $table->index(['account_id', 'direction'], 'idx_entries_direction');
        });
    }

    private function createSqliteImmutabilityTriggers(): void
    {
        DB::unprepared("
            CREATE TRIGGER prevent_ledger_entries_update
            BEFORE UPDATE ON ledger_entries
            BEGIN
                SELECT RAISE(ABORT, 'Ledger entries are strictly immutable. UPDATE is prohibited.');
            END;

            CREATE TRIGGER prevent_ledger_entries_delete
            BEFORE DELETE ON ledger_entries
            BEGIN
                SELECT RAISE(ABORT, 'Ledger entries are strictly immutable. DELETE is prohibited.');
            END;
        ");
    }
};
