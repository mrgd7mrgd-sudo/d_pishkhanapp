<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1: ai_conversations table
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement("
                DO $$ BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ai_channel') THEN
                        CREATE TYPE ai_channel AS ENUM ('chat', 'voice');
                    END IF;
                END $$;
            ");
        }

        Schema::create('ai_conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('citizen_id');
            $table->string('channel', 16)->default('chat');
            $table->string('title', 255)->default('گفتگوی جدید');
            $table->timestamps();

            $table->foreign('citizen_id')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();

            $table->index(['citizen_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS ai_channel;');
        }
    }
};
