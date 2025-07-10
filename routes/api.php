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

// Public API routes
Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    });
    
    // Authentication endpoints
    Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'apiLogin'])
        ->middleware('throttle:login');
    
    Route::post('/auth/forgot-password', [\App\Http\Controllers\AuthController::class, 'apiSendResetLink'])
        ->middleware('throttle:password-reset');
});

// Protected API routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Logout
    Route::post('/auth/logout', [\App\Http\Controllers\AuthController::class, 'apiLogout']);
    
    // Profile management
    Route::get('/profile', function (Request $request) {
        return response()->json($request->user());
    });
    
    Route::put('/profile', function (Request $request) {
        // Profile update logic for API
        return response()->json(['message' => 'Profile updated successfully']);
    });
});