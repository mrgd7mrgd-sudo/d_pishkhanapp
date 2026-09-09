<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for service_required_docs (§6.1, §6.2, TASK-036).
     */
    public function up(): void
    {
        Schema::create('service_required_docs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')
                ->constrained('services')
                ->cascadeOnDelete();

            $table->string('document_type_code');
            $table->foreign('document_type_code')
                ->references('code')
                ->on('document_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->boolean('is_mandatory')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'document_type_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_required_docs');
    }
};
