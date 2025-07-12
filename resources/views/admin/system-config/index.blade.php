@extends('layouts.app')

@section('title', 'System Configuration')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-white">System Configuration</h1>
                    <p class="text-muted mb-0">Manage application settings and system preferences</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" onclick="performHealthCheck()">
                        <i class="fas fa-heartbeat"></i> Health Check
                    </button>
                    <button type="button" class="btn btn-warning" onclick="toggleMaintenance()">
                        <i class="fas fa-tools"></i> Maintenance Mode
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- System Health Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-heartbeat text-success"></i> System Health Status
                    </h5>
                </div>
                <div class="card-body">
                    <div id="health-status" class="row">
                        @if(isset($healthStatus))
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="health-indicator {{ $healthStatus['overall_status'] === 'healthy' ? 'text-success' : ($healthStatus['overall_status'] === 'degraded' ? 'text-warning' : 'text-danger') }}">
                                        <i class="fas fa-circle fa-2x"></i>
                                    </div>
                                    <h6 class="mt-2 text-white">Overall</h6>
                                    <small class="text-muted text-capitalize">{{ $healthStatus['overall_status'] }}</small>
                                </div>
                            </div>
                            @foreach($healthStatus['checks'] as $check => $status)
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <div class="health-indicator {{ $status['status'] === 'healthy' ? 'text-success' : ($status['status'] === 'warning' ? 'text-warning' : 'text-danger') }}">
                                            <i class="fas fa-circle"></i>
                                        </div>
                                        <h6 class="mt-2 text-white text-capitalize">{{ str_replace('_', ' ', $check) }}</h6>
                                        <small class="text-muted">{{ $status['message'] ?? $status['status'] }}</small>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-12 text-center">
                                <p class="text-muted">Click "Health Check" to view system status</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-cog"></i> General Settings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab">
                                <i class="fas fa-palette"></i> Appearance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                <i class="fas fa-shield-alt"></i> Security
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                                <i class="fas fa-envelope"></i> Email Settings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                                <i class="fas fa-tools"></i> Maintenance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">
                                <i class="fas fa-file-alt"></i> System Logs
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="configTabContent">
                        <!-- General Settings Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            @include('admin.system-config.tabs.general')
                        </div>

                        <!-- Appearance Tab -->
                        <div class="tab-pane fade" id="appearance" role="tabpanel">
                            @include('admin.system-config.tabs.appearance')
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            @include('admin.system-config.tabs.security')
                        </div>

                        <!-- Email Settings Tab -->
                        <div class="tab-pane fade" id="email" role="tabpanel">
                            @include('admin.system-config.tabs.email')
                        </div>

                        <!-- Maintenance Tab -->
                        <div class="tab-pane fade" id="maintenance" role="tabpanel">
                            @include('admin.system-config.tabs.maintenance')
                        </div>

                        <!-- System Logs Tab -->
                        <div class="tab-pane fade" id="logs" role="tabpanel">
                            @include('admin.system-config.tabs.logs')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Changes -->
    @if(isset($recentChanges) && $recentChanges->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history"></i> Recent Configuration Changes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Configuration</th>
                                    <th>Changed By</th>
                                    <th>Changed At</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentChanges as $change)
                                <tr>
                                    <td>
                                        <strong>{{ $change->display_name }}</strong>
                                        <br><small class="text-muted">{{ $change->key }}</small>
                                    </td>
                                    <td>
                                        @if($change->lastChangedBy)
                                            {{ $change->lastChangedBy->name }}
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>{{ $change->last_changed_at ? $change->last_changed_at->format('M d, Y H:i') : 'N/A' }}</td>
                                    <td>{{ $change->change_reason ?? 'No reason provided' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Maintenance Mode Modal -->
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-tools"></i> Maintenance Mode
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="maintenanceForm">
                    <div class="mb-3">
                        <label class="form-label text-white">Action</label>
                        <select class="form-select bg-dark text-white border-secondary" id="maintenanceAction" name="action">
                            <option value="enable">Enable Maintenance Mode</option>
                            <option value="disable">Disable Maintenance Mode</option>
                        </select>
                    </div>
                    <div class="mb-3" id="messageGroup">
                        <label for="maintenanceMessage" class="form-label text-white">Maintenance Message</label>
                        <textarea class="form-control bg-dark text-white border-secondary" id="maintenanceMessage" name="message" rows="3" placeholder="Enter maintenance message for users...">System is under maintenance. Please try again later.</textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitMaintenanceForm()">Apply Changes</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.health-indicator {
    font-size: 1.2rem;
}

.nav-tabs .nav-link {
    background-color: transparent;
    border-color: #495057;
    color: #adb5bd;
}

.nav-tabs .nav-link.active {
    background-color: #495057;
    border-color: #495057;
    color: #fff;
}

.nav-tabs .nav-link:hover {
    border-color: #6c757d;
    color: #fff;
}

.config-item {
    border: 1px solid #495057;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #2d3748;
}

.config-item:hover {
    border-color: #6c757d;
}

.file-upload-area {
    border: 2px dashed #495057;
    border-radius: 0.375rem;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-upload-area:hover {
    border-color: #FF7A00;
    background-color: rgba(255, 122, 0, 0.1);
}

.file-upload-area.dragover {
    border-color: #FF7A00;
    background-color: rgba(255, 122, 0, 0.2);
}

.preview-image {
    max-width: 200px;
    max-height: 100px;
    border-radius: 0.375rem;
    border: 1px solid #495057;
}

.log-entry {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    white-space: pre-wrap;
    background-color: #1a1a1a;
    border: 1px solid #495057;
    border-radius: 0.375rem;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
}

.log-level-error {
    border-left: 4px solid #dc3545;
}

.log-level-warning {
    border-left: 4px solid #ffc107;
}

.log-level-info {
    border-left: 4px solid #17a2b8;
}

.log-level-debug {
    border-left: 4px solid #6c757d;
}
</style>
@endpush

@push('scripts')
<script>
// System Configuration Management
const SystemConfig = {
    // Update configuration value
    updateConfig: function(key, value, reason = '') {
        const formData = new FormData();
        formData.append('value', value);
        if (reason) formData.append('reason', reason);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        
        fetch(`/admin/system-config/${key}`, {
            method: 'PUT',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                if (data.requires_restart) {
                    toastr.warning('This change requires application restart to take effect.');
                }
            } else {
                toastr.error(data.message || 'Failed to update configuration');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('An error occurred while updating configuration');
        });
    },

    // Upload file
    uploadFile: function(input, type, configKey) {
        const file = input.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append(type, file);
        if (type === 'logo') {
            formData.append('type', configKey.split('.').pop());
        }
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        const endpoint = type === 'logo' ? '/admin/system-config/upload-logo' : '/admin/system-config/upload-background';
        
        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                // Update preview if exists
                const preview = document.querySelector(`#preview-${configKey.replace(/\./g, '-')}`);
                if (preview) {
                    preview.src = data.path;
                    preview.style.display = 'block';
                }
            } else {
                toastr.error(data.message || 'Failed to upload file');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('An error occurred while uploading file');
        });
    }
};

