<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1: subscriptions table
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->uuid('citizen_id');
            $table->string('status', 32)->default('active'); // active, expired, cancelled
            $table->date('started_on');
            $table->date('expires_on');
            $table->timestamps();

            $table->foreign('plan_id')
                ->references('id')
                ->on('subscription_plans')
                ->cascadeOnDelete();

            $table->foreign('citizen_id')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();

            $table->index(['citizen_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
