<?php

declare(strict_types=1);

use App\Modules\OfficeNetwork\Http\Controllers\DeskAppointmentController;
use App\Modules\OfficeNetwork\Http\Controllers\DeskOfficeController;
use App\Modules\OfficeNetwork\Http\Controllers\DeskReviewController;
use App\Modules\OfficeNetwork\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

Route::get('/desk/queue', [QueueController::class, 'show']);
Route::get('/desk/offices/{office}', [DeskOfficeController::class, 'show']);
Route::get('/desk/offices/{office}/operators', [DeskOfficeController::class, 'operators']);
Route::get('/desk/operators/{operator}', [DeskOfficeController::class, 'showOperator']);

// Operator desk appointments (§5.3, §6.1, TASK-100)
Route::get('/desk/appointments', [DeskAppointmentController::class, 'index']);
Route::get('/desk/appointments/{id}', [DeskAppointmentController::class, 'show']);
Route::post('/desk/appointments/{id}/attendance', [DeskAppointmentController::class, 'setAttendance']);
Route::post('/desk/appointments/{id}/completion', [DeskAppointmentController::class, 'setCompletion']);

// Operator desk reviews (§4.4, §7.3, TASK-101)
Route::get('/desk/reviews', [DeskReviewController::class, 'index']);
Route::get('/desk/reviews/{id}', [DeskReviewController::class, 'show']);
Route::post('/desk/reviews/{id}/reply', [DeskReviewController::class, 'reply']);
