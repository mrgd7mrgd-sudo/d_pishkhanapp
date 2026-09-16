<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1: advisors table
     */
    public function up(): void
    {
        Schema::create('advisors', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('citizen_id');
            $table->string('display_name', 128);
            $table->string('avatar_key', 255)->nullable();
            $table->string('title', 128);
            $table->string('category', 64);
            $table->string('credentials_badge', 128)->nullable();
            $table->string('license_number', 64)->unique();
            $table->unsignedInteger('experience_years')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->unsignedInteger('review_count')->default(0);
            $table->decimal('rating_accuracy', 3, 2)->default(0.00);
            $table->decimal('rating_eloquence', 3, 2)->default(0.00);
            $table->decimal('rating_patience', 3, 2)->default(0.00);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->text('bio')->nullable();
            $table->unsignedInteger('consultation_count')->default(0);
            $table->unsignedBigInteger('price_text_chat_rials')->default(0);
            $table->unsignedBigInteger('price_phone_per_minute_rials')->default(0);
            $table->unsignedBigInteger('price_deep_review_rials')->default(0);
            $table->string('application_status', 32)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('citizen_id')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();

            $table->index(['category', 'application_status', 'is_verified']);
            $table->index(['rating', 'review_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisors');
    }
};
