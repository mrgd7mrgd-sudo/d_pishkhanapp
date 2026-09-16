<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1: advisor_reviews table
     */
    public function up(): void
    {
        Schema::create('advisor_reviews', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('advisor_id');
            $table->uuid('citizen_id');
            $table->uuid('session_id')->nullable();
            $table->decimal('rating_accuracy', 3, 2);
            $table->decimal('rating_eloquence', 3, 2);
            $table->decimal('rating_patience', 3, 2);
            $table->decimal('overall_rating', 3, 2);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('advisor_id')
                ->references('id')
                ->on('advisors')
                ->cascadeOnDelete();

            $table->foreign('citizen_id')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();

            $table->index(['advisor_id', 'created_at']);
            $table->index(['citizen_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisor_reviews');
    }
};
