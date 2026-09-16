<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            $this->createPgsqlEnums();
            $this->createPgsqlTable();
            $this->createPgsqlIndexes();
        } else {
            $this->createSqliteTable();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_requests');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS delivery_payment_method;');
            DB::statement('DROP TYPE IF EXISTS delivery_status;');
            DB::statement('DROP TYPE IF EXISTS courier_type;');
            DB::statement('DROP TYPE IF EXISTS delivery_doc_type;');
        }
    }

    private function createPgsqlEnums(): void
    {
        DB::statement("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'delivery_doc_type') THEN
                    CREATE TYPE delivery_doc_type AS ENUM (
                        'smart_card', 'identity_booklet', 'official_certificate',
                        'sealed_dossier', 'business_license', 'postal_packet'
                    );
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'courier_type') THEN
                    CREATE TYPE courier_type AS ENUM (
                        'express_courier', 'special_post', 'registered_post'
                    );
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'delivery_status') THEN
                    CREATE TYPE delivery_status AS ENUM (
                        'ready_for_dispatch', 'courier_assigned', 'in_transit', 'delivered', 'failed'
                    );
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'delivery_payment_method') THEN
                    CREATE TYPE delivery_payment_method AS ENUM (
                        'cod', 'prepaid', 'office_wallet'
                    );
                END IF;
            END $$;
        ");
    }

    private function createPgsqlTable(): void
    {
        DB::statement("
            CREATE TABLE delivery_requests (
                id uuid PRIMARY KEY,
                case_id uuid NOT NULL,
                office_id uuid NOT NULL,
                doc_type delivery_doc_type NOT NULL,
                doc_type_name character varying(128) NOT NULL,
                doc_serial_number character varying(64) NULL,
                destination_address text NOT NULL,
                destination_postal_code character(10) NOT NULL,
                destination_zone character varying(64) NULL,
                courier_type courier_type NOT NULL,
                delivery_status delivery_status NOT NULL DEFAULT 'ready_for_dispatch',
                courier_name character varying(128) NULL,
                courier_phone character varying(32) NULL,
                courier_plate character varying(32) NULL,
                otp_hash character varying(60) NULL,
                otp_expires_at timestamp with time zone NULL,
                shipping_fee_rials bigint NOT NULL DEFAULT 0 CHECK (shipping_fee_rials >= 0),
                payment_method delivery_payment_method NOT NULL DEFAULT 'prepaid',
                require_old_doc_return boolean NOT NULL DEFAULT false,
                is_sealed_pack boolean NOT NULL DEFAULT true,
                security_note text NULL,
                tracking_barcode character varying(64) NOT NULL,
                dispatched_at timestamp with time zone NULL,
                delivered_at timestamp with time zone NULL,
                created_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_deliveries_office FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE
            );
        ");
    }

    private function createPgsqlIndexes(): void
    {
        DB::statement('CREATE UNIQUE INDEX idx_deliveries_barcode ON delivery_requests (tracking_barcode);');
        DB::statement('CREATE INDEX idx_deliveries_case ON delivery_requests (case_id);');
        DB::statement('CREATE INDEX idx_deliveries_office_status ON delivery_requests (office_id, delivery_status);');
        DB::statement('CREATE INDEX idx_deliveries_created_at ON delivery_requests (created_at);');
    }

    private function createSqliteTable(): void
    {
        Schema::create('delivery_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->uuid('office_id');
            $table->string('doc_type', 32);
            $table->string('doc_type_name', 128);
            $table->string('doc_serial_number', 64)->nullable();
            $table->text('destination_address');
            $table->char('destination_postal_code', 10);
            $table->string('destination_zone', 64)->nullable();
            $table->string('courier_type', 32);
            $table->string('delivery_status', 32)->default('ready_for_dispatch');
            $table->string('courier_name', 128)->nullable();
            $table->string('courier_phone', 32)->nullable();
            $table->string('courier_plate', 32)->nullable();
            $table->string('otp_hash', 60)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedBigInteger('shipping_fee_rials')->default(0);
            $table->string('payment_method', 32)->default('prepaid');
            $table->boolean('require_old_doc_return')->default(false);
            $table->boolean('is_sealed_pack')->default(true);
            $table->text('security_note')->nullable();
            $table->string('tracking_barcode', 64)->unique('idx_deliveries_barcode');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('case_id', 'idx_deliveries_case');
            $table->index(['office_id', 'delivery_status'], 'idx_deliveries_office_status');
            $table->index('created_at', 'idx_deliveries_created_at');

            $table->foreign('office_id')->references('id')->on('offices')->cascadeOnDelete();
        });
    }
};
