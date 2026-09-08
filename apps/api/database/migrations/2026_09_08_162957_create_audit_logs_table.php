<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §7.6: Audit Log with monthly RANGE partitioning on PostgreSQL.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            // Native PostgreSQL partitioned table by RANGE (occurred_at)
            DB::statement("
                CREATE TABLE audit_logs (
                    id uuid NOT NULL,
                    occurred_at timestamp with time zone NOT NULL,
                    entry_hash character(64) NOT NULL,
                    prev_hash character(64) NOT NULL,
                    action character varying(64) NOT NULL,
                    actor_type character varying(64) NULL,
                    actor_id character varying(64) NULL,
                    subject_type character varying(128) NULL,
                    subject_id character varying(64) NULL,
                    changes jsonb NOT NULL DEFAULT '{}'::jsonb,
                    context jsonb NOT NULL DEFAULT '{}'::jsonb,
                    request_id character varying(64) NULL,
                    ip_address character varying(45) NULL,
                    user_agent text NULL,
                    created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id, occurred_at)
                ) PARTITION BY RANGE (occurred_at);
            ");

            // Create partitions for current year and future months (2026-01 to 2027-12)
            $years = [2026, 2027];
            foreach ($years as $year) {
                for ($month = 1; $month <= 12; $month++) {
                    $mStr = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
                    $nextMonth = $month === 12 ? 1 : $month + 1;
                    $nextYear = $month === 12 ? $year + 1 : $year;
                    $nextMStr = str_pad((string) $nextMonth, 2, '0', STR_PAD_LEFT);

                    $partName = "audit_logs_{$year}_{$mStr}";
                    $from = "{$year}-{$mStr}-01 00:00:00+00";
                    $to = "{$nextYear}-{$nextMStr}-01 00:00:00+00";

                    DB::statement("CREATE TABLE IF NOT EXISTS {$partName} PARTITION OF audit_logs FOR VALUES FROM ('{$from}') TO ('{$to}');");
                }
            }

            // Create default partition as safety net
            DB::statement('CREATE TABLE IF NOT EXISTS audit_logs_default PARTITION OF audit_logs DEFAULT;');

            // Secondary Indexes (§6.4 & §7.6)
            DB::statement('CREATE INDEX idx_audit_subject ON audit_logs (subject_type, subject_id, occurred_at DESC);');
            DB::statement('CREATE INDEX idx_audit_actor ON audit_logs (actor_type, actor_id, occurred_at DESC);');
            DB::statement('CREATE INDEX idx_audit_action ON audit_logs (action, occurred_at DESC);');
            DB::statement('CREATE INDEX idx_audit_entry_hash ON audit_logs (entry_hash);');

            // Revoke UPDATE and DELETE permissions from app user to enforce append-only immutability
            // (Only if app user is configured and distinct from superuser)
            $dbUser = config("database.connections.{$connection}.username");
            if ($dbUser && $dbUser !== 'postgres') {
                try {
                    DB::statement("REVOKE UPDATE, DELETE, TRUNCATE ON audit_logs FROM {$dbUser};");
                } catch (Throwable) {
                    // Ignore if permission grants are not allowed in this environment
                }
            }
        } else {
            // SQLite / MySQL fallback for local tests
            Schema::create('audit_logs', function (Blueprint $table): void {
                $table->uuid('id');
                $table->timestampTz('occurred_at');
                $table->char('entry_hash', 64);
                $table->char('prev_hash', 64);
                $table->string('action', 64);
                $table->string('actor_type', 64)->nullable();
                $table->string('actor_id', 64)->nullable();
                $table->string('subject_type', 128)->nullable();
                $table->string('subject_id', 64)->nullable();
                $table->json('changes');
                $table->json('context');
                $table->string('request_id', 64)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->primary(['id', 'occurred_at']);
                $table->index(['subject_type', 'subject_id', 'occurred_at'], 'idx_audit_subject');
                $table->index(['actor_type', 'actor_id', 'occurred_at'], 'idx_audit_actor');
                $table->index(['action', 'occurred_at'], 'idx_audit_action');
                $table->index('entry_hash', 'idx_audit_entry_hash');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
