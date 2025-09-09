<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Test login route without middleware
Route::post('/test-login', function (Request $request) {
    return response()->json([
        'message' => 'Test login endpoint',
        'data' => $request->all()
    ]);
});

// Auth routes using module controller
Route::prefix('v1')->group(function () {
    Route::post('/login', [\Modules\Auth\app\Http\Controllers\AuthUserController\AuthController::class, 'login']);
    Route::post('/login-student', [\Modules\Auth\app\Http\Controllers\AuthUserController\AuthController::class, 'loginStudent']);
    Route::post('/login-lecturer', [\Modules\Auth\app\Http\Controllers\AuthUserController\AuthController::class, 'loginLecturer']);
    Route::post('/logout', [\Modules\Auth\app\Http\Controllers\AuthUserController\AuthController::class, 'logout']);
    Route::post('/refresh', [\Modules\Auth\app\Http\Controllers\AuthUserController\AuthController::class, 'refresh']);
    Route::get('/me', [\Modules\Auth\app\Http\Controllers\AuthUserController\AuthController::class, 'me']);
});
