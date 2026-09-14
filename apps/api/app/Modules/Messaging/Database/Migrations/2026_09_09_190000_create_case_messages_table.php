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
            $this->createPgsqlTable();
            $this->createPgsqlIndexes();
        } else {
            $this->createSqliteTable();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_messages');
    }

    private function createPgsqlTable(): void
    {
        DB::statement('
            CREATE TABLE case_messages (
                id uuid PRIMARY KEY,
                case_id uuid NOT NULL,
                sender_type varchar(32) NOT NULL,
                sender_id uuid NULL,
                sender_name varchar(255) NOT NULL,
                body text NOT NULL,
                attachment_key varchar(500) NULL,
                read_at timestamp with time zone NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ');
    }

    private function createPgsqlIndexes(): void
    {
        // Architecture §6.4: Hot path index for case messages in reverse chronological order
        DB::statement('CREATE INDEX idx_messages_case ON case_messages (case_id, created_at DESC);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('case_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->string('sender_type', 32);
            $table->uuid('sender_id')->nullable();
            $table->string('sender_name', 255);
            $table->text('body');
            $table->string('attachment_key', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'created_at'], 'idx_messages_case');
        });
    }
};
