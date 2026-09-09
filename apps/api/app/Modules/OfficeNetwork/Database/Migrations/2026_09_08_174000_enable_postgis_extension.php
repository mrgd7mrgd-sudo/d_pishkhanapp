<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.4 & TASK-037: Enable PostGIS and pg_trgm extensions in PostgreSQL.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Typically extensions are not dropped in down migrations to prevent accidental dependency breakage
    }
};
