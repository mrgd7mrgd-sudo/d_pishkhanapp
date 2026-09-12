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
        } else {
            $this->createSqliteTable();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_accounts');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS ledger_account_kind;');
            DB::statement('DROP TYPE IF EXISTS ledger_owner_type;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ledger_owner_type') THEN
                    CREATE TYPE ledger_owner_type AS ENUM ('citizen', 'office', 'advisor', 'platform', 'gateway');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ledger_account_kind') THEN
                    CREATE TYPE ledger_account_kind AS ENUM ('wallet', 'payable', 'revenue', 'clearing', 'escrow');
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement("
            CREATE TABLE ledger_accounts (
                id uuid PRIMARY KEY,
                owner_type ledger_owner_type NOT NULL,
                owner_id uuid NULL,
                kind ledger_account_kind NOT NULL,
                currency character(3) NOT NULL DEFAULT 'IRR',
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX idx_ledger_accounts_unique
            ON ledger_accounts (owner_type, COALESCE(owner_id, '00000000-0000-0000-0000-000000000000'::uuid), kind, currency);
        ");
        DB::statement('CREATE INDEX idx_ledger_accounts_owner ON ledger_accounts (owner_type, owner_id);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('owner_type', 32);
            $table->uuid('owner_id')->nullable();
            $table->string('kind', 32);
            $table->char('currency', 3)->default('IRR');
            $table->timestamps();

            $table->index(['owner_type', 'owner_id'], 'idx_ledger_accounts_owner');
        });
    }
};
