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
     * Architecture §6.1, §6.4: Offices table.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            DB::statement('
 CREATE TABLE offices (
 id uuid PRIMARY KEY,
 code character(4) NOT NULL,
 name character varying(150) NOT NULL,
 is_online boolean NOT NULL DEFAULT true,
 created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
 deleted_at timestamp with time zone NULL
 );
 ');

            DB::statement('CREATE UNIQUE INDEX idx_offices_code ON offices (code) WHERE deleted_at IS NULL;');
        } else {
            Schema::create('offices', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->char('code', 4);
                $table->string('name', 150);
                $table->boolean('is_online')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique('code', 'idx_offices_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
