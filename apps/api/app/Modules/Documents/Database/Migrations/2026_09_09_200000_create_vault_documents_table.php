<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('citizen_id');
            $table->string('category', 64);
            $table->string('document_type_code', 64)->nullable();
            $table->string('title', 255);
            $table->string('doc_number', 128)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('citizen_id')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();

            $table->index(['citizen_id', 'deleted_at'], 'idx_vault_docs_citizen');
            $table->index(['citizen_id', 'category', 'deleted_at'], 'idx_vault_docs_citizen_cat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_documents');
    }
};
