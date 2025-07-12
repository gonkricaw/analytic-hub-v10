<form id="logsSettingsForm">
    @csrf
    <div class="row">
        <!-- Log Configuration -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-cog text-primary"></i> Log Configuration
                </h5>
                
                <!-- Log Level -->
                <div class="mb-3">
                    <label for="log_level" class="form-label text-white">Log Level</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="log_level" name="logging.level"
                            onchange="SystemConfig.updateConfig('logging.level', this.value, 'Updated log level')">
                        @php
                            $currentLevel = $configs['logging.level']->value ?? 'info';
                            $logLevels = [
                                'emergency' => 'Emergency (0)',
                                'alert' => 'Alert (1)',
                                'critical' => 'Critical (2)',
                                'error' => 'Error (3)',
                                'warning' => 'Warning (4)',
                                'notice' => 'Notice (5)',
                                'info' => 'Info (6)',
                                'debug' => 'Debug (7)'
                            ];
                        @endphp
                        @foreach($logLevels as $level => $label)
                            <option value="{{ $level }}" {{ $currentLevel === $level ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Minimum log level to record</small>
                </div>

                <!-- Log Channel -->
                <div class="mb-3">
                    <label for="log_channel" class="form-label text-white">Default Log Channel</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="log_channel" name="logging.default"
                            onchange="SystemConfig.updateConfig('logging.default', this.value, 'Updated log channel')">
                        @php
                            $currentChannel = $configs['logging.default']->value ?? 'stack';
                            $channels = [
                                'stack' => 'Stack (Multiple channels)',
                                'single' => 'Single File',
                                'daily' => 'Daily Files',
                                'syslog' => 'System Log',
                                'errorlog' => 'PHP Error Log',
                                'database' => 'Database',
                                'slack' => 'Slack',
                                'stderr' => 'Standard Error'
                            ];
                        @endphp
                        @foreach($channels as $channel => $label)
                            <option value="{{ $channel }}" {{ $currentChannel === $channel ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Where to send log messages</small>
                </div>

                <!-- Log File Path -->
                <div class="mb-3">
                    <label for="log_file_path" class="form-label text-white">Log File Path</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" 
                           id="log_file_path" name="logging.channels.single.path" 
                           value="{{ $configs['logging.channels.single.path']->value ?? 'storage/logs/laravel.log' }}"
                           placeholder="storage/logs/laravel.log"
                           onchange="SystemConfig.updateConfig('logging.channels.single.path', this.value, 'Updated log file path')">
                    <small class="text-muted">Path to the log file (relative to app root)</small>
                </div>

                <!-- Daily Log Retention -->
                <div class="mb-3">
                    <label for="log_daily_days" class="form-label text-white">Daily Log Retention (days)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="log_daily_days" name="logging.channels.daily.days" 
                           min="1" max="365" value="{{ $configs['logging.channels.daily.days']->value ?? 14 }}"
                           onchange="SystemConfig.updateConfig('logging.channels.daily.days', this.value, 'Updated daily log retention')">
                    <small class="text-muted">Number of days to keep daily log files (1-365)</small>
                </div>

                <!-- Log Format -->
                <div class="mb-3">
                    <label for="log_format" class="form-label text-white">Log Format</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="log_format" name="logging.format"
                            onchange="SystemConfig.updateConfig('logging.format', this.value, 'Updated log format')">
                        @php
                            $currentFormat = $configs['logging.format']->value ?? 'line';
                        @endphp
                        <option value="line" {{ $currentFormat === 'line' ? 'selected' : '' }}>Line Format</option>
                        <option value="json" {{ $currentFormat === 'json' ? 'selected' : '' }}>JSON Format</option>
                        <option value="custom" {{ $currentFormat === 'custom' ? 'selected' : '' }}>Custom Format</option>
                    </select>
                    <small class="text-muted">Format for log entries</small>
                </div>

                <!-- Custom Log Format -->
                <div class="mb-3" id="custom-log-format" style="{{ $currentFormat === 'custom' ? 'display: block;' : 'display: none;' }}">
                    <label for="log_custom_format" class="form-label text-white">Custom Log Format</label>
                    <textarea class="form-control bg-dark text-white border-secondary" 
                              id="log_custom_format" name="logging.custom_format" 
                              rows="3" placeholder="[%datetime%] %channel%.%level_name%: %message% %context% %extra%"
                              onchange="SystemConfig.updateConfig('logging.custom_format', this.value, 'Updated custom log format')">{{ $configs['logging.custom_format']->value ?? '[%datetime%] %channel%.%level_name%: %message% %context% %extra%' }}</textarea>
                    <small class="text-muted">Custom format string for log entries</small>
                </div>
            </div>
        </div>

        <!-- Log Monitoring -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-eye text-success"></i> Log Monitoring
                </h5>
                
                <!-- Enable Log Monitoring -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="log_monitoring_enabled" 
                               {{ ($configs['logging.monitoring.enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('logging.monitoring.enabled', this.checked, 'Toggled log monitoring')">
                        <label class="form-check-label text-white" for="log_monitoring_enabled">
                            Enable Log Monitoring
                        </label>
                        <small class="d-block text-muted">Monitor logs for errors and alerts</small>
                    </div>
                </div>

                <!-- Error Alert Threshold -->
                <div class="mb-3">
                    <label for="error_alert_threshold" class="form-label text-white">Error Alert Threshold</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="error_alert_threshold" name="logging.monitoring.error_threshold" 
                           min="1" max="1000" value="{{ $configs['logging.monitoring.error_threshold']->value ?? 10 }}"
                           onchange="SystemConfig.updateConfig('logging.monitoring.error_threshold', this.value, 'Updated error alert threshold')">
                    <small class="text-muted">Number of errors per hour to trigger alert (1-1000)</small>
                </div>

                <!-- Alert Email -->
                <div class="mb-3">
                    <label for="log_alert_email" class="form-label text-white">Alert Email Address</label>
                    <input type="email" class="form-control bg-dark text-white border-secondary" 
                           id="log_alert_email" name="logging.monitoring.alert_email" 
                           value="{{ $configs['logging.monitoring.alert_email']->value ?? 'admin@example.com' }}"
                           placeholder="admin@example.com"
                           onchange="SystemConfig.updateConfig('logging.monitoring.alert_email', this.value, 'Updated log alert email')">
                    <small class="text-muted">Email address to receive log alerts</small>
                </div>

                <!-- Log Rotation -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="log_rotation_enabled" 
                               {{ ($configs['logging.rotation.enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('logging.rotation.enabled', this.checked, 'Toggled log rotation')">
                        <label class="form-check-label text-white" for="log_rotation_enabled">
                            Enable Log Rotation
                        </label>
                        <small class="d-block text-muted">Automatically rotate log files when they get large</small>
                    </div>
                </div>

                <!-- Max Log File Size -->
                <div class="mb-3">
                    <label for="max_log_file_size" class="form-label text-white">Max Log File Size (MB)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="max_log_file_size" name="logging.rotation.max_size" 
                           min="1" max="1000" value="{{ $configs['logging.rotation.max_size']->value ?? 100 }}"
                           onchange="SystemConfig.updateConfig('logging.rotation.max_size', this.value, 'Updated max log file size')">
                    <small class="text-muted">Maximum size before rotating log file (1-1000 MB)</small>
                </div>

                <!-- Max Log Files -->
                <div class="mb-3">
                    <label for="max_log_files" class="form-label text-white">Max Log Files to Keep</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="max_log_files" name="logging.rotation.max_files" 
                           min="1" max="100" value="{{ $configs['logging.rotation.max_files']->value ?? 10 }}"
                           onchange="SystemConfig.updateConfig('logging.rotation.max_files', this.value, 'Updated max log files')">
                    <small class="text-muted">Number of rotated log files to keep (1-100)</small>
                </div>

                <!-- Log Statistics -->
                <div class="mb-3">
                    <label class="form-label text-white">Log Statistics (Last 24 Hours)</label>
                    <div class="card bg-dark border-secondary">
                        <div class="card-body">
                            <div id="log-stats-container">
                                <div class="text-center py-2">
                                    <i class="fas fa-spinner fa-spin text-primary"></i>
                                    <p class="text-muted mt-2 mb-0 small">Loading log statistics...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Log Viewer -->
        <div class="col-12">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-file-alt text-warning"></i> Log Viewer
                </h5>
                
                <!-- Log Viewer Controls -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="log_file_select" class="form-label text-white">Log File</label>
                        <select class="form-select bg-dark text-white border-secondary" id="log_file_select">
                            <option value="">Select log file...</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="log_level_filter" class="form-label text-white">Level Filter</label>
                        <select class="form-select bg-dark text-white border-secondary" id="log_level_filter">
                            <option value="">All Levels</option>
                            <option value="emergency">Emergency</option>
                            <option value="alert">Alert</option>
                            <option value="critical">Critical</option>
                            <option value="error">Error</option>
                            <option value="warning">Warning</option>
                            <option value="notice">Notice</option>
                            <option value="info">Info</option>
                            <option value="debug">Debug</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="log_search" class="form-label text-white">Search</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               id="log_search" placeholder="Search in logs...">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="log_lines" class="form-label text-white">Lines</label>
                        <select class="form-select bg-dark text-white border-secondary" id="log_lines">
                            <option value="50">50 lines</option>
                            <option value="100" selected>100 lines</option>
                            <option value="200">200 lines</option>
                            <option value="500">500 lines</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label text-white">&nbsp;</label>
                        <div class="d-grid">
                            <button type="button" class="btn btn-primary" onclick="loadLogContent()">
                                <i class="fas fa-search"></i> Load
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Log Content -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label text-white mb-0">Log Content</label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="refreshLogContent()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="downloadLogFile()">
                                <i class="fas fa-download"></i> Download
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="clearLogFile()">
                                <i class="fas fa-trash"></i> Clear
                            </button>
                        </div>
                    </div>
                    
                    <div class="card bg-dark border-secondary" style="height: 400px;">
                        <div class="card-body p-0">
                            <pre id="log-content" class="h-100 m-0 p-3 text-white-50 small" style="overflow-y: auto; font-family: 'Courier New', monospace; background: transparent; border: none;">
Select a log file and click "Load" to view its content...
                            </pre>
                        </div>
                    </div>
                </div>
                
                <!-- Log Actions -->
                <div class="row">
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-success w-100 mb-2" onclick="exportLogs()">
                            <i class="fas fa-file-export"></i> Export Logs
                        </button>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-info w-100 mb-2" onclick="archiveLogs()">
                            <i class="fas fa-archive"></i> Archive Old Logs
                        </button>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="rotateLogs()">
                            <i class="fas fa-redo"></i> Rotate Logs
                        </button>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-danger w-100 mb-2" onclick="deleteOldLogs()">
                            <i class="fas fa-trash-alt"></i> Delete Old Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Toggle custom log format visibility
document.getElementById('log_format').addEventListener('change', function() {
    const customFormatDiv = document.getElementById('custom-log-format');
    customFormatDiv.style.display = this.value === 'custom' ? 'block' : 'none';
});

// Load available log files
function loadLogFiles() {
    fetch('/admin/system-config/logs/files')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('log_file_select');
                select.innerHTML = '<option value="">Select log file...</option>';
                
                data.files.forEach(file => {
                    const option = document.createElement('option');
                    option.value = file.path;
                    option.textContent = `${file.name} (${file.size})`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading log files:', error);
        });
}

// Load log content
function loadLogContent() {
    const logFile = document.getElementById('log_file_select').value;
    const levelFilter = document.getElementById('log_level_filter').value;
    const searchTerm = document.getElementById('log_search').value;
    const lines = document.getElementById('log_lines').value;
    
    if (!logFile) {
        toastr.error('Please select a log file');
        return;
    }
    
    const logContent = document.getElementById('log-content');
    logContent.textContent = 'Loading log content...';
    
    const params = new URLSearchParams({
        file: logFile,
        lines: lines
    });
    
    if (levelFilter) params.append('level', levelFilter);
    if (searchTerm) params.append('search', searchTerm);
    
    fetch(`/admin/system-config/logs/content?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                logContent.textContent = data.content || 'Log file is empty';
                // Scroll to bottom
                logContent.scrollTop = logContent.scrollHeight;
            } else {
                logContent.textContent = 'Error loading log content: ' + (data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error loading log content:', error);
            logContent.textContent = 'Error loading log content';
        });
}

// Refresh log content
function refreshLogContent() {
    loadLogContent();
}

// Download log file
function downloadLogFile() {
    const logFile = document.getElementById('log_file_select').value;
    
    if (!logFile) {
        toastr.error('Please select a log file');
        return;
    }
    
    window.open(`/admin/system-config/logs/download?file=${encodeURIComponent(logFile)}`);
}

// Clear log file
function clearLogFile() {
    const logFile = document.getElementById('log_file_select').value;
    
    if (!logFile) {
        toastr.error('Please select a log file');
        return;
    }
    
    if (confirm('Are you sure you want to clear this log file? This action cannot be undone.')) {
        fetch('/admin/system-config/logs/clear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ file: logFile })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                loadLogContent(); // Refresh content
            } else {
                toastr.error(data.message || 'Failed to clear log file');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('An error occurred while clearing log file');
        });
    }
}

// Export logs
function exportLogs() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    button.disabled = true;
    
    fetch('/admin/system-config/logs/export', {
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
            if (data.download_url) {
                window.open(data.download_url);
            }
        } else {
            toastr.error(data.message || 'Failed to export logs');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred while exporting logs');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Archive logs
function archiveLogs() {
    if (confirm('Are you sure you want to archive old logs?')) {
        performLogAction('archive', 'Archiving logs...');
    }
}

// Rotate logs
function rotateLogs() {
    if (confirm('Are you sure you want to rotate logs?')) {
        performLogAction('rotate', 'Rotating logs...');
    }
}

// Delete old logs
function deleteOldLogs() {
    if (confirm('Are you sure you want to delete old logs? This action cannot be undone.')) {
        performLogAction('delete-old', 'Deleting old logs...');
    }
}

// Perform log action
function performLogAction(action, loadingMessage) {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + loadingMessage;
    button.disabled = true;
    
    fetch(`/admin/system-config/logs/${action}`, {
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
            loadLogFiles(); // Refresh file list
            loadLogStats(); // Refresh statistics
        } else {
            toastr.error(data.message || 'Operation failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred during operation');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Load log statistics
function loadLogStats() {
    fetch('/admin/system-config/logs/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayLogStats(data.stats);
            }
        })
        .catch(error => {
            console.error('Error loading log stats:', error);
        });
}

// Display log statistics
function displayLogStats(stats) {
    const container = document.getElementById('log-stats-container');
    
    let html = '<div class="row g-2">';
    
    Object.entries(stats).forEach(([level, count]) => {
        const color = getLogLevelColor(level);
        html += `
            <div class="col-6">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-${color} small text-capitalize">${level}</span>
                    <span class="text-white fw-bold">${count}</span>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Get color for log level
function getLogLevelColor(level) {
    const colors = {
        emergency: 'danger',
        alert: 'danger',
        critical: 'danger',
        error: 'danger',
        warning: 'warning',
        notice: 'info',
        info: 'primary',
        debug: 'secondary'
    };
    
    return colors[level] || 'secondary';
}

// Auto-refresh log content
let autoRefreshInterval;

function toggleAutoRefresh() {
    const checkbox = document.getElementById('auto_refresh_logs');
    
    if (checkbox && checkbox.checked) {
        autoRefreshInterval = setInterval(() => {
            if (document.getElementById('log_file_select').value) {
                loadLogContent();
            }
        }, 30000); // Refresh every 30 seconds
    } else {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
    }
}

// Initialize logs settings
document.addEventListener('DOMContentLoaded', function() {
    // Load available log files
    loadLogFiles();
    
    // Load log statistics
    loadLogStats();
    
    // Set up search functionality
    let searchTimeout;
    document.getElementById('log_search').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (document.getElementById('log_file_select').value) {
                loadLogContent();
            }
        }, 500);
    });
    
    // Set up level filter
    document.getElementById('log_level_filter').addEventListener('change', function() {
        if (document.getElementById('log_file_select').value) {
            loadLogContent();
        }
    });
    
    // Auto-refresh stats every 5 minutes
    setInterval(loadLogStats, 5 * 60 * 1000);
});
</script>