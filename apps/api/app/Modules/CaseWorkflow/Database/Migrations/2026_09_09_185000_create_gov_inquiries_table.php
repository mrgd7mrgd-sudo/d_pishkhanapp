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
        Schema::dropIfExists('gov_inquiries');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS gov_inquiry_status;');
            DB::statement('DROP TYPE IF EXISTS gov_inquiry_provider;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'gov_inquiry_provider') THEN
                    CREATE TYPE gov_inquiry_provider AS ENUM ('shahkar', 'civil_registry', 'post');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'gov_inquiry_status') THEN
                    CREATE TYPE gov_inquiry_status AS ENUM ('queued', 'in_progress', 'succeeded', 'failed', 'mismatch');
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement("
            CREATE TABLE gov_inquiries (
                id uuid PRIMARY KEY,
                case_id uuid NOT NULL,
                provider gov_inquiry_provider NOT NULL,
                status gov_inquiry_status NOT NULL DEFAULT 'queued',
                request_snapshot jsonb NOT NULL DEFAULT '{}'::jsonb,
                response_snapshot jsonb NULL,
                attempts integer NOT NULL DEFAULT 0,
                last_error text NULL,
                completed_at timestamp with time zone NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE INDEX idx_gov_inquiries_case_status ON gov_inquiries (case_id, status);');
        DB::statement('CREATE INDEX idx_gov_inquiries_provider ON gov_inquiries (provider, status);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('gov_inquiries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->string('provider', 32);
            $table->string('status', 32)->default('queued');
            $table->json('request_snapshot');
            $table->json('response_snapshot')->nullable();
            $table->integer('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'status'], 'idx_gov_inquiries_case_status');
            $table->index(['provider', 'status'], 'idx_gov_inquiries_provider');
        });
    }
};
