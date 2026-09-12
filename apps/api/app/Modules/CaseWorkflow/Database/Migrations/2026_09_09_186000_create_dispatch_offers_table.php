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
        Schema::dropIfExists('dispatch_offers');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS dispatch_offer_status;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'dispatch_offer_status') THEN
                    CREATE TYPE dispatch_offer_status AS ENUM ('pending', 'accepted', 'declined', 'expired');
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement("
            CREATE TABLE dispatch_offers (
                id uuid PRIMARY KEY,
                case_id uuid NOT NULL,
                office_id uuid NOT NULL REFERENCES offices(id) ON DELETE CASCADE,
                round integer NOT NULL,
                status dispatch_offer_status NOT NULL DEFAULT 'pending',
                expires_at timestamp with time zone NOT NULL,
                responded_at timestamp with time zone NULL,
                responded_by uuid NULL REFERENCES operators(id) ON DELETE SET NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    private function createPgsqlIndexes(): void
    {
        // Architecture §6.4: Hot path partial index for pending offers per office
        DB::statement("CREATE INDEX idx_offers_office_pending ON dispatch_offers (office_id, status, expires_at) WHERE status = 'pending';");

        // Architecture §6.4: Case offers index
        DB::statement('CREATE INDEX idx_offers_case ON dispatch_offers (case_id, round);');

        // Partial unique index preventing duplicate pending offers to the same office for the same case
        DB::statement("CREATE UNIQUE INDEX uq_offers_case_office_pending ON dispatch_offers (case_id, office_id) WHERE status = 'pending';");
    }

    private function createSqliteTable(): void
    {
        Schema::create('dispatch_offers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->foreignUuid('office_id')->constrained('offices')->cascadeOnDelete();
            $table->integer('round');
            $table->string('status', 32)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->foreignUuid('responded_by')->nullable()->constrained('operators')->nullOnDelete();
            $table->timestamps();

            $table->index(['office_id', 'status', 'expires_at'], 'idx_offers_office_pending');
            $table->index(['case_id', 'round'], 'idx_offers_case');
            $table->unique(['case_id', 'office_id', 'status'], 'uq_offers_case_office_pending');
        });
    }
};
