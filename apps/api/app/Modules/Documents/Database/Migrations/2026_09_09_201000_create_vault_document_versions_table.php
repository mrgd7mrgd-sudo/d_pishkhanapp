<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_document_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('vault_document_id');
            $table->unsignedInteger('version');
            $table->string('storage_key', 512);
            $table->text('encrypted_data_key');
            $table->char('content_sha256', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type', 128);
            $table->string('file_name', 255)->nullable();
            $table->jsonb('quality_warnings')->default('[]');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('vault_document_id')
                ->references('id')
                ->on('vault_documents')
                ->cascadeOnDelete();

            $table->unique(['vault_document_id', 'version'], 'uq_vault_doc_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_document_versions');
    }
};
