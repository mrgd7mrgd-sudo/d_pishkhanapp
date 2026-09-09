<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Architecture §6.3, §6.4, TASK-038: Persian Full-Text Search configuration, trigger, and GIN/trigram indexes.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            // 1. Ensure pg_trgm extension exists
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

            // 2. Text Search Configuration 'persian' (COPY = simple)
            DB::statement("
                DO $$ BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_ts_config WHERE cfgname = 'persian') THEN
                        CREATE TEXT SEARCH CONFIGURATION persian (COPY = simple);
                    END IF;
                END $$;
            ");

            // 3. Search vector update trigger function
            DB::statement("
                CREATE OR REPLACE FUNCTION services_search_trigger() RETURNS trigger AS $$
                BEGIN
                    NEW.search_vector :=
                        setweight(to_tsvector('persian', coalesce(NEW.title, '')), 'A') ||
                        setweight(to_tsvector('persian', coalesce(NEW.description, '')), 'B') ||
                        setweight(to_tsvector('persian', coalesce(NEW.department, '')), 'C');
                    RETURN NEW;
                END $$ LANGUAGE plpgsql;
            ");

            // 4. Trigger on insert/update of title, description, department
            DB::statement('DROP TRIGGER IF EXISTS trg_services_search_update ON services;');
            DB::statement('
                CREATE TRIGGER trg_services_search_update
                BEFORE INSERT OR UPDATE OF title, description, department ON services
                FOR EACH ROW EXECUTE FUNCTION services_search_trigger();
            ');

            // 5. GIN indexes for FTS and trigram
            DB::statement('CREATE INDEX IF NOT EXISTS idx_services_search ON services USING GIN (search_vector);');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_services_title_trgm ON services USING GIN (title gin_trgm_ops);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS trg_services_search_update ON services;');
            DB::statement('DROP FUNCTION IF EXISTS services_search_trigger();');
            DB::statement('DROP INDEX IF EXISTS idx_services_search;');
            DB::statement('DROP INDEX IF EXISTS idx_services_title_trgm;');
        }
    }
};
