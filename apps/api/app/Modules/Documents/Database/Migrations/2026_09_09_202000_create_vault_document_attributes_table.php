<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_document_attributes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('vault_document_id');
            $table->string('label', 128);
            $table->text('value');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('vault_document_id')
                ->references('id')
                ->on('vault_documents')
                ->cascadeOnDelete();

            $table->index(['vault_document_id', 'display_order'], 'idx_vault_doc_attrs_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_document_attributes');
    }
};
