<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for document_types (§6.1, §6.2, TASK-036).
     */
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table): void {
            $table->string('code')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('accepted_mimes');
            $table->boolean('requires_original')->default(false);
            $table->integer('validity_months')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
