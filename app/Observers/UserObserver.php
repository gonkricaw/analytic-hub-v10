<?php

namespace App\Observers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Class UserObserver
 * 
 * Observes User model events and logs activities for audit trail.
 * Automatically tracks user creation, updates, and deletion events.
 * 
 * @package App\Observers
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     * 
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        $this->logActivity('created', $user, [
            'user_id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'status' => $user->status,
        ]);
    }

    /**
     * Handle the User "updated" event.
     * 
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        $original = $user->getOriginal();
        
        // Remove sensitive data from logging
        unset($changes['password'], $changes['remember_token']);
        unset($original['password'], $original['remember_token']);
        
        if (!empty($changes)) {
            $this->logActivity('updated', $user, [
                'user_id' => $user->id,
                'changes' => $changes,
                'original' => array_intersect_key($original, $changes),
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     * 
     * @param User $user
     * @return void
     */
    public function deleted(User $user): void
    {
        $this->logActivity('deleted', $user, [
            'user_id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'deleted_at' => $user->deleted_at,
        ]);
    }

    /**
     * Handle the User "restored" event.
     * 
     * @param User $user
     * @return void
     */
    public function restored(User $user): void
    {
        $this->logActivity('restored', $user, [
            'user_id' => $user->id,
            'email' => $user->email,
            'restored_at' => now(),
        ]);
    }

    /**
     * Log activity to the activity log.
     * 
     * @param string $action
     * @param User $user
     * @param array $properties
     * @return void
     */
    private function logActivity(string $action, User $user, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'action' => $action,
                'description' => "User {$action}: {$user->email}",
                'properties' => $properties,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
                'method' => Request::method(),
                'is_sensitive' => in_array($action, ['created', 'deleted', 'restored']),
                'severity' => $this->getSeverity($action),
                'category' => 'user_management',
                'tags' => ['user', 'authentication', $action],
            ]);
        } catch (\Exception $e) {
            // Log the error but don't break the application
            \Log::error('Failed to log user activity', [
                'action' => $action,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get severity level for the action.
     * 
     * @param string $action
     * @return string
     */
    private function getSeverity(string $action): string
    {
        return match ($action) {
            'created', 'deleted' => 'high',
            'restored' => 'medium',
            'updated' => 'low',
            default => 'info',
        };
    }
}