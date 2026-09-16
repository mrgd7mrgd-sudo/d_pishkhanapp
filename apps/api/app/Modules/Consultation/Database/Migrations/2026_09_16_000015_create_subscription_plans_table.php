<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1: subscription_plans table
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('plan_key', 64)->unique();
            $table->string('title', 128);
            $table->string('badge', 64)->nullable();
            $table->boolean('is_popular')->default(false);
            $table->unsignedBigInteger('price_monthly_rials')->default(0);
            $table->string('target_audience', 128);
            $table->jsonb('features')->default('[]');
            $table->jsonb('quota')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
