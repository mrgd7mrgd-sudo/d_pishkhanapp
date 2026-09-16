<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.1: session_messages table
     */
    public function up(): void
    {
        Schema::create('session_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->string('sender_type', 32); // citizen, advisor, system
            $table->uuid('sender_id');
            $table->text('body');
            $table->string('attachment_key', 255)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('session_id')
                ->references('id')
                ->on('consultation_sessions')
                ->cascadeOnDelete();

            $table->index(['session_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_messages');
    }
};
