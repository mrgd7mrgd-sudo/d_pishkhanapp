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
     * Architecture §6.1, §7.2: OTP challenges table.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement("
                DO $$ BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'otp_purpose') THEN
                        CREATE TYPE otp_purpose AS ENUM ('login', 'register', 'delegation', 'sensitive_action');
                    END IF;
                END $$;
            ");

            DB::statement('
                CREATE TABLE otp_challenges (
                    id uuid PRIMARY KEY,
                    mobile_hash character(64) NOT NULL,
                    code_hash character varying(255) NOT NULL,
                    purpose otp_purpose NOT NULL DEFAULT \'login\',
                    attempts integer NOT NULL DEFAULT 0,
                    expires_at timestamp with time zone NOT NULL,
                    verified_at timestamp with time zone NULL,
                    ip_address character varying(45) NULL,
                    created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
            ');

            // Compound index for active challenges lookup (§6.1, §6.4, §7.2)
            DB::statement('CREATE INDEX idx_otp_mobile_purpose ON otp_challenges (mobile_hash, purpose, expires_at);');
        } else {
            Schema::create('otp_challenges', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->char('mobile_hash', 64);
                $table->string('code_hash', 255);
                $table->string('purpose', 32)->default('login');
                $table->integer('attempts')->default(0);
                $table->timestamp('expires_at');
                $table->timestamp('verified_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['mobile_hash', 'purpose', 'expires_at'], 'idx_otp_mobile_purpose');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_challenges');
    }
};
