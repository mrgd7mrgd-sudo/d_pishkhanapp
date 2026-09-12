<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROVINCES = [
        'THR', 'ESF', 'FRS', 'KHZ', 'EAZ', 'WAZ', 'ARD', 'ILM', 'BSH', 'CHB',
        'SKH', 'RKH', 'NKH', 'ZNJ', 'SMN', 'SBN', 'QZW', 'QOM', 'KRD', 'KMN',
        'KSH', 'KBD', 'GLS', 'GLN', 'LRS', 'MZN', 'MRK', 'HRZ', 'HMD', 'YZD',
        'ABZ',
    ];

    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            $this->createPgsqlEnums();
            $this->createPgsqlTable();
            $this->createPgsqlPartitions();
            $this->createPgsqlIndexes();
        } else {
            $this->createSqliteTable();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_requests');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS case_status;');
            DB::statement('DROP TYPE IF EXISTS turn_owner;');
            DB::statement('DROP TYPE IF EXISTS delivery_preference;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'case_status') THEN
                    CREATE TYPE case_status AS ENUM (
                        'draft','searching_office','assigned_to_office','expert_review',
                        'action_required','government_inquiry','ready_for_issue',
                        'delivering','completed','rejected','cancelled'
                    );
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'turn_owner') THEN
                    CREATE TYPE turn_owner AS ENUM (
                        'citizen','office','government','postal','system'
                    );
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'delivery_preference') THEN
                    CREATE TYPE delivery_preference AS ENUM (
                        'in_person','courier','post'
                    );
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement("
            CREATE TABLE case_requests (
                id uuid NOT NULL,
                tracking_code character varying(32) NOT NULL,
                citizen_id uuid NOT NULL,
                service_id uuid NOT NULL,
                office_id uuid NULL,
                province_code character(3) NOT NULL,
                status case_status NOT NULL DEFAULT 'draft',
                turn_owner turn_owner NOT NULL DEFAULT 'system',
                current_step integer NOT NULL DEFAULT 1,
                total_steps integer NOT NULL DEFAULT 6,
                fee_paid_rials bigint NOT NULL DEFAULT 0,
                office_share_rials bigint NOT NULL DEFAULT 0,
                platform_share_rials bigint NOT NULL DEFAULT 0,
                citizen_location geography(POINT, 4326) NULL,
                delegation_id uuid NULL,
                delivery_preference delivery_preference NOT NULL DEFAULT 'in_person',
                sla_deadline_at timestamp with time zone NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                closed_at timestamp with time zone NULL,
                PRIMARY KEY (id, province_code)
            ) PARTITION BY LIST (province_code);
        ");
    }

    private function createPgsqlPartitions(): void
    {
        foreach (self::PROVINCES as $province) {
            $tableName = 'case_requests_'.strtolower($province);
            DB::statement("CREATE TABLE IF NOT EXISTS {$tableName} PARTITION OF case_requests FOR VALUES IN ('{$province}');");
        }

        DB::statement('CREATE TABLE IF NOT EXISTS case_requests_default PARTITION OF case_requests DEFAULT;');
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE UNIQUE INDEX idx_cases_tracking ON case_requests (tracking_code, province_code);');
        DB::statement('CREATE INDEX idx_cases_citizen_status ON case_requests (citizen_id, status, created_at DESC);');
        DB::statement("CREATE INDEX idx_cases_office_status ON case_requests (office_id, status, sla_deadline_at) WHERE status IN ('assigned_to_office','expert_review','ready_for_issue');");
        DB::statement("CREATE INDEX idx_cases_turn_owner ON case_requests (turn_owner, sla_deadline_at) WHERE status NOT IN ('completed','rejected','cancelled');");
        DB::statement('CREATE INDEX idx_cases_citizen_location ON case_requests USING GIST (citizen_location);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('case_requests', function (Blueprint $table): void {
            $table->uuid('id');
            $table->string('tracking_code', 32);
            $table->uuid('citizen_id');
            $table->uuid('service_id');
            $table->uuid('office_id')->nullable();
            $table->char('province_code', 3);
            $table->string('status', 32)->default('draft');
            $table->string('turn_owner', 32)->default('system');
            $table->unsignedInteger('current_step')->default(1);
            $table->unsignedInteger('total_steps')->default(6);
            $table->unsignedBigInteger('fee_paid_rials')->default(0);
            $table->unsignedBigInteger('office_share_rials')->default(0);
            $table->unsignedBigInteger('platform_share_rials')->default(0);
            $table->text('citizen_location')->nullable();
            $table->uuid('delegation_id')->nullable();
            $table->string('delivery_preference', 32)->default('in_person');
            $table->timestamp('sla_deadline_at')->nullable();
            $table->timestamps();
            $table->timestamp('closed_at')->nullable();

            $table->primary(['id', 'province_code']);
            $table->unique(['tracking_code', 'province_code'], 'idx_cases_tracking');
            $table->index(['citizen_id', 'status', 'created_at'], 'idx_cases_citizen_status');
            $table->index(['office_id', 'status', 'sla_deadline_at'], 'idx_cases_office_status');
            $table->index(['turn_owner', 'sla_deadline_at'], 'idx_cases_turn_owner');
        });
    }
};
