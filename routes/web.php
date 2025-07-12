<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
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
    
    // Widget API routes for dashboard
    Route::prefix('api/widgets')->name('widgets.')->group(function () {
        Route::get('/clock', [\App\Http\Controllers\WidgetController::class, 'getClock'])->name('clock');
        Route::get('/login-activity', [\App\Http\Controllers\WidgetController::class, 'getLoginActivity'])->name('login-activity');
        Route::get('/active-users', [\App\Http\Controllers\WidgetController::class, 'getActiveUsers'])->name('active-users');
        Route::get('/online-users', [\App\Http\Controllers\WidgetController::class, 'getOnlineUsers'])->name('online-users');
        Route::get('/popular-content', [\App\Http\Controllers\WidgetController::class, 'getPopularContent'])->name('popular-content');
        Route::get('/announcements', [\App\Http\Controllers\WidgetController::class, 'getAnnouncements'])->name('announcements');
        Route::get('/new-users', [\App\Http\Controllers\WidgetController::class, 'getNewUsers'])->name('new-users');
        Route::get('/marquee', [\App\Http\Controllers\WidgetController::class, 'getMarquee'])->name('marquee');
        Route::get('/banner', [\App\Http\Controllers\WidgetController::class, 'getBanner'])->name('banner');
        Route::post('/clear-cache', [\App\Http\Controllers\WidgetController::class, 'clearCache'])->name('clear-cache');
    });
    
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
    
    // User notification routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'getUserNotifications'])->name('user.index');
        Route::post('/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('user.read');
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('user.mark-all-read');
        Route::post('/{notification}/dismiss', [\App\Http\Controllers\NotificationController::class, 'dismiss'])->name('user.dismiss');
        Route::get('/stats', [\App\Http\Controllers\NotificationController::class, 'getUserStats'])->name('user.stats');
    });
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
    
    // Content role assignment routes
    Route::post('contents/{content}/roles/assign', [ContentController::class, 'assignRoles'])->name('contents.assign-roles');
    Route::delete('contents/{content}/roles/{role}', [ContentController::class, 'removeRole'])->name('contents.remove-role');
    Route::get('contents/{content}/roles', [ContentController::class, 'getRoles'])->name('contents.get-roles');
    
    // Content expiry management routes
    Route::get('contents/{content}/expiry/status', [ContentController::class, 'getExpiryStatus'])->name('contents.expiry-status');
    Route::post('contents/{content}/expiry/extend', [ContentController::class, 'extendExpiry'])->name('contents.extend-expiry');
    Route::post('contents/{content}/expiry/set', [ContentController::class, 'setExpiry'])->name('contents.set-expiry');
    Route::delete('contents/{content}/expiry', [ContentController::class, 'removeExpiry'])->name('contents.remove-expiry');
    Route::get('contents/expired', [ContentController::class, 'getExpiredContent'])->name('contents.expired');
    Route::get('contents/expiring', [ContentController::class, 'getExpiringContent'])->name('contents.expiring');
    Route::get('contents/expiry/statistics', [ContentController::class, 'getExpiryStatistics'])->name('contents.expiry-statistics');
    Route::post('contents/expiry/bulk-extend', [ContentController::class, 'bulkExtendExpiry'])->name('contents.bulk-extend-expiry');
    
    // Content visit analytics routes
    Route::get('contents/{content}/analytics/visits', [ContentController::class, 'getVisitAnalytics'])->name('contents.visit-analytics');
    Route::get('contents/analytics/popular', [ContentController::class, 'getPopularContent'])->name('contents.popular');
    Route::get('contents/analytics/trending', [ContentController::class, 'getTrendingContent'])->name('contents.trending');
    Route::get('contents/analytics/realtime', [ContentController::class, 'getRealTimeStats'])->name('contents.realtime-stats');
    Route::post('contents/{content}/analytics/reading-progress', [ContentController::class, 'trackReadingProgress'])->name('contents.track-reading-progress');
    Route::get('contents/analytics/summary', [ContentController::class, 'getVisitSummary'])->name('contents.visit-summary');
    Route::get('contents/analytics/export', [ContentController::class, 'exportVisitAnalytics'])->name('contents.export-analytics');
    
    // Popular content analytics dashboard routes
    Route::prefix('analytics/popular-content')->name('analytics.popular-content.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PopularContentAnalyticsController::class, 'index'])->name('index');
        Route::get('/get-popular', [\App\Http\Controllers\Admin\PopularContentAnalyticsController::class, 'getPopularContent'])->name('get-popular');
        Route::get('/get-trending', [\App\Http\Controllers\Admin\PopularContentAnalyticsController::class, 'getTrendingContent'])->name('get-trending');
        Route::get('/performance-comparison', [\App\Http\Controllers\Admin\PopularContentAnalyticsController::class, 'getPerformanceComparison'])->name('performance-comparison');
        Route::get('/engagement-analytics', [\App\Http\Controllers\Admin\PopularContentAnalyticsController::class, 'getEngagementAnalytics'])->name('engagement-analytics');
        Route::get('/export', [\App\Http\Controllers\Admin\PopularContentAnalyticsController::class, 'exportAnalytics'])->name('export');
    });
    
    // Email template management routes
    Route::prefix('email-templates')->name('email-templates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'store'])->name('store');
        Route::get('/{emailTemplate}', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'show'])->name('show');
        Route::get('/{emailTemplate}/edit', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'edit'])->name('edit');
        Route::put('/{emailTemplate}', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'update'])->name('update');
        Route::delete('/{emailTemplate}', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'destroy'])->name('destroy');
        
        // Additional email template routes
        Route::get('/{emailTemplate}/preview', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'preview'])->name('preview');
        Route::post('/{emailTemplate}/test', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'sendTest'])->name('test');
        Route::post('/{emailTemplate}/duplicate', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/{emailTemplate}/activate', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'activate'])->name('activate');
        Route::post('/{emailTemplate}/deactivate', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'deactivate'])->name('deactivate');
        Route::get('/{emailTemplate}/versions', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'versions'])->name('versions');
        Route::post('/{emailTemplate}/versions', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'createVersion'])->name('versions.create');
        Route::post('/{emailTemplate}/versions/{version}/restore', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'restoreVersion'])->name('versions.restore');
        Route::get('/data/table', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'getData'])->name('data');
        Route::get('/variables/list', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'getVariables'])->name('variables');
        Route::post('/export', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'export'])->name('export');
        Route::post('/import', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'import'])->name('import');
    });
    
    // Email queue management routes
    Route::prefix('email-queue')->name('email-queue.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\EmailQueueController::class, 'index'])->name('index');
        Route::get('/data', [\App\Http\Controllers\Admin\EmailQueueController::class, 'data'])->name('data');
        Route::get('/statistics', [\App\Http\Controllers\Admin\EmailQueueController::class, 'statistics'])->name('statistics');
        Route::get('/{emailQueue}', [\App\Http\Controllers\Admin\EmailQueueController::class, 'show'])->name('show');
        Route::post('/retry', [\App\Http\Controllers\Admin\EmailQueueController::class, 'retry'])->name('retry');
        Route::post('/cancel', [\App\Http\Controllers\Admin\EmailQueueController::class, 'cancel'])->name('cancel');
        Route::post('/cleanup', [\App\Http\Controllers\Admin\EmailQueueController::class, 'cleanup'])->name('cleanup');
        Route::post('/send-bulk', [\App\Http\Controllers\Admin\EmailQueueController::class, 'sendBulk'])->name('send-bulk');
    });
    
    // Notification management routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('/data', [\App\Http\Controllers\NotificationController::class, 'data'])->name('data');
        Route::get('/create', [\App\Http\Controllers\NotificationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\NotificationController::class, 'store'])->name('store');
        Route::get('/{notification}', [\App\Http\Controllers\NotificationController::class, 'show'])->name('show');
        Route::get('/{notification}/edit', [\App\Http\Controllers\NotificationController::class, 'edit'])->name('edit');
        Route::put('/{notification}', [\App\Http\Controllers\NotificationController::class, 'update'])->name('update');
        Route::delete('/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
        Route::get('/statistics/data', [\App\Http\Controllers\NotificationController::class, 'statistics'])->name('statistics');
    });
    
    // Upload routes for content editor
    Route::post('upload/image', [\App\Http\Controllers\Admin\UploadController::class, 'uploadImage'])->name('upload.image');
    Route::post('upload/file', [\App\Http\Controllers\Admin\UploadController::class, 'uploadFile'])->name('upload.file');
    Route::delete('upload/file', [\App\Http\Controllers\Admin\UploadController::class, 'deleteFile'])->name('upload.delete');
});

