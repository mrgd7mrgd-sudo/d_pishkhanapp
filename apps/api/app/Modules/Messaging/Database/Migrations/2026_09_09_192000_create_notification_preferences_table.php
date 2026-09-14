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
        } else {
            $this->createSqliteTable();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }

    private function createPgsqlTable(): void
    {
        DB::statement('
            CREATE TABLE notification_preferences (
                id uuid PRIMARY KEY,
                citizen_id uuid NOT NULL REFERENCES citizens(id) ON DELETE CASCADE,
                notification_type varchar(64) NOT NULL,
                sms_enabled boolean NOT NULL DEFAULT true,
                push_enabled boolean NOT NULL DEFAULT true,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_notif_pref_citizen_type UNIQUE (citizen_id, notification_type)
            );
        ');
    }

    private function createSqliteTable(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('citizen_id')->constrained('citizens')->cascadeOnDelete();
            $table->string('notification_type', 64);
            $table->boolean('sms_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->timestamps();

            $table->unique(['citizen_id', 'notification_type'], 'uq_notif_pref_citizen_type');
        });
    }
};
