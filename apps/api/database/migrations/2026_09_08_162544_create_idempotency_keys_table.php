<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->string('key', 64)->primary();
            $table->uuid('actor_id')->nullable();
            $table->string('endpoint');
            $table->string('request_hash', 64);
            $table->jsonb('response_body');
            $table->integer('response_status');
            $table->timestampTz('expires_at');
            $table->timestamps();

            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
