<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Private user channel for personal notifications
 * 
 * This channel is used to send real-time notifications to specific users.
 * Only the authenticated user can listen to their own private channel.
 * 
 * Channel format: user.{userId}
 * 
 * @param User $user The authenticated user
 * @param string $userId The user ID from the channel name
 * @return bool True if the user can access this channel
 */
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    // User can only listen to their own private channel
    // This ensures notification privacy and security
    return (int) $user->id === (int) $userId;
});

/**
 * Admin broadcast channel for system-wide notifications
 * 
 * This channel is used for broadcasting admin notifications
 * and system alerts to users with admin privileges.
 * 
 * Only users with admin role can listen to this channel.
 * 
 * @param User $user The authenticated user
 * @return bool True if the user has admin privileges
 */
Broadcast::channel('admin', function (User $user) {
    // Check if user has admin role
    return $user->hasRole('admin');
});

/**
 * Role-based channels for targeted notifications
 * 
 * These channels allow broadcasting notifications to users
 * with specific roles (e.g., managers, editors, viewers).
 * 
 * Channel format: role.{roleName}
 * 
 * @param User $user The authenticated user
 * @param string $roleName The role name from the channel
 * @return bool True if the user has the specified role
 */
Broadcast::channel('role.{roleName}', function (User $user, string $roleName) {
    // Check if user has the specified role
    return $user->hasRole($roleName);
});

/**
 * System status channel for application-wide updates
 * 
 * This channel broadcasts system status updates, maintenance
 * notifications, and other application-wide messages.
 * 
 * All authenticated users can listen to this channel.
 * 
 * @param User $user The authenticated user
 * @return bool Always true for authenticated users
 */
Broadcast::channel('system', function (User $user) {
    // All authenticated users can listen to system updates
    return true;
});