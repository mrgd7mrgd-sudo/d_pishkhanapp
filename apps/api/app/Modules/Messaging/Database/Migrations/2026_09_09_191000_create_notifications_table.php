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
        Schema::dropIfExists('notifications');
    }

    private function createPgsqlTable(): void
    {
        DB::statement('
            CREATE TABLE notifications (
                id uuid PRIMARY KEY,
                citizen_id uuid NOT NULL REFERENCES citizens(id) ON DELETE CASCADE,
                type varchar(64) NOT NULL,
                title varchar(255) NOT NULL,
                body text NOT NULL,
                payload jsonb NULL,
                read_at timestamp with time zone NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ');
    }

    private function createPgsqlIndexes(): void
    {
        // Architecture §6.4: Hot path partial index for unread notifications per citizen
        DB::statement('CREATE INDEX idx_notifications_unread ON notifications (citizen_id, created_at DESC) WHERE read_at IS NULL;');

        // General index for citizen notifications
        DB::statement('CREATE INDEX idx_notifications_citizen ON notifications (citizen_id, created_at DESC);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('citizen_id')->constrained('citizens')->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('title', 255);
            $table->text('body');
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['citizen_id', 'created_at'], 'idx_notifications_citizen');
        });

        DB::statement('CREATE INDEX idx_notifications_unread ON notifications (citizen_id, created_at DESC) WHERE read_at IS NULL;');
    }
};