// Health Check
function performHealthCheck() {
    const button = document.querySelector('button[onclick="performHealthCheck()"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    button.disabled = true;

    fetch('/admin/system-config/health-check')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateHealthStatus(data.health);
                toastr.success('Health check completed');
            } else {
                toastr.error('Health check failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('An error occurred during health check');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
}

// Update health status display
function updateHealthStatus(health) {
    const container = document.getElementById('health-status');
    let html = '';
    
    // Overall status
    const overallClass = health.overall_status === 'healthy' ? 'text-success' : 
                        health.overall_status === 'degraded' ? 'text-warning' : 'text-danger';
    
    html += `
        <div class="col-md-2">
            <div class="text-center">
                <div class="health-indicator ${overallClass}">
                    <i class="fas fa-circle fa-2x"></i>
                </div>
                <h6 class="mt-2 text-white">Overall</h6>
                <small class="text-muted text-capitalize">${health.overall_status}</small>
            </div>
        </div>
    `;
    
    // Individual checks
    Object.entries(health.checks).forEach(([check, status]) => {
        const statusClass = status.status === 'healthy' ? 'text-success' : 
                           status.status === 'warning' ? 'text-warning' : 'text-danger';
        
        html += `
            <div class="col-md-2">
                <div class="text-center">
                    <div class="health-indicator ${statusClass}">
                        <i class="fas fa-circle"></i>
                    </div>
                    <h6 class="mt-2 text-white text-capitalize">${check.replace('_', ' ')}</h6>
                    <small class="text-muted">${status.message || status.status}</small>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Maintenance Mode
function toggleMaintenance() {
    const modal = new bootstrap.Modal(document.getElementById('maintenanceModal'));
    modal.show();
}

function submitMaintenanceForm() {
    const form = document.getElementById('maintenanceForm');
    const formData = new FormData(form);
    const action = formData.get('action');
    
    formData.append('enable', action === 'enable');
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch('/admin/system-config/maintenance', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            bootstrap.Modal.getInstance(document.getElementById('maintenanceModal')).hide();
        } else {
            toastr.error(data.message || 'Failed to toggle maintenance mode');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred while toggling maintenance mode');
    });
}

// Show/hide maintenance message based on action
document.getElementById('maintenanceAction').addEventListener('change', function() {
    const messageGroup = document.getElementById('messageGroup');
    messageGroup.style.display = this.value === 'enable' ? 'block' : 'none';
});

// Auto-refresh health status every 5 minutes
setInterval(performHealthCheck, 5 * 60 * 1000);
</script>
@endpush