<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_sla_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('office_id')->constrained('offices')->cascadeOnDelete();
            $table->uuid('case_id')->nullable();
            $table->string('event_type', 64);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('penalty_points')->default(0);
            $table->boolean('is_breach')->default(true);
            $table->timestamp('occurred_at');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'occurred_at'], 'idx_sla_events_office_date');
            $table->index('case_id', 'idx_sla_events_case');
            $table->index(['is_breach', 'event_type'], 'idx_sla_events_breach');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_sla_events');
    }
};
