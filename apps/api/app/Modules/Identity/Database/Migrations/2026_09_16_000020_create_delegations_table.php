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
     * Architecture §5.3, §6.1, §7.1: delegations table with two-party OTP activation
     */
    public function up(): void
    {
        Schema::create('delegations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('principal_citizen_id');
            $table->uuid('delegate_citizen_id');
            $table->string('document_number', 64)->nullable();
            $table->string('status', 32)->default('pending_otp');
            $table->unsignedBigInteger('max_amount_rials');
            $table->jsonb('allowed_service_ids')->nullable();
            $table->string('principal_otp_hash', 64)->nullable();
            $table->string('delegate_otp_hash', 64)->nullable();
            $table->boolean('principal_otp_verified')->default(false);
            $table->boolean('delegate_otp_verified')->default(false);
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('valid_until');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('principal_citizen_id')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();

            $table->foreign('delegate_citizen_id')
                ->references('id')
                ->on('citizens')
                ->cascadeOnDelete();

            $table->index(['principal_citizen_id', 'status']);
            $table->index(['delegate_citizen_id', 'status']);
            $table->index(['status', 'valid_until']);
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE delegations ADD CONSTRAINT check_delegation_max_amount_positive CHECK (max_amount_rials > 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delegations');
    }
};
