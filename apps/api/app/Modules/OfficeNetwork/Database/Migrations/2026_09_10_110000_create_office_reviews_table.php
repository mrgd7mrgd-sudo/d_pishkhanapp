<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_reviews', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignUuid('citizen_id')->constrained('citizens')->cascadeOnDelete();
            $table->uuid('case_id')->unique('idx_office_reviews_case');
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->jsonb('tags')->nullable();
            $table->unsignedInteger('likes')->default(0);
            $table->boolean('is_verified')->default(true);
            $table->text('manager_reply')->nullable();
            $table->timestamp('manager_replied_at')->nullable();
            $table->foreignUuid('manager_operator_id')->nullable()->constrained('operators')->nullOnDelete();
            $table->timestamps();

            $table->index(['office_id', 'is_verified', 'rating'], 'idx_office_reviews_office');
            $table->index(['citizen_id', 'created_at'], 'idx_office_reviews_citizen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_reviews');
    }
};
