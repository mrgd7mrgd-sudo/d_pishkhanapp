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
        Schema::dropIfExists('case_timeline_steps');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS timeline_step_status;');
            DB::statement('DROP TYPE IF EXISTS timeline_actor_type;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'timeline_step_status') THEN
                    CREATE TYPE timeline_step_status AS ENUM ('done', 'current', 'pending', 'failed', 'warning');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'timeline_actor_type') THEN
                    CREATE TYPE timeline_actor_type AS ENUM ('citizen', 'operator', 'system', 'government', 'courier');
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement("
            CREATE TABLE case_timeline_steps (
                id uuid PRIMARY KEY,
                case_id uuid NOT NULL,
                sequence integer NOT NULL,
                title character varying(150) NOT NULL,
                description text NULL,
                status timeline_step_status NOT NULL DEFAULT 'pending',
                turn_owner turn_owner NOT NULL DEFAULT 'system',
                turn_owner_label character varying(100) NOT NULL DEFAULT '',
                actor_type timeline_actor_type NOT NULL DEFAULT 'system',
                actor_id uuid NULL,
                office_note text NULL,
                duration_actual_minutes integer NULL,
                duration_typical_minutes integer NULL,
                occurred_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE UNIQUE INDEX idx_timeline_case_seq ON case_timeline_steps (case_id, sequence);');
        DB::statement('CREATE INDEX idx_timeline_actor ON case_timeline_steps (actor_type, actor_id);');
        DB::statement('CREATE INDEX idx_timeline_occurred ON case_timeline_steps (case_id, occurred_at DESC);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('case_timeline_steps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->unsignedInteger('sequence');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('turn_owner', 32)->default('system');
            $table->string('turn_owner_label', 100)->default('');
            $table->string('actor_type', 32)->default('system');
            $table->uuid('actor_id')->nullable();
            $table->text('office_note')->nullable();
            $table->unsignedInteger('duration_actual_minutes')->nullable();
            $table->unsignedInteger('duration_typical_minutes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['case_id', 'sequence'], 'idx_timeline_case_seq');
            $table->index(['actor_type', 'actor_id'], 'idx_timeline_actor');
            $table->index(['case_id', 'occurred_at'], 'idx_timeline_occurred');
        });
    }
};
