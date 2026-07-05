<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LandmarkController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AlbumController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    // Multipart file uploads must use POST: PHP does not populate $_FILES for PUT.
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);

    // Landmarks (all authenticated can list & show; only admin can create/update/delete)
    Route::get('/landmarks', [LandmarkController::class, 'index']);
    Route::get('/landmarks/{landmark}', [LandmarkController::class, 'show']);
    Route::post('/scan', [LandmarkController::class, 'scan']);
    Route::middleware('admin')->group(function () {
        Route::post('/landmarks', [LandmarkController::class, 'store']);
        Route::put('/landmarks/{landmark}', [LandmarkController::class, 'update']);
        Route::post('/landmarks/{landmark}', [LandmarkController::class, 'update']); // Add this for multipart updates
        Route::delete('/landmarks/{landmark}', [LandmarkController::class, 'destroy']);
    });

    // Translations
    Route::apiResource('translations', TranslationController::class);
    Route::post('/translate', [TranslationController::class, 'translateImage']);

    // Chat
    Route::post('/chat', [ChatController::class, 'sendMessage']);
    Route::get('/chat/history', [ChatController::class, 'history']);

    // Albums
    Route::apiResource('albums', AlbumController::class);

    // Favorites
    Route::get('/favorites', [\App\Http\Controllers\Api\FavoriteController::class, 'index']);
    Route::post('/favorites', [\App\Http\Controllers\Api\FavoriteController::class, 'store']);
    Route::delete('/favorites/{landmarkId}', [\App\Http\Controllers\Api\FavoriteController::class, 'destroy']);

    // Admin only
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
        Route::post('/users/{id}/block', [\App\Http\Controllers\Api\AdminController::class, 'blockUser']);
        Route::post('/users/{id}/unblock', [\App\Http\Controllers\Api\AdminController::class, 'unblockUser']);
        Route::get('/favorites/count', [\App\Http\Controllers\Api\AdminController::class, 'favoritesCount']);
        Route::get('/chat/count', [\App\Http\Controllers\Api\AdminController::class, 'chatCount']);
    });
});
