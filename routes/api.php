<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\ConversationController;
use App\Http\Controllers\API\AdminController;

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

// Public routes
Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::controller(AuthController::class)->group(function () {
        Route::post('logout', 'logout');
        Route::post('refresh', 'refresh');
    });

    // Conversation routes
    Route::apiResource('conversations', ConversationController::class);

    // New route
    Route::get('models', [ChatController::class, 'getModels']);

    // Chat routes
    Route::post('conversations/{conversation}/chat', [ChatController::class, 'chat']);

    // Streaming route
    Route::post('conversations/{conversation}/chat/stream', [ChatController::class, 'chatStream']);

    // History route
    Route::get('conversations/{conversation}/history', [ChatController::class, 'history']);
});

// Super Admin routes
Route::middleware(['auth:api', 'super.admin'])->prefix('admin')->group(function () {
    Route::get('providers', [AdminController::class, 'listProviders']);
    Route::post('providers', [AdminController::class, 'addProvider']);
    Route::post('models', [AdminController::class, 'addModel']);
});
