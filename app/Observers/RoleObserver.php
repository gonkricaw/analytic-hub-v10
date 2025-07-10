<?php

namespace App\Observers;

use App\Models\Role;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Class RoleObserver
 * 
 * Observes Role model events and logs activities for security audit trail.
 * Tracks role creation, updates, permission changes, and deletion events.
 * 
 * @package App\Observers
 */
class RoleObserver
{
    /**
     * Handle the Role "created" event.
     * 
     * @param Role $role
     * @return void
     */
    public function created(Role $role): void
    {
        $this->logActivity('created', $role, [
            'role_id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
            'level' => $role->level,
            'is_system_role' => $role->is_system_role,
            'is_default' => $role->is_default,
            'status' => $role->status,
        ]);
    }

    /**
     * Handle the Role "updated" event.
     * 
     * @param Role $role
     * @return void
     */
    public function updated(Role $role): void
    {
        $changes = $role->getChanges();
        $original = $role->getOriginal();
        
        if (!empty($changes)) {
            // Check for critical changes
            $criticalFields = ['name', 'level', 'is_system_role', 'status'];
            $hasCriticalChanges = !empty(array_intersect(array_keys($changes), $criticalFields));
            
            $this->logActivity('updated', $role, [
                'role_id' => $role->id,
                'name' => $role->name,
                'changes' => $changes,
                'original' => array_intersect_key($original, $changes),
                'has_critical_changes' => $hasCriticalChanges,
                'critical_fields_changed' => array_intersect(array_keys($changes), $criticalFields),
            ]);
        }
    }

    /**
     * Handle the Role "deleted" event.
     * 
     * @param Role $role
     * @return void
     */
    public function deleted(Role $role): void
    {
        $this->logActivity('deleted', $role, [
            'role_id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
            'level' => $role->level,
            'is_system_role' => $role->is_system_role,
            'user_count' => $role->users()->count(),
            'deleted_at' => $role->deleted_at,
        ]);
    }

    /**
     * Handle the Role "restored" event.
     * 
     * @param Role $role
     * @return void
     */
    public function restored(Role $role): void
    {
        $this->logActivity('restored', $role, [
            'role_id' => $role->id,
            'name' => $role->name,
            'restored_at' => now(),
        ]);
    }

    /**
     * Log activity to the activity log.
     * 
     * @param string $action
     * @param Role $role
     * @param array $properties
     * @return void
     */
    private function logActivity(string $action, Role $role, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => Role::class,
                'subject_id' => $role->id,
                'action' => $action,
                'description' => "Role {$action}: {$role->name}",
                'properties' => $properties,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
                'method' => Request::method(),
                'is_sensitive' => true, // All role changes are sensitive
                'severity' => $this->getSeverity($action),
                'category' => 'security',
                'tags' => ['role', 'rbac', 'security', $action],
            ]);
        } catch (\Exception $e) {
            // Log the error but don't break the application
            \Log::error('Failed to log role activity', [
                'action' => $action,
                'role_id' => $role->id,
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
            'deleted' => 'critical',
            'created', 'restored' => 'high',
            'updated' => 'medium',
            default => 'info',
        };
    }
}