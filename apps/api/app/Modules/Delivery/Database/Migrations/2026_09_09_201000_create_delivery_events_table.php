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
        Schema::dropIfExists('delivery_events');
    }

    private function createPgsqlTable(): void
    {
        DB::statement('
            CREATE TABLE delivery_events (
                id uuid PRIMARY KEY,
                delivery_request_id uuid NOT NULL,
                event character varying(64) NOT NULL,
                location character varying(128) NULL,
                note text NULL,
                occurred_at timestamp with time zone NOT NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_events_delivery FOREIGN KEY (delivery_request_id) REFERENCES delivery_requests(id) ON DELETE CASCADE
            );
        ');
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE INDEX idx_events_delivery_occurred ON delivery_events (delivery_request_id, occurred_at DESC);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('delivery_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('delivery_request_id');
            $table->string('event', 64);
            $table->string('location', 128)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['delivery_request_id', 'occurred_at'], 'idx_events_delivery_occurred');
            $table->foreign('delivery_request_id')->references('id')->on('delivery_requests')->cascadeOnDelete();
        });
    }
};
