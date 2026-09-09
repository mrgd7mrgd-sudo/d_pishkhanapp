<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:operator,sanctum', 'role:office_operator|office_manager|system_admin', 'office.scope'])
    ->group(__DIR__.'/routes-desk.php');
