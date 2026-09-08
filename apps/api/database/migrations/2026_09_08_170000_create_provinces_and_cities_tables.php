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
     * Architecture §6.1, §6.4 & §6.5: 31 Provinces and Cities with PostGIS geography point.
     */
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table): void {
            $table->char('province_code', 3)->primary();
            $table->string('name', 100);
            $table->unsignedInteger('office_count')->default(0);
            $table->timestamps();
        });

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement('
                CREATE TABLE cities (
                    id uuid PRIMARY KEY,
                    province_code character(3) NOT NULL REFERENCES provinces(province_code) ON DELETE CASCADE,
                    name character varying(100) NOT NULL,
                    center geography(Point, 4326) NOT NULL,
                    created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
            ');

            // GIST spatial index on city center point (§6.4 & TASK-018)
            DB::statement('CREATE INDEX idx_cities_center_gist ON cities USING GIST (center);');
            DB::statement('CREATE INDEX idx_cities_province_code ON cities (province_code);');
        } else {
            // SQLite / fallback for local tests without PostGIS
            Schema::create('cities', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->char('province_code', 3);
                $table->string('name', 100);
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->timestamps();

                $table->foreign('province_code')->references('province_code')->on('provinces')->onDelete('cascade');
                $table->index('province_code', 'idx_cities_province_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
    }
};
