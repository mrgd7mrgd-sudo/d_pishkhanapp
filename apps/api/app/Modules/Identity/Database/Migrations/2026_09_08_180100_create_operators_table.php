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
     * Architecture §6.1, §6.2, §7.4: Operators table.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement("
                DO $$ BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'operator_role') THEN
                        CREATE TYPE operator_role AS ENUM ('operator', 'manager');
                    END IF;
                END $$;
            ");

            DB::statement('
                CREATE TABLE operators (
                    id uuid PRIMARY KEY,
                    office_id uuid NULL,
                    username character varying(50) NOT NULL,
                    password_hash character varying(255) NOT NULL,
                    full_name character varying(150) NOT NULL,
                    national_id_encrypted text NULL,
                    national_id_hash character(64) NOT NULL,
                    mobile_encrypted text NULL,
                    mobile_hash character(64) NOT NULL,
                    allowed_ip_ranges text NULL,
                    role operator_role NOT NULL DEFAULT \'operator\',
                    counter_number integer NOT NULL DEFAULT 1,
                    is_active boolean NOT NULL DEFAULT true,
                    last_login_at timestamp with time zone NULL,
                    created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    deleted_at timestamp with time zone NULL
                );
            ');

            DB::statement('CREATE UNIQUE INDEX idx_operators_username ON operators (username) WHERE deleted_at IS NULL;');
            DB::statement('CREATE UNIQUE INDEX idx_operators_nid_hash ON operators (national_id_hash) WHERE deleted_at IS NULL;');
            DB::statement('CREATE INDEX idx_operators_office_id ON operators (office_id);');
            DB::statement('CREATE INDEX idx_operators_mobile_hash ON operators (mobile_hash);');
        } else {
            Schema::create('operators', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('office_id')->nullable();
                $table->string('username', 50);
                $table->string('password_hash', 255);
                $table->string('full_name', 150);
                $table->text('national_id_encrypted')->nullable();
                $table->char('national_id_hash', 64);
                $table->text('mobile_encrypted')->nullable();
                $table->char('mobile_hash', 64);
                $table->text('allowed_ip_ranges')->nullable();
                $table->string('role', 20)->default('operator');
                $table->integer('counter_number')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique('username', 'idx_operators_username');
                $table->unique('national_id_hash', 'idx_operators_nid_hash');
                $table->index('office_id', 'idx_operators_office_id');
                $table->index('mobile_hash', 'idx_operators_mobile_hash');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operators');
    }
};
