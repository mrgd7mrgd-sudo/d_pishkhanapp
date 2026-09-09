<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1, §6.4: Office Announcements table.
     */
    public function up(): void
    {
        Schema::create('office_announcements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('office_id')
                ->constrained('offices')
                ->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('content');
            $table->string('priority', 32)->default('normal');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['office_id', 'is_active', 'priority'], 'idx_announcements_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_announcements');
    }
};
