<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1: advisor_specialties table
     */
    public function up(): void
    {
        Schema::create('advisor_specialties', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('advisor_id');
            $table->string('specialty_name', 128);
            $table->timestamps();

            $table->foreign('advisor_id')
                ->references('id')
                ->on('advisors')
                ->cascadeOnDelete();

            $table->index(['advisor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisor_specialties');
    }
};
