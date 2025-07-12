@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-list me-2"></i>Activity Logs
            </h1>
            <p class="text-muted mb-0">Monitor user activities and system events</p>
        </div>
        <div>
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-1"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.monitoring.activity-logs') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Search activities...">
                </div>
                
                <div class="col-md-2">
                    <label for="action_type" class="form-label">Action Type</label>
                    <select class="form-select" id="action_type" name="action_type">
                        <option value="">All Actions</option>
                        @foreach($actionTypes as $type)
                            <option value="{{ $type }}" {{ request('action_type') === $type ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
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
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('admin.monitoring.activity-logs') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Logs Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Activity Logs</h6>
            <div class="d-flex gap-2">
                <span class="badge bg-info">{{ $logs->total() }} total records</span>
                <button class="btn btn-sm btn-outline-primary" onclick="exportLogs()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover" id="activityLogsTable">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Resource</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>
                                        <div class="text-nowrap">
                                            <strong>{{ $log->created_at->format('M d, Y') }}</strong><br>
                                            <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($log->user)
                                            <div>
                                                <strong>{{ $log->user->name }}</strong><br>
                                                <small class="text-muted">{{ $log->user->email }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getActionBadgeColor($log->action_type) }}">
                                            {{ ucfirst(str_replace('_', ' ', $log->action_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $log->resource_type }}</strong>
                                            @if($log->resource_id)
                                                <br><small class="text-muted">ID: {{ $log->resource_id }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $log->description }}">
                                            {{ $log->description }}
                                        </div>
                                    </td>
                                    <td>
                                        <code>{{ $log->ip_address }}</code>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 150px;" title="{{ $log->user_agent }}">
                                            <small>{{ $log->user_agent }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewLogDetails({{ $log->id }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
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
                            Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results
                        </small>
                    </div>
                    <div>
                        {{ $logs->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-muted">No Activity Logs Found</h5>
                    <p class="text-muted">No activities match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function viewLogDetails(logId) {
    // In a real implementation, you would fetch log details via AJAX
    // For now, we'll show a placeholder
    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    document.getElementById('logDetailsContent').innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
            <p>Loading log details...</p>
        </div>
    `;
    modal.show();
    
    // Simulate loading
    setTimeout(() => {
        document.getElementById('logDetailsContent').innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Basic Information</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Log ID:</strong></td><td>${logId}</td></tr>
                        <tr><td><strong>Timestamp:</strong></td><td>${new Date().toLocaleString()}</td></tr>
                        <tr><td><strong>Action Type:</strong></td><td>View Details</td></tr>
                        <tr><td><strong>Resource:</strong></td><td>Activity Log</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Context Information</h6>
                    <table class="table table-sm">
                        <tr><td><strong>IP Address:</strong></td><td>127.0.0.1</td></tr>
                        <tr><td><strong>User Agent:</strong></td><td>Browser Details</td></tr>
                        <tr><td><strong>Session ID:</strong></td><td>session_123</td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Additional Data</h6>
                    <pre class="bg-light p-3 rounded"><code>{"example": "Additional log data would appear here"}</code></pre>
                </div>
            </div>
        `;
    }, 1000);
}

function exportLogs() {
    // Get current filter parameters
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    // Create download link
    const downloadUrl = `${window.location.pathname}?${params.toString()}`;
    
    // Show loading state
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Exporting...';
    button.disabled = true;
    
    // Simulate export (in real implementation, this would trigger a download)
    setTimeout(() => {
        alert('Export functionality would be implemented here');
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}

// Initialize DataTable for better sorting and searching
$(document).ready(function() {
    if ($('#activityLogsTable tbody tr').length > 0) {
        $('#activityLogsTable').DataTable({
            "paging": false,
            "searching": false,
            "info": false,
            "ordering": true,
            "order": [[0, "desc"]],
            "columnDefs": [
                { "orderable": false, "targets": [6, 7] } // Disable sorting for User Agent and Actions columns
            ]
        });
    }
});
</script>
@endpush

@php
// Helper function for badge colors
function getActionBadgeColor($actionType) {
    $colors = [
        'create' => 'success',
        'update' => 'primary',
        'delete' => 'danger',
        'view' => 'info',
        'login' => 'success',
        'logout' => 'secondary',
        'failed_login' => 'warning',
        'password_change' => 'info',
        'permission_denied' => 'danger',
        'export' => 'primary',
        'import' => 'primary',
    ];
    
    return $colors[$actionType] ?? 'secondary';
}
@endphp
@endsection