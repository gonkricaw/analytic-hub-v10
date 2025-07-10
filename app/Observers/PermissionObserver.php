<?php

namespace App\Observers;

use App\Models\Permission;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Class PermissionObserver
 * 
 * Observes Permission model events and logs activities for security audit trail.
 * Tracks permission creation, updates, and deletion events.
 * 
 * @package App\Observers
 */
class PermissionObserver
{
    /**
     * Handle the Permission "created" event.
     * 
     * @param Permission $permission
     * @return void
     */
    public function created(Permission $permission): void
    {
        $this->logActivity('created', $permission, [
            'permission_id' => $permission->id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'description' => $permission->description,
            'module' => $permission->module,
            'category' => $permission->category,
            'level' => $permission->level,
            'is_system_permission' => $permission->is_system_permission,
            'status' => $permission->status,
        ]);
    }

    /**
     * Handle the Permission "updated" event.
     * 
     * @param Permission $permission
     * @return void
     */
    public function updated(Permission $permission): void
    {
        $changes = $permission->getChanges();
        $original = $permission->getOriginal();
        
        if (!empty($changes)) {
            // Check for critical changes
            $criticalFields = ['name', 'module', 'level', 'is_system_permission', 'status'];
            $hasCriticalChanges = !empty(array_intersect(array_keys($changes), $criticalFields));
            
            $this->logActivity('updated', $permission, [
                'permission_id' => $permission->id,
                'name' => $permission->name,
                'changes' => $changes,
                'original' => array_intersect_key($original, $changes),
                'has_critical_changes' => $hasCriticalChanges,
                'critical_fields_changed' => array_intersect(array_keys($changes), $criticalFields),
            ]);
        }
    }

    /**
     * Handle the Permission "deleted" event.
     * 
     * @param Permission $permission
     * @return void
     */
    public function deleted(Permission $permission): void
    {
        $this->logActivity('deleted', $permission, [
            'permission_id' => $permission->id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'module' => $permission->module,
            'level' => $permission->level,
            'is_system_permission' => $permission->is_system_permission,
            'role_count' => $permission->roles()->count(),
            'user_count' => $permission->users()->count(),
            'deleted_at' => $permission->deleted_at,
        ]);
    }

    /**
     * Handle the Permission "restored" event.
     * 
     * @param Permission $permission
     * @return void
     */
    public function restored(Permission $permission): void
    {
        $this->logActivity('restored', $permission, [
            'permission_id' => $permission->id,
            'name' => $permission->name,
            'restored_at' => now(),
        ]);
    }

    /**
     * Log activity to the activity log.
     * 
     * @param string $action
     * @param Permission $permission
     * @param array $properties
     * @return void
     */
    private function logActivity(string $action, Permission $permission, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => Permission::class,
                'subject_id' => $permission->id,
                'action' => $action,
                'description' => "Permission {$action}: {$permission->name}",
                'properties' => $properties,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
                'method' => Request::method(),
                'is_sensitive' => true, // All permission changes are sensitive
                'severity' => $this->getSeverity($action),
                'category' => 'security',
                'tags' => ['permission', 'rbac', 'security', $action],
            ]);
        } catch (\Exception $e) {
            // Log the error but don't break the application
            \Log::error('Failed to log permission activity', [
                'action' => $action,
                'permission_id' => $permission->id,
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