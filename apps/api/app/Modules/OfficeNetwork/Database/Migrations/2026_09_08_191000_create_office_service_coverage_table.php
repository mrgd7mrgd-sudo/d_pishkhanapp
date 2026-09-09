<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1, §6.4: Office Service Coverage table.
     */
    public function up(): void
    {
        Schema::create('office_service_coverage', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('office_id')
                ->constrained('offices')
                ->cascadeOnDelete();

            $table->string('category_id');
            $table->foreign('category_id')
                ->references('id')
                ->on('service_categories')
                ->cascadeOnDelete();

            $table->foreignUuid('service_id')
                ->nullable()
                ->constrained('services')
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('daily_capacity')->default(50);
            $table->timestamps();

            $table->unique(['office_id', 'category_id', 'service_id'], 'idx_coverage_unique');
            $table->index(['category_id', 'office_id'], 'idx_coverage_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_service_coverage');
    }
};
