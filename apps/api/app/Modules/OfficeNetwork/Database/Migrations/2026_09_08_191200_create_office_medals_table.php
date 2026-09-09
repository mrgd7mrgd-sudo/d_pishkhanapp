<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1, §6.4: Office Medals table (independent table, not string array).
     */
    public function up(): void
    {
        Schema::create('office_medals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('office_id')
                ->constrained('offices')
                ->cascadeOnDelete();

            $table->string('title', 150);
            $table->string('icon', 64)->nullable();
            $table->timestamp('earned_at')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'earned_at'], 'idx_medals_office_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_medals');
    }
};
