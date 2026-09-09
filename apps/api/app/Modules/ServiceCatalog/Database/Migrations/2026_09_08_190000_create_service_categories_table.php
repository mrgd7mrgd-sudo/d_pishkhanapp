<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for service_categories (§6.1, §6.2, TASK-036).
     * Note: service_count is deliberately omitted (derived attribute, Architecture §6.2).
     */
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('title');
            $table->string('short_title')->nullable();
            $table->string('icon_name')->nullable();
            $table->string('color', 32)->nullable();
            $table->string('badge')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
