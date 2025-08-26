<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\app\Http\Controllers\NotificationsController;

/*
|--------------------------------------------------------------------------
| Notification Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public API endpoints (for external services)
Route::prefix('v1/notifications')->group(function () {
    Route::post('/send', [NotificationsController::class, 'send']);
    Route::post('/send-bulk', [NotificationsController::class, 'sendBulk']);
    Route::post('/schedule', [NotificationsController::class, 'schedule']);
    Route::get('/templates', [NotificationsController::class, 'templates']);
    Route::get('/status/{id}', [NotificationsController::class, 'status']);
});

// Internal API endpoints (for authenticated internal services)
Route::prefix('v1/internal/notifications')->middleware('jwt')->group(function () {
    Route::get('/user', [NotificationsController::class, 'userNotifications']);
    Route::post('/mark-read', [NotificationsController::class, 'markAsRead']);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'module' => 'Notifications',
        'timestamp' => now()->toISOString()
    ]);
});
