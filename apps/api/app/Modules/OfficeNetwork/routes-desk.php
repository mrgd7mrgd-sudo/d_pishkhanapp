<?php

declare(strict_types=1);

use App\Modules\OfficeNetwork\Http\Controllers\DeskOfficeController;
use Illuminate\Support\Facades\Route;

Route::get('/desk/offices/{office}', [DeskOfficeController::class, 'show']);
Route::get('/desk/offices/{office}/operators', [DeskOfficeController::class, 'operators']);
Route::get('/desk/operators/{operator}', [DeskOfficeController::class, 'showOperator']);
