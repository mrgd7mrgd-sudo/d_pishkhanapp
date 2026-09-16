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
     * Architecture §6.1, §5.3 Invariant: quota_usages table with used >= 0 constraint
     */
    public function up(): void
    {
        Schema::create('quota_usages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->string('quota_key', 64);
            $table->unsignedInteger('used')->default(0);
            $table->unsignedInteger('limit')->default(0);
            $table->date('period_start');
            $table->timestamps();

            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->cascadeOnDelete();

            $table->unique(['subscription_id', 'quota_key', 'period_start']);
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE quota_usages ADD CONSTRAINT check_used_non_negative CHECK (used >= 0)');
            DB::statement('ALTER TABLE quota_usages ADD CONSTRAINT check_limit_non_negative CHECK ("limit" >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quota_usages');
    }
};
