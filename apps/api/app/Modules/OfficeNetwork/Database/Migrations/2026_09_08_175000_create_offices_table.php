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
     * Architecture §6.1, §6.4: Offices table with PostGIS geography point & GIST index.
     * STRICT RULE (§6.2, §6.4): distance_km and coords.mapX/mapY MUST NOT exist!
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement("
                DO $$ BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'office_membership_status') THEN
                        CREATE TYPE office_membership_status AS ENUM ('registered_online','registered_offline','unregistered');
                    END IF;
                END $$;
            ");

            DB::statement("
                CREATE TABLE offices (
                    id uuid PRIMARY KEY,
                    code character(4) NOT NULL,
                    name character varying(150) NOT NULL,
                    manager_name character varying(150) NOT NULL DEFAULT '',
                    membership_status office_membership_status NOT NULL DEFAULT 'unregistered',
                    is_online boolean NOT NULL DEFAULT true,
                    rating numeric(3, 2) NOT NULL DEFAULT 0.00,
                    review_count integer NOT NULL DEFAULT 0,
                    address text NULL,
                    city_id uuid NULL,
                    province_code character(3) NULL,
                    location geography(POINT, 4326) NULL,
                    phone character varying(32) NULL,
                    working_hours jsonb NULL,
                    active_counters integer NOT NULL DEFAULT 1,
                    current_waiting_queue integer NOT NULL DEFAULT 0,
                    sla_score numeric(4, 2) NOT NULL DEFAULT 100.00,
                    created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    deleted_at timestamp with time zone NULL
                );
            ");

            DB::statement('CREATE UNIQUE INDEX idx_offices_code ON offices (code) WHERE deleted_at IS NULL;');
            DB::statement('CREATE INDEX idx_offices_location ON offices USING GIST (location);');
            DB::statement('CREATE INDEX idx_offices_online_prov ON offices (province_code, is_online, rating DESC) WHERE deleted_at IS NULL;');
            DB::statement('CREATE INDEX idx_offices_name_trgm ON offices USING GIN (name gin_trgm_ops);');
        } else {
            Schema::create('offices', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->char('code', 4);
                $table->string('name', 150);
                $table->string('manager_name', 150)->default('');
                $table->string('membership_status', 32)->default('unregistered');
                $table->boolean('is_online')->default(true);
                $table->decimal('rating', 3, 2)->default(0.00);
                $table->unsignedInteger('review_count')->default(0);
                $table->text('address')->nullable();
                $table->uuid('city_id')->nullable();
                $table->char('province_code', 3)->nullable();
                $table->text('location')->nullable();
                $table->string('phone', 32)->nullable();
                $table->json('working_hours')->nullable();
                $table->unsignedInteger('active_counters')->default(1);
                $table->unsignedInteger('current_waiting_queue')->default(0);
                $table->decimal('sla_score', 4, 2)->default(100.00);
                $table->timestamps();
                $table->softDeletes();

                $table->unique('code', 'idx_offices_code');
                $table->index(['province_code', 'is_online', 'rating'], 'idx_offices_online_prov');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS office_membership_status;');
        }
    }
};
