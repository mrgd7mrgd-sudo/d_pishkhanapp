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
     * Architecture §6.1: consultation_sessions table
     */
    public function up(): void
    {
        Schema::create('consultation_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('advisor_id');
            $table->uuid('citizen_id');
            $table->string('mode', 32);
            $table->string('status', 32)->default('scheduled');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedBigInteger('total_fee_rials')->default(0);
            $table->string('tracking_code', 32)->unique();
            $table->unsignedInteger('uploaded_docs_count')->default(0);
            $table->text('advisor_verdict')->nullable();
            $table->uuid('linked_service_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('advisor_id')
                ->references('id')
                ->on('advisors')
                ->cascadeOnDelete();

            $table->foreign('citizen_id')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();

            $table->foreign('linked_service_id')
                ->references('id')
                ->on('services')
                ->nullOnDelete();

            $table->index(['advisor_id', 'status']);
            $table->index(['citizen_id', 'status']);
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE consultation_sessions ADD CONSTRAINT check_duration_positive CHECK (duration_seconds >= 0)');
            DB::statement('ALTER TABLE consultation_sessions ADD CONSTRAINT check_total_fee_positive CHECK (total_fee_rials >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_sessions');
    }
};
