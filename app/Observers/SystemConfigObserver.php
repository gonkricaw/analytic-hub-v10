<?php

namespace App\Observers;

use App\Models\SystemConfig;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Class SystemConfigObserver
 * 
 * Observes SystemConfig model events and logs activities for audit trail.
 * Tracks system configuration changes which are critical for security and operations.
 * 
 * @package App\Observers
 */
class SystemConfigObserver
{
    /**
     * Handle the SystemConfig "created" event.
     * 
     * @param SystemConfig $config
     * @return void
     */
    public function created(SystemConfig $config): void
    {
        $this->logActivity('created', $config, [
            'config_id' => $config->id,
            'key' => $config->key,
            'category' => $config->category,
            'type' => $config->type,
            'is_encrypted' => $config->is_encrypted,
            'is_public' => $config->is_public,
            'environment' => $config->environment,
            'status' => $config->status,
            // Don't log the actual value for security
            'has_value' => !empty($config->value),
        ]);
    }

    /**
     * Handle the SystemConfig "updated" event.
     * 
     * @param SystemConfig $config
     * @return void
     */
    public function updated(SystemConfig $config): void
    {
        $changes = $config->getChanges();
        $original = $config->getOriginal();
        
        if (!empty($changes)) {
            // Check for critical changes
            $criticalFields = ['key', 'value', 'type', 'is_encrypted', 'status', 'environment'];
            $hasCriticalChanges = !empty(array_intersect(array_keys($changes), $criticalFields));
            
            // Prepare safe changes (exclude sensitive values)
            $safeChanges = $changes;
            $safeOriginal = $original;
            
            // Remove sensitive values from logging
            if (isset($safeChanges['value'])) {
                $safeChanges['value'] = $config->is_encrypted ? '[ENCRYPTED]' : '[REDACTED]';
            }
            if (isset($safeOriginal['value'])) {
                $safeOriginal['value'] = $config->is_encrypted ? '[ENCRYPTED]' : '[REDACTED]';
            }
            
            $this->logActivity('updated', $config, [
                'config_id' => $config->id,
                'key' => $config->key,
                'category' => $config->category,
                'changes' => array_intersect_key($safeChanges, $changes),
                'original' => array_intersect_key($safeOriginal, $changes),
                'has_critical_changes' => $hasCriticalChanges,
                'critical_fields_changed' => array_intersect(array_keys($changes), $criticalFields),
                'value_changed' => isset($changes['value']),
            ]);
        }
    }

    /**
     * Handle the SystemConfig "deleted" event.
     * 
     * @param SystemConfig $config
     * @return void
     */
    public function deleted(SystemConfig $config): void
    {
        $this->logActivity('deleted', $config, [
            'config_id' => $config->id,
            'key' => $config->key,
            'category' => $config->category,
            'type' => $config->type,
            'is_encrypted' => $config->is_encrypted,
            'environment' => $config->environment,
            'deleted_at' => $config->deleted_at,
        ]);
    }

    /**
     * Handle the SystemConfig "restored" event.
     * 
     * @param SystemConfig $config
     * @return void
     */
    public function restored(SystemConfig $config): void
    {
        $this->logActivity('restored', $config, [
            'config_id' => $config->id,
            'key' => $config->key,
            'restored_at' => now(),
        ]);
    }

    /**
     * Log activity to the activity log.
     * 
     * @param string $action
     * @param SystemConfig $config
     * @param array $properties
     * @return void
     */
    private function logActivity(string $action, SystemConfig $config, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => SystemConfig::class,
                'subject_id' => $config->id,
                'action' => $action,
                'description' => "System config {$action}: {$config->key}",
                'properties' => $properties,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
                'method' => Request::method(),
                'is_sensitive' => true, // All system config changes are sensitive
                'severity' => $this->getSeverity($action, $config),
                'category' => 'system_configuration',
                'tags' => ['system_config', $config->category, $action],
            ]);
        } catch (\Exception $e) {
            // Log the error but don't break the application
            \Log::error('Failed to log system config activity', [
                'action' => $action,
                'config_id' => $config->id,
                'config_key' => $config->key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get severity level for the action.
     * 
     * @param string $action
     * @param SystemConfig $config
     * @return string
     */
    private function getSeverity(string $action, SystemConfig $config): string
    {
        // Security-related configs get higher severity
        $securityCategories = ['security', 'authentication', 'encryption', 'api'];
        $isSecurityConfig = in_array($config->category, $securityCategories);
        
        return match ($action) {
            'deleted' => $isSecurityConfig ? 'critical' : 'high',
            'created', 'restored' => $isSecurityConfig ? 'high' : 'medium',
            'updated' => $isSecurityConfig ? 'medium' : 'low',
            default => 'info',
        };
    }
}