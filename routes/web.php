<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ContentController;

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
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->name('password.update');
});

// Authenticated routes
Route::middleware(['auth.user', 'check.status'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Public content viewing routes
    Route::get('/content/{slug}', [\App\Http\Controllers\ContentViewController::class, 'show'])->name('content.show');
    Route::get('/content/{uuid}/embed', [\App\Http\Controllers\ContentViewController::class, 'embed'])->name('content.embed');
    Route::post('/content/{uuid}/access-token', [\App\Http\Controllers\ContentViewController::class, 'generateAccessToken'])->name('content.access-token');
    Route::get('/content/secure/{token}', [\App\Http\Controllers\ContentViewController::class, 'secureView'])->name('content.secure-view');
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Profile management
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar/upload', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar.upload');
    Route::delete('/profile/avatar/remove', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.change');
    Route::get('/profile/activity', [ProfileController::class, 'getActivityHistory'])->name('profile.activity');
    Route::put('/profile/notifications', [ProfileController::class, 'updateNotificationPreferences'])->name('profile.notifications.update');
});

// Admin routes (requires admin role)
Route::middleware(['auth.user', 'check.status', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    // User management routes
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::post('users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/bulk-action', [\App\Http\Controllers\Admin\UserController::class, 'bulkAction'])->name('users.bulk-action');
    
    // User invitation routes
    Route::get('invitations', [\App\Http\Controllers\Admin\InvitationController::class, 'index'])->name('invitations.index');
    Route::post('invitations/send', [\App\Http\Controllers\Admin\InvitationController::class, 'send'])->name('invitations.send');
    Route::post('invitations/resend', [\App\Http\Controllers\Admin\InvitationController::class, 'resend'])->name('invitations.resend');
    Route::get('invitations/status', [\App\Http\Controllers\Admin\InvitationController::class, 'status'])->name('invitations.status');
    Route::get('invitations/{user}/history', [\App\Http\Controllers\Admin\InvitationController::class, 'history'])->name('invitations.history');
    Route::post('invitations/cancel', [\App\Http\Controllers\Admin\InvitationController::class, 'cancel'])->name('invitations.cancel');
    Route::post('invitations/cleanup', [\App\Http\Controllers\Admin\InvitationController::class, 'cleanup'])->name('invitations.cleanup');
    Route::get('invitations/stats', [\App\Http\Controllers\Admin\InvitationController::class, 'stats'])->name('invitations.stats');
    
    // Role management routes
    Route::resource('roles', RoleController::class);
    Route::get('roles/{role}/permissions', [RoleController::class, 'getPermissions'])->name('roles.permissions');
    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
    
    // Permission management routes
    Route::resource('permissions', PermissionController::class);
    Route::get('permissions/{permission}/roles', [PermissionController::class, 'getRoles'])->name('permissions.roles');
    Route::put('permissions/{permission}/roles', [PermissionController::class, 'updateRoles'])->name('permissions.roles.update');
    Route::get('permissions/hierarchy/tree', [PermissionController::class, 'getHierarchy'])->name('permissions.hierarchy');
    
    // Role-Permission Assignment Routes
    Route::get('/role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
    Route::get('/role-permissions/matrix-data', [RolePermissionController::class, 'getMatrixData'])->name('role-permissions.matrix-data');
    Route::post('/role-permissions/assign', [RolePermissionController::class, 'assignPermission'])->name('role-permissions.assign');
    Route::delete('/role-permissions/remove', [RolePermissionController::class, 'removePermission'])->name('role-permissions.remove');
    Route::post('/role-permissions/bulk-assign', [RolePermissionController::class, 'bulkAssign'])->name('role-permissions.bulk-assign');
    Route::post('/role-permissions/sync', [RolePermissionController::class, 'syncPermissions'])->name('role-permissions.sync');
    
    // Legacy Role-Permission Routes (for backward compatibility)
    Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions'])->name('roles.assign-permissions');
    Route::delete('/roles/{role}/permissions/{permission}', [RoleController::class, 'removePermission'])->name('roles.remove-permission');
    
    // Permission Hierarchy Routes
    Route::get('/permissions/hierarchy', [PermissionController::class, 'hierarchy'])->name('permissions.hierarchy');
    Route::post('/permissions/{permission}/children', [PermissionController::class, 'addChild'])->name('permissions.add-child');
    Route::delete('/permissions/{permission}/children/{child}', [PermissionController::class, 'removeChild'])->name('permissions.remove-child');
    
    // Menu management routes
    // Put specific routes before resource routes to avoid conflicts
    Route::get('menus/bulk-role-assignment', [MenuController::class, 'showBulkRoleAssignment'])->name('menus.bulk-role-assignment.show');
    Route::post('menus/bulk-role-assignment', [MenuController::class, 'bulkRoleAssignment'])->name('menus.bulk-role-assignment');
    Route::get('menus/data/table', [MenuController::class, 'getData'])->name('menus.data');
    Route::get('menus/roles/cache/clear', [MenuController::class, 'clearRoleCache'])->name('menus.clear-role-cache');
    
    Route::resource('menus', MenuController::class);
    Route::get('menus/{menu}', [MenuController::class, 'show'])->name('menus.show'); // Explicit show route
    Route::post('menus/{menu}/duplicate', [MenuController::class, 'duplicate'])->name('menus.duplicate');
    Route::post('menus/{menu}/toggle-status', [MenuController::class, 'toggleStatus'])->name('menus.toggle-status');
    Route::post('menus/update-order', [MenuController::class, 'updateOrder'])->name('menus.update-order');
    Route::get('menus/{menu}/preview', [MenuController::class, 'preview'])->name('menus.preview');
    
    // Menu-Role Assignment Routes
    Route::get('menus/{menu}/roles', [MenuController::class, 'showRoleAssignment'])->name('menus.roles');
    Route::post('menus/{menu}/roles/assign', [MenuController::class, 'assignRoles'])->name('menus.assign-roles');
    Route::delete('menus/{menu}/roles/{role}', [MenuController::class, 'removeRole'])->name('menus.remove-role');
    
    // Content management routes
    Route::resource('contents', ContentController::class);
    Route::get('contents/{content}/preview', [ContentController::class, 'preview'])->name('contents.preview');
    Route::post('contents/{content}/duplicate', [ContentController::class, 'duplicate'])->name('contents.duplicate');
    Route::post('contents/{content}/toggle-status', [ContentController::class, 'toggleStatus'])->name('contents.toggle-status');
    Route::get('contents/{content}/versions', [ContentController::class, 'versions'])->name('contents.versions');
    Route::post('contents/{content}/restore/{version}', [ContentController::class, 'restoreVersion'])->name('contents.restore-version');
    Route::get('contents/data/table', [ContentController::class, 'getData'])->name('contents.data');
    Route::post('contents/bulk-action', [ContentController::class, 'bulkAction'])->name('contents.bulk-action');
    
    // Upload routes for content editor
    Route::post('upload/image', [\App\Http\Controllers\Admin\UploadController::class, 'uploadImage'])->name('upload.image');
    Route::post('upload/file', [\App\Http\Controllers\Admin\UploadController::class, 'uploadFile'])->name('upload.file');
    Route::delete('upload/file', [\App\Http\Controllers\Admin\UploadController::class, 'deleteFile'])->name('upload.delete');
});

// Special authentication flow routes (accessible when authenticated but with specific conditions)
Route::middleware('auth.user')->group(function () {
    // First-time password change
    Route::get('/password/first-change', [PasswordChangeController::class, 'showFirstChangeForm'])
        ->name('password.first-change');
    Route::post('/password/first-change', [PasswordChangeController::class, 'updateFirstChange'])
        ->name('password.first-change.update');
    
    // Password expired
    Route::get('/password/expired', [PasswordChangeController::class, 'showExpiredForm'])
        ->name('password.expired');
    Route::post('/password/expired', [PasswordChangeController::class, 'updateExpired'])
        ->name('password.expired.update');
    
    // Password requirements API
    Route::get('/api/password/requirements', [PasswordChangeController::class, 'getPasswordRequirements'])
        ->name('password.requirements');
    
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
