<form id="maintenanceSettingsForm">
    @csrf
    <div class="row">
        <!-- Maintenance Mode -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-tools text-warning"></i> Maintenance Mode
                </h5>
                
                <!-- Maintenance Status -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white mb-1">System Status</h6>
                            <small class="text-muted">Current maintenance mode status</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode" 
                                   {{ ($configs['app.maintenance_mode']->value ?? false) ? 'checked' : '' }}
                                   onchange="toggleMaintenanceMode(this.checked)">
                            <label class="form-check-label text-white" for="maintenance_mode">
                                <span id="maintenance_status_text">
                                    {{ ($configs['app.maintenance_mode']->value ?? false) ? 'Maintenance Mode' : 'Online' }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Message -->
                <div class="mb-3">
                    <label for="maintenance_message" class="form-label text-white">Maintenance Message</label>
                    <textarea class="form-control bg-dark text-white border-secondary" 
                              id="maintenance_message" name="app.maintenance_message" 
                              rows="3" placeholder="We are currently performing scheduled maintenance..."
                              onchange="SystemConfig.updateConfig('app.maintenance_message', this.value, 'Updated maintenance message')">{{ $configs['app.maintenance_message']->value ?? 'We are currently performing scheduled maintenance. Please check back soon.' }}</textarea>
                    <small class="text-muted">Message displayed to users during maintenance</small>
                </div>

                <!-- Maintenance End Time -->
                <div class="mb-3">
                    <label for="maintenance_end_time" class="form-label text-white">Estimated End Time</label>
                    <input type="datetime-local" class="form-control bg-dark text-white border-secondary" 
                           id="maintenance_end_time" name="app.maintenance_end_time" 
                           value="{{ $configs['app.maintenance_end_time']->value ?? '' }}"
                           onchange="SystemConfig.updateConfig('app.maintenance_end_time', this.value, 'Updated maintenance end time')">
                    <small class="text-muted">Expected maintenance completion time</small>
                </div>

                <!-- Allowed IPs -->
                <div class="mb-3">
                    <label for="maintenance_allowed_ips" class="form-label text-white">Allowed IP Addresses</label>
                    <textarea class="form-control bg-dark text-white border-secondary" 
                              id="maintenance_allowed_ips" name="app.maintenance_allowed_ips" 
                              rows="3" placeholder="127.0.0.1&#10;192.168.1.100&#10;10.0.0.0/8"
                              onchange="SystemConfig.updateConfig('app.maintenance_allowed_ips', this.value, 'Updated allowed IPs')">{{ $configs['app.maintenance_allowed_ips']->value ?? '127.0.0.1' }}</textarea>
                    <small class="text-muted">IP addresses that can access the system during maintenance (one per line)</small>
                </div>

                <!-- Maintenance Actions -->
                <div class="mb-3">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-warning" onclick="scheduleMaintenanceMode()">
                            <i class="fas fa-clock"></i> Schedule Maintenance
                        </button>
                        <button type="button" class="btn btn-info" onclick="previewMaintenancePage()">
                            <i class="fas fa-eye"></i> Preview Maintenance Page
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-heartbeat text-success"></i> System Health
                </h5>
                
                <!-- Health Check Status -->
                <div class="mb-3">
                    <div class="card bg-dark border-secondary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-white mb-0">System Health Status</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshHealthStatus()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                            <div id="health-status-container">
                                <div class="text-center py-3">
                                    <i class="fas fa-spinner fa-spin text-primary"></i>
                                    <p class="text-muted mt-2 mb-0">Loading health status...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Health Check Settings -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="health_check_enabled" 
                               {{ ($configs['app.health_check_enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('app.health_check_enabled', this.checked, 'Toggled health check')">
                        <label class="form-check-label text-white" for="health_check_enabled">
                            Enable Health Monitoring
                        </label>
                        <small class="d-block text-muted">Automatically monitor system health</small>
                    </div>
                </div>

                <!-- Health Check Interval -->
                <div class="mb-3">
                    <label for="health_check_interval" class="form-label text-white">Health Check Interval (minutes)</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="health_check_interval" name="app.health_check_interval"
                            onchange="SystemConfig.updateConfig('app.health_check_interval', this.value, 'Updated health check interval')">
                        @php
                            $currentInterval = $configs['app.health_check_interval']->value ?? 5;
                        @endphp
                        <option value="1" {{ $currentInterval == 1 ? 'selected' : '' }}>1 minute</option>
                        <option value="5" {{ $currentInterval == 5 ? 'selected' : '' }}>5 minutes</option>
                        <option value="10" {{ $currentInterval == 10 ? 'selected' : '' }}>10 minutes</option>
                        <option value="15" {{ $currentInterval == 15 ? 'selected' : '' }}>15 minutes</option>
                        <option value="30" {{ $currentInterval == 30 ? 'selected' : '' }}>30 minutes</option>
                        <option value="60" {{ $currentInterval == 60 ? 'selected' : '' }}>1 hour</option>
                    </select>
                    <small class="text-muted">How often to check system health</small>
                </div>

                <!-- Health Alert Email -->
                <div class="mb-3">
                    <label for="health_alert_email" class="form-label text-white">Health Alert Email</label>
                    <input type="email" class="form-control bg-dark text-white border-secondary" 
                           id="health_alert_email" name="app.health_alert_email" 
                           value="{{ $configs['app.health_alert_email']->value ?? 'admin@example.com' }}"
                           placeholder="admin@example.com"
                           onchange="SystemConfig.updateConfig('app.health_alert_email', this.value, 'Updated health alert email')">
                    <small class="text-muted">Email address to receive health alerts</small>
                </div>

                <!-- Health Thresholds -->
                <div class="mb-3">
                    <label class="form-label text-white">Health Alert Thresholds</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="cpu_threshold" class="form-label text-white-50 small">CPU Usage (%)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" 
                                   id="cpu_threshold" name="app.health_cpu_threshold" 
                                   min="50" max="95" value="{{ $configs['app.health_cpu_threshold']->value ?? 80 }}"
                                   onchange="SystemConfig.updateConfig('app.health_cpu_threshold', this.value, 'Updated CPU threshold')">
                        </div>
                        <div class="col-6">
                            <label for="memory_threshold" class="form-label text-white-50 small">Memory Usage (%)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" 
                                   id="memory_threshold" name="app.health_memory_threshold" 
                                   min="50" max="95" value="{{ $configs['app.health_memory_threshold']->value ?? 85 }}"
                                   onchange="SystemConfig.updateConfig('app.health_memory_threshold', this.value, 'Updated memory threshold')">
                        </div>
                        <div class="col-6">
                            <label for="disk_threshold" class="form-label text-white-50 small">Disk Usage (%)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" 
                                   id="disk_threshold" name="app.health_disk_threshold" 
                                   min="70" max="95" value="{{ $configs['app.health_disk_threshold']->value ?? 90 }}"
                                   onchange="SystemConfig.updateConfig('app.health_disk_threshold', this.value, 'Updated disk threshold')">
                        </div>
                        <div class="col-6">
                            <label for="response_time_threshold" class="form-label text-white-50 small">Response Time (ms)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" 
                                   id="response_time_threshold" name="app.health_response_time_threshold" 
                                   min="500" max="5000" value="{{ $configs['app.health_response_time_threshold']->value ?? 2000 }}"
                                   onchange="SystemConfig.updateConfig('app.health_response_time_threshold', this.value, 'Updated response time threshold')">
                        </div>
                    </div>
                    <small class="text-muted">Alert when thresholds are exceeded</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Backup Management -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-database text-info"></i> Backup Management
                </h5>
                
                <!-- Auto Backup -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_backup_enabled" 
                               {{ ($configs['backup.auto_enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('backup.auto_enabled', this.checked, 'Toggled auto backup')">
                        <label class="form-check-label text-white" for="auto_backup_enabled">
                            Enable Automatic Backups
                        </label>
                        <small class="d-block text-muted">Automatically create system backups</small>
                    </div>
                </div>

                <!-- Backup Frequency -->
                <div class="mb-3">
                    <label for="backup_frequency" class="form-label text-white">Backup Frequency</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="backup_frequency" name="backup.frequency"
                            onchange="SystemConfig.updateConfig('backup.frequency', this.value, 'Updated backup frequency')">
                        @php
                            $currentFrequency = $configs['backup.frequency']->value ?? 'daily';
                        @endphp
                        <option value="hourly" {{ $currentFrequency === 'hourly' ? 'selected' : '' }}>Hourly</option>
                        <option value="daily" {{ $currentFrequency === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="weekly" {{ $currentFrequency === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ $currentFrequency === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    </select>
                    <small class="text-muted">How often to create automatic backups</small>
                </div>

                <!-- Backup Time -->
                <div class="mb-3">
                    <label for="backup_time" class="form-label text-white">Backup Time</label>
                    <input type="time" class="form-control bg-dark text-white border-secondary" 
                           id="backup_time" name="backup.time" 
                           value="{{ $configs['backup.time']->value ?? '02:00' }}"
                           onchange="SystemConfig.updateConfig('backup.time', this.value, 'Updated backup time')">
                    <small class="text-muted">Time to perform automatic backups</small>
                </div>

                <!-- Backup Retention -->
                <div class="mb-3">
                    <label for="backup_retention_days" class="form-label text-white">Backup Retention (days)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="backup_retention_days" name="backup.retention_days" 
                           min="1" max="365" value="{{ $configs['backup.retention_days']->value ?? 30 }}"
                           onchange="SystemConfig.updateConfig('backup.retention_days', this.value, 'Updated backup retention')">
                    <small class="text-muted">Number of days to keep backups (1-365)</small>
                </div>

                <!-- Backup Location -->
                <div class="mb-3">
                    <label for="backup_location" class="form-label text-white">Backup Location</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="backup_location" name="backup.location"
                            onchange="SystemConfig.updateConfig('backup.location', this.value, 'Updated backup location')">
                        @php
                            $currentLocation = $configs['backup.location']->value ?? 'local';
                        @endphp
                        <option value="local" {{ $currentLocation === 'local' ? 'selected' : '' }}>Local Storage</option>
                        <option value="s3" {{ $currentLocation === 's3' ? 'selected' : '' }}>Amazon S3</option>
                        <option value="ftp" {{ $currentLocation === 'ftp' ? 'selected' : '' }}>FTP Server</option>
                        <option value="dropbox" {{ $currentLocation === 'dropbox' ? 'selected' : '' }}>Dropbox</option>
                    </select>
                    <small class="text-muted">Where to store backup files</small>
                </div>

                <!-- Backup Actions -->
                <div class="mb-3">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" onclick="createBackup()">
                            <i class="fas fa-save"></i> Create Backup Now
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="viewBackupHistory()">
                            <i class="fas fa-history"></i> View Backup History
                        </button>
                        <button type="button" class="btn btn-outline-warning" onclick="restoreFromBackup()">
                            <i class="fas fa-undo"></i> Restore from Backup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Cleanup -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-broom text-warning"></i> System Cleanup
                </h5>
                
                <!-- Auto Cleanup -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_cleanup_enabled" 
                               {{ ($configs['cleanup.auto_enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('cleanup.auto_enabled', this.checked, 'Toggled auto cleanup')">
                        <label class="form-check-label text-white" for="auto_cleanup_enabled">
                            Enable Automatic Cleanup
                        </label>
                        <small class="d-block text-muted">Automatically clean temporary files and logs</small>
                    </div>
                </div>

                <!-- Log Retention -->
                <div class="mb-3">
                    <label for="log_retention_days" class="form-label text-white">Log Retention (days)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="log_retention_days" name="cleanup.log_retention_days" 
                           min="1" max="365" value="{{ $configs['cleanup.log_retention_days']->value ?? 30 }}"
                           onchange="SystemConfig.updateConfig('cleanup.log_retention_days', this.value, 'Updated log retention')">
                    <small class="text-muted">Number of days to keep log files (1-365)</small>
                </div>

                <!-- Temp File Cleanup -->
                <div class="mb-3">
                    <label for="temp_file_retention_hours" class="form-label text-white">Temp File Retention (hours)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="temp_file_retention_hours" name="cleanup.temp_file_retention_hours" 
                           min="1" max="168" value="{{ $configs['cleanup.temp_file_retention_hours']->value ?? 24 }}"
                           onchange="SystemConfig.updateConfig('cleanup.temp_file_retention_hours', this.value, 'Updated temp file retention')">
                    <small class="text-muted">Number of hours to keep temporary files (1-168)</small>
                </div>

                <!-- Session Cleanup -->
                <div class="mb-3">
                    <label for="session_cleanup_probability" class="form-label text-white">Session Cleanup Probability (%)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="session_cleanup_probability" name="cleanup.session_cleanup_probability" 
                           min="1" max="100" value="{{ $configs['cleanup.session_cleanup_probability']->value ?? 2 }}"
                           onchange="SystemConfig.updateConfig('cleanup.session_cleanup_probability', this.value, 'Updated session cleanup probability')">
                    <small class="text-muted">Probability of cleaning expired sessions (1-100%)</small>
                </div>

                <!-- Storage Usage -->
                <div class="mb-3">
                    <label class="form-label text-white">Storage Usage</label>
                    <div class="card bg-dark border-secondary">
                        <div class="card-body">
                            <div id="storage-usage-container">
                                <div class="text-center py-2">
                                    <i class="fas fa-spinner fa-spin text-primary"></i>
                                    <p class="text-muted mt-2 mb-0 small">Loading storage usage...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cleanup Actions -->
                <div class="mb-3">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-warning" onclick="cleanupTempFiles()">
                            <i class="fas fa-trash"></i> Clean Temp Files
                        </button>
                        <button type="button" class="btn btn-outline-warning" onclick="cleanupLogs()">
                            <i class="fas fa-file-alt"></i> Clean Old Logs
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="clearAllCaches()">
                            <i class="fas fa-eraser"></i> Clear All Caches
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Toggle maintenance mode
function toggleMaintenanceMode(enabled) {
    const statusText = document.getElementById('maintenance_status_text');
    
    fetch('/admin/system-config/maintenance/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ enabled: enabled })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusText.textContent = enabled ? 'Maintenance Mode' : 'Online';
            toastr.success(data.message);
        } else {
            // Revert checkbox if failed
            document.getElementById('maintenance_mode').checked = !enabled;
            toastr.error(data.message || 'Failed to toggle maintenance mode');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('maintenance_mode').checked = !enabled;
        toastr.error('An error occurred while toggling maintenance mode');
    });
}

// Schedule maintenance mode
function scheduleMaintenanceMode() {
    // This could open a modal for scheduling
    alert('Scheduled maintenance feature coming soon!');
}

// Preview maintenance page
function previewMaintenancePage() {
    window.open('/maintenance-preview', '_blank');
}

// Refresh health status
function refreshHealthStatus() {
    const container = document.getElementById('health-status-container');
    container.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin text-primary"></i>
            <p class="text-muted mt-2 mb-0">Refreshing health status...</p>
        </div>
    `;
    
    fetch('/admin/system-config/health-check')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayHealthStatus(data.health);
            } else {
                container.innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <p class="text-warning mt-2 mb-0">Failed to load health status</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
                <div class="text-center py-3">
                    <i class="fas fa-times-circle text-danger"></i>
                    <p class="text-danger mt-2 mb-0">Error loading health status</p>
                </div>
            `;
        });
}

// Display health status
function displayHealthStatus(health) {
    const container = document.getElementById('health-status-container');
    const overallStatus = health.overall_status;
    const statusColor = overallStatus === 'healthy' ? 'success' : overallStatus === 'warning' ? 'warning' : 'danger';
    const statusIcon = overallStatus === 'healthy' ? 'check-circle' : overallStatus === 'warning' ? 'exclamation-triangle' : 'times-circle';
    
    let html = `
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-${statusIcon} text-${statusColor} me-2"></i>
            <span class="text-${statusColor} fw-bold text-capitalize">${overallStatus}</span>
            <small class="text-muted ms-auto">${health.last_check}</small>
        </div>
        <div class="row g-2">
    `;
    
    // Add individual health metrics
    Object.entries(health.metrics).forEach(([key, metric]) => {
        const metricColor = metric.status === 'ok' ? 'success' : metric.status === 'warning' ? 'warning' : 'danger';
        const metricIcon = metric.status === 'ok' ? 'check' : metric.status === 'warning' ? 'exclamation' : 'times';
        
        html += `
            <div class="col-6">
                <div class="d-flex align-items-center">
                    <i class="fas fa-${metricIcon} text-${metricColor} me-2 small"></i>
                    <span class="text-white small">${key.replace('_', ' ').toUpperCase()}</span>
                </div>
                <div class="text-muted small">${metric.value}</div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Create backup
function createBackup() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    button.disabled = true;
    
    fetch('/admin/system-config/backup/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
        } else {
            toastr.error(data.message || 'Failed to create backup');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred while creating backup');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// View backup history
function viewBackupHistory() {
    // This could open a modal with backup history
    alert('Backup history feature coming soon!');
}

// Restore from backup
function restoreFromBackup() {
    // This could open a modal for selecting backup to restore
    alert('Backup restore feature coming soon!');
}

// Cleanup functions
function cleanupTempFiles() {
    if (confirm('Are you sure you want to clean temporary files?')) {
        performCleanup('temp-files', 'Cleaning temporary files...');
    }
}

function cleanupLogs() {
    if (confirm('Are you sure you want to clean old log files?')) {
        performCleanup('logs', 'Cleaning old logs...');
    }
}

function clearAllCaches() {
    if (confirm('Are you sure you want to clear all caches? This may temporarily slow down the system.')) {
        performCleanup('caches', 'Clearing all caches...');
    }
}

// Perform cleanup operation
function performCleanup(type, loadingMessage) {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + loadingMessage;
    button.disabled = true;
    
    fetch('/admin/system-config/cleanup/' + type, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            // Refresh storage usage if available
            loadStorageUsage();
        } else {
            toastr.error(data.message || 'Cleanup operation failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred during cleanup');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Load storage usage
function loadStorageUsage() {
    fetch('/admin/system-config/storage-usage')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStorageUsage(data.usage);
            }
        })
        .catch(error => {
            console.error('Error loading storage usage:', error);
        });
}

// Display storage usage
function displayStorageUsage(usage) {
    const container = document.getElementById('storage-usage-container');
    
    let html = '';
    Object.entries(usage).forEach(([key, value]) => {
        const percentage = value.percentage || 0;
        const color = percentage > 90 ? 'danger' : percentage > 75 ? 'warning' : 'success';
        
        html += `
            <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-white">${key.replace('_', ' ').toUpperCase()}</small>
                    <small class="text-muted">${value.used} / ${value.total}</small>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-${color}" style="width: ${percentage}%"></div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Initialize maintenance settings
document.addEventListener('DOMContentLoaded', function() {
    // Load initial health status
    refreshHealthStatus();
    
    // Load initial storage usage
    loadStorageUsage();
    
    // Auto-refresh health status every 5 minutes
    setInterval(refreshHealthStatus, 5 * 60 * 1000);
    
    // Auto-refresh storage usage every 10 minutes
    setInterval(loadStorageUsage, 10 * 60 * 1000);
});
</script>