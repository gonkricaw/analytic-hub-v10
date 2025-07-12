@extends('layouts.app')

@section('title', 'Security Logs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-shield-alt me-2 text-warning"></i>Security Logs
            </h1>
            <p class="text-muted mb-0">Monitor security events, failed logins, and potential threats</p>
        </div>
        <div>
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Security Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Failed Logins
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $securityStats['failed_logins'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Blocked IPs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $securityStats['blocked_ips'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                SQL Injections
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $securityStats['sql_injections'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Security Score
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $securityStats['score'] ?? '95' }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shield-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Alerts -->
    @if(isset($securityAlerts) && count($securityAlerts) > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-danger text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-exclamation-triangle me-1"></i>Active Security Alerts
            </h6>
        </div>
        <div class="card-body">
            @foreach($securityAlerts as $alert)
                <div class="alert alert-{{ $alert['severity'] }} alert-dismissible fade show" role="alert">
                    <strong>{{ $alert['title'] }}</strong> - {{ $alert['message'] }}
                    <small class="d-block mt-1 text-muted">{{ $alert['timestamp'] }}</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-1"></i>Security Log Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.monitoring.security-logs') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="IP, User, Event...">
                </div>
                
                <div class="col-md-2">
                    <label for="event_type" class="form-label">Event Type</label>
                    <select class="form-select" id="event_type" name="event_type">
                        <option value="">All Events</option>
                        <option value="failed_login" {{ request('event_type') === 'failed_login' ? 'selected' : '' }}>Failed Login</option>
                        <option value="successful_login" {{ request('event_type') === 'successful_login' ? 'selected' : '' }}>Successful Login</option>
                        <option value="logout" {{ request('event_type') === 'logout' ? 'selected' : '' }}>Logout</option>
                        <option value="password_change" {{ request('event_type') === 'password_change' ? 'selected' : '' }}>Password Change</option>
                        <option value="account_locked" {{ request('event_type') === 'account_locked' ? 'selected' : '' }}>Account Locked</option>
                        <option value="sql_injection" {{ request('event_type') === 'sql_injection' ? 'selected' : '' }}>SQL Injection</option>
                        <option value="xss_attempt" {{ request('event_type') === 'xss_attempt' ? 'selected' : '' }}>XSS Attempt</option>
                        <option value="csrf_violation" {{ request('event_type') === 'csrf_violation' ? 'selected' : '' }}>CSRF Violation</option>
                        <option value="unauthorized_access" {{ request('event_type') === 'unauthorized_access' ? 'selected' : '' }}>Unauthorized Access</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="severity" class="form-label">Severity</label>
                    <select class="form-select" id="severity" name="severity">
                        <option value="">All Severities</option>
                        <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ request('date_from') }}">
                </div>
                
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="{{ request('date_to') }}">
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('admin.monitoring.security-logs') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Logs Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Security Events</h6>
            <div class="d-flex gap-2">
                <span class="badge bg-info">{{ $logs->total() }} events</span>
                <button class="btn btn-sm btn-outline-primary" onclick="exportSecurityLogs()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="securityLogsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>Event Type</th>
                                <th>Severity</th>
                                <th>IP Address</th>
                                <th>User</th>
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="{{ $this->getRowClass($log) }}">
                                    <td>
                                        <small>{{ $log->created_at->format('Y-m-d H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getEventTypeColor($log->event_type) }}">
                                            {{ ucfirst(str_replace('_', ' ', $log->event_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getSeverityColor($log->severity) }}">
                                            {{ ucfirst($log->severity) }}
                                        </span>
                                    </td>
                                    <td>
                                        <code>{{ $log->ip_address }}</code>
                                        @if($log->is_blocked)
                                            <i class="fas fa-ban text-danger ms-1" title="Blocked IP"></i>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->user)
                                            <a href="{{ route('admin.users.show', $log->user_id) }}" class="text-decoration-none">
                                                {{ $log->user->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">Guest</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="{{ $log->details }}">
                                            {{ $log->details }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-info" onclick="viewLogDetails({{ $log->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if(!$log->is_blocked && $log->ip_address)
                                                <button class="btn btn-sm btn-outline-warning" onclick="blockIP('{{ $log->ip_address }}')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                            @if($log->user_id)
                                                <button class="btn btn-sm btn-outline-danger" onclick="lockUser({{ $log->user_id }})">
                                                    <i class="fas fa-user-lock"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <small class="text-muted">
                            Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} events
                        </small>
                    </div>
                    <div>
                        {{ $logs->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-shield-check fa-3x text-success mb-3"></i>
                    <h5 class="text-success">No Security Events</h5>
                    <p class="text-muted">No security events match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Security Log Detail Modal -->
<div class="modal fade" id="securityLogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Security Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="securityLogContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" id="blockIPBtn" style="display: none;">
                    <i class="fas fa-ban me-1"></i>Block IP
                </button>
                <button type="button" class="btn btn-danger" id="lockUserBtn" style="display: none;">
                    <i class="fas fa-user-lock me-1"></i>Lock User
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function viewLogDetails(logId) {
    // In a real implementation, this would fetch log details via AJAX
    const modal = new bootstrap.Modal(document.getElementById('securityLogModal'));
    
    // Mock data for demonstration
    const logDetails = `
        <div class="row">
            <div class="col-md-6">
                <h6>Event Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Event ID:</strong></td><td>${logId}</td></tr>
                    <tr><td><strong>Timestamp:</strong></td><td>2024-01-15 14:30:25</td></tr>
                    <tr><td><strong>Event Type:</strong></td><td>Failed Login</td></tr>
                    <tr><td><strong>Severity:</strong></td><td>Medium</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Source Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>IP Address:</strong></td><td>192.168.1.100</td></tr>
                    <tr><td><strong>User Agent:</strong></td><td>Mozilla/5.0...</td></tr>
                    <tr><td><strong>Referrer:</strong></td><td>/login</td></tr>
                    <tr><td><strong>Country:</strong></td><td>United States</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Event Details</h6>
                <div class="bg-light p-3 rounded">
                    <pre>Failed login attempt for user 'admin' from IP 192.168.1.100\nReason: Invalid password\nAttempt count: 3/5</pre>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('securityLogContent').innerHTML = logDetails;
    modal.show();
}

function blockIP(ipAddress) {
    if (!confirm(`Are you sure you want to block IP address ${ipAddress}?`)) {
        return;
    }
    
    // In a real implementation, this would make an AJAX call
    showAlert(`IP address ${ipAddress} has been blocked`, 'warning');
}

function lockUser(userId) {
    if (!confirm('Are you sure you want to lock this user account?')) {
        return;
    }
    
    // In a real implementation, this would make an AJAX call
    showAlert('User account has been locked', 'warning');
}

function exportSecurityLogs() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    const exportUrl = `{{ route('admin.monitoring.security-logs') }}?${params.toString()}`;
    window.open(exportUrl, '_blank');
}

function refreshLogs() {
    location.reload();
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Auto-refresh every 60 seconds
setInterval(() => {
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 60000);
</script>
@endpush

@php
// Helper functions for security log display
function getRowClass($log) {
    $classes = [
        'critical' => 'table-danger',
        'high' => 'table-warning',
        'medium' => '',
        'low' => '',
    ];
    
    return $classes[$log->severity] ?? '';
}

function getEventTypeColor($eventType) {
    $colors = [
        'failed_login' => 'danger',
        'successful_login' => 'success',
        'logout' => 'info',
        'password_change' => 'warning',
        'account_locked' => 'danger',
        'sql_injection' => 'danger',
        'xss_attempt' => 'danger',
        'csrf_violation' => 'warning',
        'unauthorized_access' => 'danger',
    ];
    
    return $colors[$eventType] ?? 'secondary';
}

function getSeverityColor($severity) {
    $colors = [
        'low' => 'success',
        'medium' => 'warning',
        'high' => 'danger',
        'critical' => 'danger',
    ];
    
    return $colors[$severity] ?? 'secondary';
}
@endphp
@endsection