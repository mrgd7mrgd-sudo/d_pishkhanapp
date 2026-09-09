<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1, §6.4: Office Specialties table (independent table, not string array).
     */
    public function up(): void
    {
        Schema::create('office_specialties', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('office_id')
                ->constrained('offices')
                ->cascadeOnDelete();

            $table->string('title', 150);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['office_id', 'title'], 'idx_specialties_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_specialties');
    }
};
