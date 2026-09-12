<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_returns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->string('reason_code', 64);
            $table->string('target_document_type_code', 64)->nullable();
            $table->text('operator_note')->nullable();
            $table->uuid('operator_id')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'created_at'], 'idx_case_returns_case');
            $table->index('reason_code', 'idx_case_returns_reason');
            $table->index(['operator_id', 'created_at'], 'idx_case_returns_operator');
            $table->foreign('reason_code')->references('code')->on('return_reasons')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_returns');
    }
};
