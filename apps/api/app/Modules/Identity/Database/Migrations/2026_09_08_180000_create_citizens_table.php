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
     * Architecture §6.1, §6.2, §7.4: Citizens table with encrypted PII columns.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement("
                DO $$ BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'citizen_tier') THEN
                        CREATE TYPE citizen_tier AS ENUM ('bronze', 'silver', 'gold');
                    END IF;
                END $$;
            ");

            DB::statement('
                CREATE TABLE citizens (
                    id uuid PRIMARY KEY,
                    national_id_encrypted text NULL,
                    national_id_hash character(64) NULL,
                    mobile_encrypted text NOT NULL,
                    mobile_hash character(64) NOT NULL,
                    full_name character varying(150) NOT NULL,
                    father_name character varying(100) NULL,
                    birth_date date NULL,
                    postal_code character(10) NULL,
                    address text NULL,
                    city_id uuid NULL REFERENCES cities(id) ON DELETE SET NULL,
                    province_code character(3) NULL REFERENCES provinces(province_code) ON DELETE SET NULL,
                    tier citizen_tier NOT NULL DEFAULT \'bronze\',
                    sana_verified boolean NOT NULL DEFAULT false,
                    digital_signature_active boolean NOT NULL DEFAULT false,
                    credit_score integer NOT NULL DEFAULT 500,
                    created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    deleted_at timestamp with time zone NULL
                );
            ');

            // Unique indexes on hashes for non-deleted records (§6.4)
            DB::statement('CREATE UNIQUE INDEX idx_citizens_nid_hash ON citizens (national_id_hash) WHERE deleted_at IS NULL AND national_id_hash IS NOT NULL;');
            DB::statement('CREATE UNIQUE INDEX idx_citizens_mobile_hash ON citizens (mobile_hash) WHERE deleted_at IS NULL;');
            DB::statement('CREATE INDEX idx_citizens_province_code ON citizens (province_code);');
            DB::statement('CREATE INDEX idx_citizens_city_id ON citizens (city_id);');
        } else {
            Schema::create('citizens', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->text('national_id_encrypted')->nullable();
                $table->char('national_id_hash', 64)->nullable();
                $table->text('mobile_encrypted')->nullable();
                $table->char('mobile_hash', 64);
                $table->string('full_name', 150);
                $table->string('father_name', 100)->nullable();
                $table->date('birth_date')->nullable();
                $table->char('postal_code', 10)->nullable();
                $table->text('address')->nullable();
                $table->uuid('city_id')->nullable();
                $table->char('province_code', 3)->nullable();
                $table->string('tier', 20)->default('bronze');
                $table->boolean('sana_verified')->default(false);
                $table->boolean('digital_signature_active')->default(false);
                $table->integer('credit_score')->default(500);
                $table->timestamps();
                $table->softDeletes();

                $table->unique('national_id_hash', 'idx_citizens_nid_hash');
                $table->unique('mobile_hash', 'idx_citizens_mobile_hash');
                $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
                $table->foreign('province_code')->references('province_code')->on('provinces')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};
