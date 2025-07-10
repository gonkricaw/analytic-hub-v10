<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Guest routes (accessible only when not authenticated)
Route::middleware('guest')->group(function () {
    // Login routes
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.submit');
    
    // Password reset routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])
        ->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])
        ->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.update');
});

// Authenticated routes
Route::middleware(['auth.user', 'check.status'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Profile management
    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile.show');
    
    Route::put('/profile', function () {
        // Profile update logic
    })->name('profile.update');
});

// Special authentication flow routes (accessible when authenticated but with specific conditions)
Route::middleware('auth.user')->group(function () {
    // First-time password change
    Route::get('/password/first-change', [AuthController::class, 'showFirstPasswordChangeForm'])
        ->name('password.first-change');
    Route::post('/password/first-change', [AuthController::class, 'updateFirstPassword'])
        ->name('password.first-change.update');
    
    // Password expired
    Route::get('/password/expired', [AuthController::class, 'showPasswordExpiredForm'])
        ->name('password.expired');
    Route::post('/password/expired', [AuthController::class, 'updateExpiredPassword'])
        ->name('password.expired.update');
    
    // Terms & Conditions acceptance
    Route::get('/terms/accept', function () {
        return view('auth.terms-accept');
    })->name('terms.accept');
    Route::post('/terms/accept', [AuthController::class, 'acceptTerms'])
        ->name('terms.accept.submit');
    
    // Email verification
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');
    
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
        ->middleware('throttle:email-verification')
        ->name('verification.send');
});

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Error pages
Route::get('/blocked', function () {
    return view('errors.blocked');
})->name('blocked');
