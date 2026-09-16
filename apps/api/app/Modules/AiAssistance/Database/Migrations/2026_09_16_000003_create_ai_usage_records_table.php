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
     * Architecture §6.1, §6.5: ai_usage_records with monthly RANGE partitioning on PostgreSQL
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement('
                CREATE TABLE ai_usage_records (
                    id uuid NOT NULL,
                    conversation_id uuid NOT NULL,
                    model character varying(64) NOT NULL,
                    input_tokens integer NOT NULL DEFAULT 0,
                    output_tokens integer NOT NULL DEFAULT 0,
                    cost_rials bigint NOT NULL DEFAULT 0,
                    created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id, created_at)
                ) PARTITION BY RANGE (created_at);
            ');

            // Partitions for 2026 and 2027
            $years = [2026, 2027];
            foreach ($years as $year) {
                for ($month = 1; $month <= 12; $month++) {
                    $mStr = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
                    $nextMonth = $month === 12 ? 1 : $month + 1;
                    $nextYear = $month === 12 ? $year + 1 : $year;
                    $nextMStr = str_pad((string) $nextMonth, 2, '0', STR_PAD_LEFT);

                    $partName = "ai_usage_records_{$year}_{$mStr}";
                    $from = "{$year}-{$mStr}-01 00:00:00+00";
                    $to = "{$nextYear}-{$nextMStr}-01 00:00:00+00";

                    DB::statement("CREATE TABLE IF NOT EXISTS {$partName} PARTITION OF ai_usage_records FOR VALUES FROM ('{$from}') TO ('{$to}');");
                }
            }

            // Default safety partition
            DB::statement('CREATE TABLE IF NOT EXISTS ai_usage_records_default PARTITION OF ai_usage_records DEFAULT;');

            // Indexes
            DB::statement('CREATE INDEX idx_ai_usage_conversation ON ai_usage_records (conversation_id, created_at DESC);');
            DB::statement('CREATE INDEX idx_ai_usage_model_created ON ai_usage_records (model, created_at DESC);');
        } else {
            Schema::create('ai_usage_records', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('conversation_id');
                $table->string('model', 64);
                $table->unsignedInteger('input_tokens')->default(0);
                $table->unsignedInteger('output_tokens')->default(0);
                $table->unsignedBigInteger('cost_rials')->default(0);
                $table->timestamp('created_at')->useCurrent();

                $table->index(['conversation_id', 'created_at']);
                $table->index(['model', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_records');
    }
};
