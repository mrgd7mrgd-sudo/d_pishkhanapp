<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('_test_module_registry', function (Blueprint $table): void {
            $table->id();
            $table->string('module_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('_test_module_registry');
    }
};
