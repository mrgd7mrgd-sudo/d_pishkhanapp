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
     * Architecture §6.1, §6.4, TASK-099: In-Person Appointments table.
     * Invariants §5.3:
     * - Non-overlapping slot capacity limit
     * - Unique queue_number per office and appointment_date (idx_appointments_queue)
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                DO $$ BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'appointment_status') THEN
                        CREATE TYPE appointment_status AS ENUM ('active', 'completed', 'cancelled');
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'appointment_attendance') THEN
                        CREATE TYPE appointment_attendance AS ENUM ('pending', 'attended', 'absent');
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'appointment_completion') THEN
                        CREATE TYPE appointment_completion AS ENUM ('pending', 'in_progress', 'completed', 'not_completed');
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'appointment_reminder_type') THEN
                        CREATE TYPE appointment_reminder_type AS ENUM ('sms', 'push', 'all');
                    END IF;
                END $$;
            ");

            DB::statement("
                CREATE TABLE appointments (
                    id uuid PRIMARY KEY,
                    citizen_id uuid NOT NULL,
                    office_id uuid NOT NULL REFERENCES offices(id) ON DELETE CASCADE,
                    service_id uuid NOT NULL REFERENCES services(id) ON DELETE CASCADE,
                    appointment_date date NOT NULL,
                    time_slot character varying(32) NOT NULL,
                    tracking_code character varying(32) NOT NULL,
                    status appointment_status NOT NULL DEFAULT 'active',
                    attendance appointment_attendance NOT NULL DEFAULT 'pending',
                    completion appointment_completion NOT NULL DEFAULT 'pending',
                    completion_reason text NULL,
                    queue_number character varying(16) NOT NULL,
                    counter_number integer NOT NULL DEFAULT 1,
                    reminder_enabled boolean NOT NULL DEFAULT true,
                    reminder_type appointment_reminder_type NOT NULL DEFAULT 'all',
                    reminder_at timestamp with time zone NULL,
                    cancelled_at timestamp with time zone NULL,
                    cancellation_reason character varying(255) NULL,
                    created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
            ");

            DB::statement('CREATE INDEX idx_appointments_office_day ON appointments (office_id, appointment_date, status);');
            DB::statement('CREATE UNIQUE INDEX idx_appointments_queue ON appointments (office_id, appointment_date, queue_number);');
            DB::statement('CREATE UNIQUE INDEX idx_appointments_tracking ON appointments (tracking_code);');
            DB::statement('CREATE INDEX idx_appointments_citizen ON appointments (citizen_id, appointment_date DESC);');
        } else {
            Schema::create('appointments', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('citizen_id');
                $table->foreignUuid('office_id')->constrained('offices')->cascadeOnDelete();
                $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
                $table->date('appointment_date');
                $table->string('time_slot', 32);
                $table->string('tracking_code', 32)->unique('idx_appointments_tracking');
                $table->string('status', 32)->default('active');
                $table->string('attendance', 32)->default('pending');
                $table->string('completion', 32)->default('pending');
                $table->text('completion_reason')->nullable();
                $table->string('queue_number', 16);
                $table->unsignedInteger('counter_number')->default(1);
                $table->boolean('reminder_enabled')->default(true);
                $table->string('reminder_type', 16)->default('all');
                $table->timestamp('reminder_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancellation_reason', 255)->nullable();
                $table->timestamps();

                $table->index(['office_id', 'appointment_date', 'status'], 'idx_appointments_office_day');
                $table->unique(['office_id', 'appointment_date', 'queue_number'], 'idx_appointments_queue');
                $table->index(['citizen_id', 'appointment_date'], 'idx_appointments_citizen');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS appointment_status;');
            DB::statement('DROP TYPE IF EXISTS appointment_attendance;');
            DB::statement('DROP TYPE IF EXISTS appointment_completion;');
            DB::statement('DROP TYPE IF EXISTS appointment_reminder_type;');
        }
    }
};
