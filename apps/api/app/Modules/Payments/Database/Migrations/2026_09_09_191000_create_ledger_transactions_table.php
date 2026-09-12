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
        Schema::dropIfExists('ledger_transactions');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS ledger_transaction_type;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ledger_transaction_type') THEN
                    CREATE TYPE ledger_transaction_type AS ENUM (
                        'topup', 'service_fee', 'refund', 'payout', 'cashback', 'consultation_fee', 'shipping_fee'
                    );
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement('
            CREATE TABLE ledger_transactions (
                id uuid PRIMARY KEY,
                reference character varying(64) NOT NULL,
                type ledger_transaction_type NOT NULL,
                case_id uuid NULL,
                payment_intent_id uuid NULL,
                description text NULL,
                posted_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ');
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE UNIQUE INDEX idx_tx_reference ON ledger_transactions (reference);');
        DB::statement('CREATE INDEX idx_tx_case ON ledger_transactions (case_id);');
        DB::statement('CREATE INDEX idx_tx_type_posted ON ledger_transactions (type, posted_at DESC);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference', 64)->unique();
            $table->string('type', 32);
            $table->uuid('case_id')->nullable();
            $table->uuid('payment_intent_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->index('case_id', 'idx_tx_case');
            $table->index(['type', 'posted_at'], 'idx_tx_type_posted');
        });
    }
};