// Email tracking routes (public, no authentication required)
Route::prefix('email')->middleware(['email.tracking'])->group(function () {
    // Email open tracking (1x1 pixel image)
    Route::get('/open/{messageId}', function ($messageId) {
        // Return a 1x1 transparent pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($pixel),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    })->name('email.track.open');
    
    // Email click tracking (redirect)
    Route::get('/click/{messageId}', function ($messageId, Request $request) {
        $url = $request->get('url');
        if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
            return redirect($url);
        }
        return response('Invalid URL', 400);
    })->name('email.track.click');
    
    // Email unsubscribe page
    Route::get('/unsubscribe/{messageId?}', function ($messageId = null, Request $request) {
        return view('emails.unsubscribe', [
            'messageId' => $messageId,
            'email' => $request->get('email')
        ]);
    })->name('email.unsubscribe');
    
    // Process unsubscribe request
    Route::post('/unsubscribe', function (Request $request) {
        // The middleware will handle the tracking
        return view('emails.unsubscribed', [
            'email' => $request->get('email')
        ]);
    })->name('email.unsubscribe.process');
    
    // Webhook endpoints for email service providers
    Route::post('/webhook/sendgrid', function (Request $request) {
        // Middleware handles the processing
        return response('OK', 200);
    })->name('email.webhook.sendgrid');
    
    Route::post('/webhook/mailgun', function (Request $request) {
        // Middleware handles the processing
        return response('OK', 200);
    })->name('email.webhook.mailgun');
    
    Route::post('/webhook/ses', function (Request $request) {
        // Middleware handles the processing
        return response('OK', 200);
    })->name('email.webhook.ses');
    
    Route::post('/webhook/generic', function (Request $request) {
        // Middleware handles the processing
        return response('OK', 200);
    })->name('email.webhook.generic');
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
