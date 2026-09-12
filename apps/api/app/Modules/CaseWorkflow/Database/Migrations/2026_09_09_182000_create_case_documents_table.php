<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            $this->createPgsqlEnums();
            $this->createPgsqlTable();
            $this->createPgsqlIndexes();
        } else {
            $this->createSqliteTable();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_documents');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS case_document_status;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'case_document_status') THEN
                    CREATE TYPE case_document_status AS ENUM ('pending', 'processing', 'verified', 'rejected');
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement("
            CREATE TABLE case_documents (
                id uuid PRIMARY KEY,
                case_id uuid NOT NULL,
                document_type_code character varying(64) NOT NULL,
                version integer NOT NULL DEFAULT 1,
                status case_document_status NOT NULL DEFAULT 'pending',
                storage_key character varying(512) NOT NULL,
                encrypted_data_key bytea NOT NULL,
                content_sha256 character(64) NOT NULL,
                size_bytes bigint NOT NULL DEFAULT 0,
                mime_type character varying(100) NOT NULL,
                quality_warnings jsonb NOT NULL DEFAULT '[]'::jsonb,
                reason_code character varying(64) NULL,
                reviewed_by uuid NULL,
                uploaded_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE UNIQUE INDEX idx_case_docs_version ON case_documents (case_id, document_type_code, version);');
        DB::statement('CREATE INDEX idx_case_docs_case ON case_documents (case_id, document_type_code, version DESC);');
        DB::statement('CREATE INDEX idx_case_docs_status ON case_documents (case_id, status);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('case_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->string('document_type_code', 64);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 32)->default('pending');
            $table->string('storage_key', 512);
            $table->binary('encrypted_data_key');
            $table->char('content_sha256', 64);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('mime_type', 100);
            $table->json('quality_warnings');
            $table->string('reason_code', 64)->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->unique(['case_id', 'document_type_code', 'version'], 'idx_case_docs_version');
            $table->index(['case_id', 'document_type_code', 'version'], 'idx_case_docs_case');
            $table->index(['case_id', 'status'], 'idx_case_docs_status');
        });
    }
};
