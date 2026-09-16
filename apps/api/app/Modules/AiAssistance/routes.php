<?php

declare(strict_types=1);

use App\Modules\AiAssistance\Http\Controllers\AiChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('ai')->group(function (): void {
    Route::post('/chat', [AiChatController::class, 'chat']);
    Route::get('/conversations', [AiChatController::class, 'indexConversations']);
    Route::get('/conversations/{id}', [AiChatController::class, 'showConversation']);
});
