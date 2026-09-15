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
        Schema::dropIfExists('payouts');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS payout_status;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payout_status') THEN
                    CREATE TYPE payout_status AS ENUM (
                        'pending', 'processing', 'completed', 'failed'
                    );
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement('
            CREATE TABLE payouts (
                id uuid PRIMARY KEY,
                office_id uuid NOT NULL,
                amount_rials bigint NOT NULL CHECK (amount_rials >= 0),
                status payout_status NOT NULL DEFAULT \'pending\',
                period_start timestamp with time zone NOT NULL,
                period_end timestamp with time zone NOT NULL,
                total_cases_count integer NOT NULL DEFAULT 0,
                reference_number character varying(64) NULL,
                ledger_transaction_id uuid NULL,
                generated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                processed_at timestamp with time zone NULL,
                failure_reason text NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_payouts_office FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE RESTRICT,
                CONSTRAINT fk_payouts_ledger_tx FOREIGN KEY (ledger_transaction_id) REFERENCES ledger_transactions(id) ON DELETE SET NULL
            );
        ');
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE INDEX idx_payouts_office_status ON payouts (office_id, status);');
        DB::statement('CREATE INDEX idx_payouts_period ON payouts (period_start, period_end);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('payouts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('office_id');
            $table->unsignedBigInteger('amount_rials');
            $table->string('status', 32)->default('pending');
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->unsignedInteger('total_cases_count')->default(0);
            $table->string('reference_number', 64)->nullable();
            $table->uuid('ledger_transaction_id')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'status'], 'idx_payouts_office_status');
            $table->index(['period_start', 'period_end'], 'idx_payouts_period');

            $table->foreign('office_id')->references('id')->on('offices')->cascadeOnDelete();
            $table->foreign('ledger_transaction_id')->references('id')->on('ledger_transactions')->nullOnDelete();
        });
    }
};
