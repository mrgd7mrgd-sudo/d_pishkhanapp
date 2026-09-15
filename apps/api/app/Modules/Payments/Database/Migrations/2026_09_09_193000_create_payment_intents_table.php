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
        Schema::dropIfExists('payment_intents');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS payment_intent_status;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_intent_status') THEN
                    CREATE TYPE payment_intent_status AS ENUM (
                        'created', 'redirected', 'paid', 'failed', 'expired', 'reconciled'
                    );
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement("
            CREATE TABLE payment_intents (
                id uuid PRIMARY KEY,
                citizen_id uuid NOT NULL,
                amount_rials bigint NOT NULL CHECK (amount_rials > 0),
                gateway character varying(32) NOT NULL,
                authority character varying(128) NOT NULL,
                status payment_intent_status NOT NULL DEFAULT 'created',
                ref_id character varying(64) NULL,
                card_pan_masked character varying(32) NULL CHECK (card_pan_masked IS NULL OR card_pan_masked LIKE '%*%'),
                expires_at timestamp with time zone NOT NULL,
                verified_at timestamp with time zone NULL,
                metadata jsonb NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_intents_citizen FOREIGN KEY (citizen_id) REFERENCES citizens(id) ON DELETE RESTRICT
            );
        ");
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE UNIQUE INDEX idx_intents_auth ON payment_intents (gateway, authority);');
        DB::statement('CREATE INDEX idx_intents_citizen_status ON payment_intents (citizen_id, status);');
        DB::statement('CREATE INDEX idx_intents_created_at ON payment_intents (created_at);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('payment_intents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('citizen_id');
            $table->unsignedBigInteger('amount_rials');
            $table->string('gateway', 32);
            $table->string('authority', 128);
            $table->string('status', 32)->default('created');
            $table->string('ref_id', 64)->nullable();
            $table->string('card_pan_masked', 32)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'authority'], 'idx_intents_auth');
            $table->index(['citizen_id', 'status'], 'idx_intents_citizen_status');
            $table->index('created_at', 'idx_intents_created_at');

            $table->foreign('citizen_id')->references('id')->on('citizens')->cascadeOnDelete();
        });
    }
};
