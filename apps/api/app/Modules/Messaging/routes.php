<?php

declare(strict_types=1);

use App\Modules\Messaging\Http\Controllers\CaseMessageController;
use App\Modules\Messaging\Http\Controllers\NotificationController;
use App\Modules\Messaging\Http\Controllers\NotificationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    // Case Messages
    Route::get('/cases/{id}/messages', [CaseMessageController::class, 'index']);
    Route::post('/cases/{id}/messages', [CaseMessageController::class, 'store']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Notification Preferences
    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::patch('/notification-preferences', [NotificationPreferenceController::class, 'update']);
});
