<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for services (§6.1, §6.2, D-06, TASK-036).
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('category_id');
            $table->foreign('category_id')
                ->references('id')
                ->on('service_categories')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->json('tags');
            $table->json('requirements')->nullable();
            $table->unsignedInteger('estimated_days_min')->default(1);
            $table->unsignedInteger('estimated_days_max')->default(3);
            $table->unsignedBigInteger('fee_rials')->default(0);
            $table->decimal('office_share_percent', 5, 2)->default(70.00);
            $table->string('department')->nullable();
            $table->boolean('is_popular')->default(false)->index();
            $table->boolean('is_new')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

            if (DB::connection()->getDriverName() === 'pgsql') {
                $table->addColumn('tsvector', 'search_vector')->nullable();
            } else {
                $table->text('search_vector')->nullable();
            }

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
