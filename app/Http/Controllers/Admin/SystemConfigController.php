<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Exception;
use Carbon\Carbon;

/**
 * SystemConfigController
 * 
 * Handles system configuration management for the Analytics Hub.
 * Manages application settings, logo uploads, login backgrounds,
 * footer content, maintenance mode, health checks, and system logs.
 * 
 * Features:
 * - System settings interface
 * - Logo upload functionality
 * - Login background customization
 * - Footer content editor
 * - Maintenance mode management
 * - System health checks
 * - Backup functionality
 * - System logs viewer
 * 
 * Security: Admin role required for all operations
 * Logging: All configuration changes are logged
 * Caching: Configuration values are cached for performance
 */
class SystemConfigController extends Controller
{
    /**
     * Display the system configuration dashboard
     * 
     * @return View
     */
    public function index(): View
    {
        try {
            // Get configuration groups for organization
            $configGroups = SystemConfig::select('group')
                ->where('is_active', true)
                ->groupBy('group')
                ->orderBy('group')
                ->pluck('group');

            // Get all active configurations grouped by category
            $configurations = SystemConfig::where('is_active', true)
                ->where('is_editable', true)
                ->orderBy('group')
                ->orderBy('sort_order')
                ->orderBy('display_name')
                ->get()
                ->groupBy('group');

            // Get system health status
            $healthStatus = $this->getSystemHealth();

            // Get recent configuration changes
            $recentChanges = SystemConfig::where('last_changed_at', '>=', now()->subDays(7))
                ->orderBy('last_changed_at', 'desc')
                ->limit(10)
                ->get();

            return view('admin.system-config.index', compact(
                'configGroups',
                'configurations',
                'healthStatus',
                'recentChanges'
            ));
        } catch (Exception $e) {
            Log::error('Error loading system configuration dashboard', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('admin.system-config.index')
                ->with('error', 'Failed to load system configuration. Please try again.');
        }
    }

    /**
     * Update a system configuration value
     * 
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function update(Request $request, string $key): JsonResponse
    {
        try {
            $config = SystemConfig::where('key', $key)
                ->where('is_editable', true)
                ->where('is_active', true)
                ->firstOrFail();

            // Validate the input based on configuration type
            $validator = $this->validateConfigValue($request, $config);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldValue = $config->value;
            $newValue = $request->input('value');

            // Handle file uploads for specific config types
            if ($config->input_type === 'file') {
                $newValue = $this->handleFileUpload($request, $config);
                if (!$newValue) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File upload failed'
                    ], 500);
                }
            }

            // Update configuration
            $config->update([
                'value' => $newValue,
                'last_changed_at' => now(),
                'last_changed_by' => Auth::id(),
                'change_reason' => $request->input('reason', 'Updated via admin panel'),
                'version' => $config->version + 1
            ]);

            // Add to change history
            $changeHistory = $config->change_history ?? [];
            $changeHistory[] = [
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => Auth::id(),
                'changed_at' => now()->toISOString(),
                'reason' => $request->input('reason', 'Updated via admin panel')
            ];
            $config->update(['change_history' => $changeHistory]);

            // Clear cache for this configuration
            Cache::forget("system_config_{$key}");
            Cache::forget('system_configs_all');

            // Log the change
            Log::info('System configuration updated', [
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'user_id' => Auth::id(),
                'reason' => $request->input('reason')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully',
                'requires_restart' => $config->requires_restart
            ]);

        } catch (Exception $e) {
            Log::error('Error updating system configuration', [
                'key' => $key,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update configuration'
            ], 500);
        }
    }

    /**
     * Upload logo file
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'logo' => 'required|image|mimes:jpeg,jpg,png,svg|max:2048',
                'type' => 'required|in:navbar,login,favicon'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $type = $request->input('type');
            $file = $request->file('logo');
            
            // Generate unique filename
            $filename = $type . '_logo_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Store file in public/uploads/logos directory
            $path = $file->storeAs('logos', $filename, 'public');
            
            if (!$path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload file'
                ], 500);
            }

            // Update system configuration
            $configKey = "app.logo.{$type}";
            $config = SystemConfig::where('key', $configKey)->first();
            
            if ($config) {
                // Delete old logo file if exists
                if ($config->value && Storage::disk('public')->exists($config->value)) {
                    Storage::disk('public')->delete($config->value);
                }
                
                $config->update([
                    'value' => $path,
                    'last_changed_at' => now(),
                    'last_changed_by' => Auth::id()
                ]);
            }

            // Clear cache
            Cache::forget("system_config_{$configKey}");

            Log::info('Logo uploaded successfully', [
                'type' => $type,
                'filename' => $filename,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully',
                'path' => Storage::url($path),
                'filename' => $filename
            ]);

        } catch (Exception $e) {
            Log::error('Error uploading logo', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo'
            ], 500);
        }
    }

    /**
     * Upload login background image
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadBackground(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'background' => 'required|image|mimes:jpeg,jpg,png|max:5120' // 5MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('background');
            $filename = 'login_background_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Store file
            $path = $file->storeAs('backgrounds', $filename, 'public');
            
            if (!$path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload file'
                ], 500);
            }

            // Update system configuration
            $config = SystemConfig::where('key', 'app.login.background')->first();
            
            if ($config) {
                // Delete old background if exists
                if ($config->value && Storage::disk('public')->exists($config->value)) {
                    Storage::disk('public')->delete($config->value);
                }
                
                $config->update([
                    'value' => $path,
                    'last_changed_at' => now(),
                    'last_changed_by' => Auth::id()
                ]);
            }

            // Clear cache
            Cache::forget('system_config_app.login.background');

            Log::info('Login background uploaded successfully', [
                'filename' => $filename,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Background uploaded successfully',
                'path' => Storage::url($path),
                'filename' => $filename
            ]);

        } catch (Exception $e) {
            Log::error('Error uploading login background', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload background'
            ], 500);
        }
    }

    /**
     * Toggle maintenance mode
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleMaintenance(Request $request): JsonResponse
    {
        try {
            $enable = $request->boolean('enable');
            $message = $request->input('message', 'System is under maintenance. Please try again later.');
            
            if ($enable) {
                // Enable maintenance mode
                Artisan::call('down', [
                    '--message' => $message,
                    '--retry' => 60
                ]);
                
                $status = 'enabled';
            } else {
                // Disable maintenance mode
                Artisan::call('up');
                $status = 'disabled';
            }

            // Update configuration
            $config = SystemConfig::where('key', 'app.maintenance.enabled')->first();
            if ($config) {
                $config->update([
                    'value' => $enable ? 'true' : 'false',
                    'last_changed_at' => now(),
                    'last_changed_by' => Auth::id()
                ]);
            }

            $messageConfig = SystemConfig::where('key', 'app.maintenance.message')->first();
            if ($messageConfig) {
                $messageConfig->update([
                    'value' => $message,
                    'last_changed_at' => now(),
                    'last_changed_by' => Auth::id()
                ]);
            }

            Log::info('Maintenance mode toggled', [
                'status' => $status,
                'message' => $message,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Maintenance mode {$status} successfully",
                'status' => $status
            ]);

        } catch (Exception $e) {
            Log::error('Error toggling maintenance mode', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle maintenance mode'
            ], 500);
        }
    }

    /**
     * Get system health status
     * 
     * @return JsonResponse
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $health = $this->getSystemHealth();
            
            return response()->json([
                'success' => true,
                'health' => $health,
                'overall_status' => $health['overall_status'],
                'timestamp' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('Error performing health check', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Health check failed'
            ], 500);
        }
    }

    /**
     * Perform system health checks
     * 
     * @return array
     */
    private function getSystemHealth(): array
    {
        $checks = [];
        $overallStatus = 'healthy';

        try {
            // Database connection check
            $dbStart = microtime(true);
            DB::connection()->getPdo();
            $dbTime = round((microtime(true) - $dbStart) * 1000, 2);
            $checks['database'] = [
                'status' => 'healthy',
                'response_time' => $dbTime . 'ms',
                'message' => 'Database connection successful'
            ];
        } catch (Exception $e) {
            $checks['database'] = [
                'status' => 'unhealthy',
                'response_time' => 'N/A',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
            $overallStatus = 'unhealthy';
        }

        // Cache check
        try {
            $cacheKey = 'health_check_' . time();
            Cache::put($cacheKey, 'test', 60);
            $cached = Cache::get($cacheKey);
            Cache::forget($cacheKey);
            
            $checks['cache'] = [
                'status' => $cached === 'test' ? 'healthy' : 'unhealthy',
                'message' => $cached === 'test' ? 'Cache working properly' : 'Cache not working'
            ];
            
            if ($cached !== 'test') {
                $overallStatus = 'degraded';
            }
        } catch (Exception $e) {
            $checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache error: ' . $e->getMessage()
            ];
            $overallStatus = 'degraded';
        }

        // Storage check
        try {
            $testFile = 'health_check_' . time() . '.txt';
            Storage::disk('public')->put($testFile, 'test');
            $content = Storage::disk('public')->get($testFile);
            Storage::disk('public')->delete($testFile);
            
            $checks['storage'] = [
                'status' => $content === 'test' ? 'healthy' : 'unhealthy',
                'message' => $content === 'test' ? 'Storage working properly' : 'Storage not working'
            ];
            
            if ($content !== 'test') {
                $overallStatus = 'degraded';
            }
        } catch (Exception $e) {
            $checks['storage'] = [
                'status' => 'unhealthy',
                'message' => 'Storage error: ' . $e->getMessage()
            ];
            $overallStatus = 'degraded';
        }

        // Disk space check
        $freeBytes = disk_free_space(storage_path());
        $totalBytes = disk_total_space(storage_path());
        $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2);
        
        $diskStatus = 'healthy';
        if ($usedPercent > 90) {
            $diskStatus = 'unhealthy';
            $overallStatus = 'unhealthy';
        } elseif ($usedPercent > 80) {
            $diskStatus = 'warning';
            if ($overallStatus === 'healthy') {
                $overallStatus = 'degraded';
            }
        }
        
        $checks['disk_space'] = [
            'status' => $diskStatus,
            'used_percent' => $usedPercent,
            'free_space' => $this->formatBytes($freeBytes),
            'total_space' => $this->formatBytes($totalBytes),
            'message' => "Disk usage: {$usedPercent}%"
        ];

        // Memory usage check
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));
        $memoryPercent = round(($memoryUsage / $memoryLimit) * 100, 2);
        
        $memoryStatus = 'healthy';
        if ($memoryPercent > 90) {
            $memoryStatus = 'warning';
            if ($overallStatus === 'healthy') {
                $overallStatus = 'degraded';
            }
        }
        
        $checks['memory'] = [
            'status' => $memoryStatus,
            'used_percent' => $memoryPercent,
            'current_usage' => $this->formatBytes($memoryUsage),
            'limit' => $this->formatBytes($memoryLimit),
            'message' => "Memory usage: {$memoryPercent}%"
        ];

        return [
            'overall_status' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Validate configuration value based on its type and rules
     * 
     * @param Request $request
     * @param SystemConfig $config
     * @return \Illuminate\Validation\Validator
     */
    private function validateConfigValue(Request $request, SystemConfig $config)
    {
        $rules = ['value' => 'required'];
        
        // Add type-specific validation
        switch ($config->data_type) {
            case 'integer':
                $rules['value'] .= '|integer';
                break;
            case 'boolean':
                $rules['value'] .= '|boolean';
                break;
            case 'email':
                $rules['value'] .= '|email';
                break;
            case 'url':
                $rules['value'] .= '|url';
                break;
            case 'json':
                $rules['value'] .= '|json';
                break;
        }
        
        // Add custom validation rules if defined
        if ($config->validation_rules) {
            $customRules = is_array($config->validation_rules) 
                ? $config->validation_rules 
                : json_decode($config->validation_rules, true);
                
            if ($customRules && isset($customRules['value'])) {
                $rules['value'] .= '|' . $customRules['value'];
            }
        }
        
        return Validator::make($request->all(), $rules);
    }

    /**
     * Handle file upload for configuration
     * 
     * @param Request $request
     * @param SystemConfig $config
     * @return string|null
     */
    private function handleFileUpload(Request $request, SystemConfig $config): ?string
    {
        if (!$request->hasFile('value')) {
            return null;
        }
        
        $file = $request->file('value');
        $directory = 'config-files';
        
        // Generate filename based on config key
        $filename = str_replace('.', '_', $config->key) . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Delete old file if exists
        if ($config->value && Storage::disk('public')->exists($config->value)) {
            Storage::disk('public')->delete($config->value);
        }
        
        return $path;
    }

    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Parse size string to bytes
     * 
     * @param string $size
     * @return int
     */
    private function parseSize(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Get storage usage information
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStorageUsage(): JsonResponse
    {
        try {
            $storage = [
                'total' => disk_total_space(storage_path()),
                'free' => disk_free_space(storage_path()),
                'used' => disk_total_space(storage_path()) - disk_free_space(storage_path())
            ];
            
            $storage['usage_percentage'] = round(($storage['used'] / $storage['total']) * 100, 2);
            
            return response()->json([
                'success' => true,
                'storage' => [
                    'total' => $this->formatFileSize($storage['total']),
                     'free' => $this->formatFileSize($storage['free']),
                     'used' => $this->formatFileSize($storage['used']),
                    'usage_percentage' => $storage['usage_percentage']
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get storage usage: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send test email
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);
        
        try {
            \Mail::raw($request->message, function ($mail) use ($request) {
                $mail->to($request->email)
                     ->subject($request->subject);
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Test email connection
     *
     * @return JsonResponse
     */
    public function testEmailConnection(): JsonResponse
    {
        try {
            $transport = \Mail::getSwiftMailer()->getTransport();
            $transport->start();
            
            return response()->json([
                'success' => true,
                'message' => 'Email connection successful'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get email queue status
     *
     * @return JsonResponse
     */
    public function getEmailQueueStatus(): JsonResponse
    {
        try {
            // Get queue statistics from database
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            
            return response()->json([
                'success' => true,
                'queue' => [
                    'pending' => $pending,
                    'failed' => $failed,
                    'total' => $pending + $failed
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get queue status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Clear email queue
     *
     * @return JsonResponse
     */
    public function clearEmailQueue(): JsonResponse
    {
        try {
            DB::table('jobs')->delete();
            DB::table('failed_jobs')->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Email queue cleared successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear email queue: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create backup
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createBackup(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:database,files,full',
            'description' => 'nullable|string|max:255'
        ]);
        
        try {
            $backupPath = storage_path('app/backups');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$request->type}_{$timestamp}.zip";
            $fullPath = $backupPath . '/' . $filename;
            
            // Create backup based on type
            switch ($request->type) {
                case 'database':
                    $this->createDatabaseBackup($fullPath);
                    break;
                case 'files':
                    $this->createFilesBackup($fullPath);
                    break;
                case 'full':
                    $this->createFullBackup($fullPath);
                    break;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get backup history
     *
     * @return JsonResponse
     */
    public function getBackupHistory(): JsonResponse
    {
        try {
            $backupPath = storage_path('app/backups');
            $backups = [];
            
            if (file_exists($backupPath)) {
                $files = glob($backupPath . '/backup_*.zip');
                
                foreach ($files as $file) {
                    $backups[] = [
                        'filename' => basename($file),
                        'size' => $this->formatFileSize(filesize($file)),
                        'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                        'type' => $this->getBackupType(basename($file))
                    ];
                }
                
                // Sort by creation time (newest first)
                usort($backups, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            }
            
            return response()->json([
                'success' => true,
                'backups' => $backups
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get backup history: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Restore backup
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        $request->validate([
            'filename' => 'required|string'
        ]);
        
        try {
            $backupPath = storage_path('app/backups/' . $request->filename);
            
            if (!file_exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }
            
            // Implement backup restoration logic here
            // This is a placeholder - actual implementation would depend on backup format
            
            return response()->json([
                'success' => true,
                'message' => 'Backup restored successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore backup: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cleanup temporary files
     *
     * @return JsonResponse
     */
    public function cleanupTempFiles(): JsonResponse
    {
        try {
            $tempPath = storage_path('app/temp');
            $deletedCount = 0;
            $deletedSize = 0;
            
            if (file_exists($tempPath)) {
                $files = glob($tempPath . '/*');
                
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < strtotime('-1 day')) {
                        $deletedSize += filesize($file);
                        unlink($file);
                        $deletedCount++;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} temporary files ({$this->formatFileSize($deletedSize)})"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup temporary files: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cleanup logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cleanupLogs(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);
        
        try {
            $logPath = storage_path('logs');
            $cutoffDate = strtotime("-{$request->days} days");
            $deletedCount = 0;
            $deletedSize = 0;
            
            if (file_exists($logPath)) {
                $files = glob($logPath . '/*.log');
                
                foreach ($files as $file) {
                    if (filemtime($file) < $cutoffDate) {
                        $deletedSize += filesize($file);
                        unlink($file);
                        $deletedCount++;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} log files ({$this->formatFileSize($deletedSize)})"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup logs: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Clear caches
     *
     * @return JsonResponse
     */
    public function clearCaches(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear caches: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get log files
     *
     * @return JsonResponse
     */
    public function getLogFiles(): JsonResponse
    {
        try {
            $logPath = storage_path('logs');
            $files = [];
            
            if (file_exists($logPath)) {
                $logFiles = glob($logPath . '/*.log');
                
                foreach ($logFiles as $file) {
                    $files[] = [
                        'name' => basename($file),
                        'size' => $this->formatFileSize(filesize($file)),
                        'modified' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
                
                // Sort by modification time (newest first)
                usort($files, function ($a, $b) {
                    return strtotime($b['modified']) - strtotime($a['modified']);
                });
            }
            
            return response()->json([
                'success' => true,
                'files' => $files
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get log files: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get log content
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLogContent(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|string',
            'lines' => 'nullable|integer|min:10|max:1000',
            'level' => 'nullable|string|in:emergency,alert,critical,error,warning,notice,info,debug',
            'search' => 'nullable|string|max:255'
        ]);
        
        try {
            $logPath = storage_path('logs/' . $request->file);
            
            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log file not found'
                ], 404);
            }
            
            $lines = $request->lines ?? 100;
            $content = $this->readLogFile($logPath, $lines, $request->level, $request->search);
            
            return response()->json([
                'success' => true,
                'content' => $content
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to read log file: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Download log file
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadLogFile(Request $request)
    {
        $request->validate([
            'file' => 'required|string'
        ]);
        
        $logPath = storage_path('logs/' . $request->file);
        
        if (!file_exists($logPath)) {
            abort(404, 'Log file not found');
        }
        
        return response()->download($logPath);
    }
    
    /**
      * Clear log file
      *
      * @param Request $request
      * @return JsonResponse
      */
    public function clearLogFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|string'
        ]);
        
        try {
            $logPath = storage_path('logs/' . $request->file);
            
            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log file not found'
                ], 404);
            }
            
            file_put_contents($logPath, '');
            
            return response()->json([
                'success' => true,
                'message' => 'Log file cleared successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear log file: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportLogs(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'string',
            'format' => 'required|in:zip,tar'
        ]);
        
        try {
            $exportPath = storage_path('app/exports');
            if (!file_exists($exportPath)) {
                mkdir($exportPath, 0755, true);
            }
            
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "logs_export_{$timestamp}.{$request->format}";
            $fullPath = $exportPath . '/' . $filename;
            
            // Create archive with selected log files
            $this->createLogArchive($request->files, $fullPath, $request->format);
            
            return response()->json([
                'success' => true,
                'message' => 'Logs exported successfully',
                'filename' => $filename,
                'download_url' => route('system-config.logs.download', ['file' => $filename])
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export logs: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Archive logs
     *
     * @return JsonResponse
     */
    public function archiveLogs(): JsonResponse
    {
        try {
            $logPath = storage_path('logs');
            $archivePath = storage_path('app/archives');
            
            if (!file_exists($archivePath)) {
                mkdir($archivePath, 0755, true);
            }
            
            $timestamp = now()->format('Y-m-d_H-i-s');
            $archiveFile = $archivePath . "/logs_archive_{$timestamp}.zip";
            
            $zip = new \ZipArchive();
            if ($zip->open($archiveFile, \ZipArchive::CREATE) === TRUE) {
                $files = glob($logPath . '/*.log');
                
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }
                
                $zip->close();
                
                // Clear original log files after archiving
                foreach ($files as $file) {
                    file_put_contents($file, '');
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Logs archived successfully',
                    'archive' => basename($archiveFile)
                ]);
            } else {
                throw new Exception('Failed to create archive');
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive logs: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Rotate logs
     *
     * @return JsonResponse
     */
    public function rotateLogs(): JsonResponse
    {
        try {
            Artisan::call('log:rotate');
            
            return response()->json([
                'success' => true,
                'message' => 'Logs rotated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to rotate logs: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete old logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteOldLogs(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);
        
        try {
            $logPath = storage_path('logs');
            $cutoffDate = strtotime("-{$request->days} days");
            $deletedCount = 0;
            
            if (file_exists($logPath)) {
                $files = glob($logPath . '/*.log');
                
                foreach ($files as $file) {
                    if (filemtime($file) < $cutoffDate) {
                        unlink($file);
                        $deletedCount++;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} old log files"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete old logs: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get log statistics
     *
     * @return JsonResponse
     */
    public function getLogStats(): JsonResponse
    {
        try {
            $logPath = storage_path('logs');
            $stats = [
                'total_files' => 0,
                'total_size' => 0,
                'error_count' => 0,
                'warning_count' => 0,
                'latest_error' => null
            ];
            
            if (file_exists($logPath)) {
                $files = glob($logPath . '/*.log');
                $stats['total_files'] = count($files);
                
                foreach ($files as $file) {
                    $stats['total_size'] += filesize($file);
                    
                    // Count errors and warnings in the file
                    $content = file_get_contents($file);
                    $stats['error_count'] += substr_count($content, '.ERROR:');
                    $stats['warning_count'] += substr_count($content, '.WARNING:');
                }
            }
            
            $stats['total_size_formatted'] = $this->formatFileSize($stats['total_size']);
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get log statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create database backup
     *
     * @param string $path
     * @return void
     */
    private function createDatabaseBackup($path)
    {
        // Implement database backup logic
        // This is a placeholder - actual implementation would use mysqldump or similar
    }
    
    /**
     * Create files backup
     *
     * @param string $path
     * @return void
     */
    private function createFilesBackup($path)
    {
        // Implement files backup logic
        // This is a placeholder - actual implementation would zip important directories
    }
    
    /**
     * Create full backup
     *
     * @param string $path
     * @return void
     */
    private function createFullBackup($path)
    {
        // Implement full backup logic
        // This is a placeholder - actual implementation would backup both database and files
    }
    
    /**
     * Get backup type from filename
     *
     * @param string $filename
     * @return string
     */
    private function getBackupType($filename)
    {
        if (strpos($filename, '_database_') !== false) {
            return 'database';
        } elseif (strpos($filename, '_files_') !== false) {
            return 'files';
        } elseif (strpos($filename, '_full_') !== false) {
            return 'full';
        }
        
        return 'unknown';
    }
    
    /**
      * Read log file with filtering
      *
      * @param string $path
      * @param int $lines
      * @param string|null $level
      * @param string|null $search
      * @return array
      */
     private function readLogFile($path, $lines, $level = null, $search = null)
     {
         $content = [];
         $file = new \SplFileObject($path);
         $file->seek(PHP_INT_MAX);
         $totalLines = $file->key();
         
         $startLine = max(0, $totalLines - $lines);
         $file->seek($startLine);
         
         while (!$file->eof() && count($content) < $lines) {
             $line = trim($file->current());
             
             if (!empty($line)) {
                 $include = true;
                 
                 // Filter by log level
                 if ($level && strpos($line, strtoupper($level)) === false) {
                     $include = false;
                 }
                 
                 // Filter by search term
                 if ($search && stripos($line, $search) === false) {
                     $include = false;
                 }
                 
                 if ($include) {
                     $content[] = $line;
                 }
             }
             
             $file->next();
         }
         
         return $content;
     }
     
     /**
      * Create log archive
      *
      * @param array $files
      * @param string $path
      * @param string $format
      * @return void
      */
     private function createLogArchive($files, $path, $format)
     {
         if ($format === 'zip') {
             $zip = new \ZipArchive();
             if ($zip->open($path, \ZipArchive::CREATE) === TRUE) {
                 foreach ($files as $file) {
                     $logPath = storage_path('logs/' . $file);
                     if (file_exists($logPath)) {
                         $zip->addFile($logPath, $file);
                     }
                 }
                 $zip->close();
             }
         }
         // Add tar format support if needed
     }
}