@extends('layouts.app')

@section('title', 'System Monitoring Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-line me-2"></i>System Monitoring Dashboard
            </h1>
            <p class="text-muted mb-0">Real-time system health, performance metrics, and monitoring tools</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Health Status Alert -->
    <div class="row mb-4">
        <div class="col-12">
            @if($healthStatus['overall_status'] === 'healthy')
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div>
                        <strong>System Status: Healthy</strong>
                        <small class="d-block">All systems are operating normally. Last check: {{ \Carbon\Carbon::parse($healthStatus['last_check'])->diffForHumans() }}</small>
                    </div>
                </div>
            @else
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>System Status: Warning</strong>
                        <small class="d-block">Some issues detected. Please review the health checks below.</small>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- System Metrics Cards -->
    <div class="row mb-4">
        <!-- Users & Sessions -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Users & Sessions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($metrics['total_users']) }}</div>
                            <small class="text-muted">{{ number_format($metrics['active_sessions']) }} active sessions</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content & Activities -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Content & Activities
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($metrics['total_content']) }}</div>
                            <small class="text-muted">{{ number_format($metrics['total_activities']) }} activities logged</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Performance
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $performanceMetrics['avg_response_time'] }}ms</div>
                            <small class="text-muted">{{ $performanceMetrics['slow_requests_count'] }} slow requests today</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Resources -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                System Resources
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['memory_usage']['current_mb'] }}MB</div>
                            <small class="text-muted">{{ $metrics['disk_usage']['used_percent'] }}% disk used</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-server fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Checks & Quick Actions -->
    <div class="row mb-4">
        <!-- Health Checks -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">System Health Checks</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="runHealthCheck()">
                        <i class="fas fa-sync-alt me-1"></i>Run Check
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($healthStatus['checks'] as $checkName => $check)
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    @if($check['status'] === 'healthy')
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                    @elseif($check['status'] === 'warning')
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    @else
                                        <i class="fas fa-times-circle text-danger me-2"></i>
                                    @endif
                                    <div>
                                        <strong class="text-capitalize">{{ str_replace('_', ' ', $checkName) }}</strong>
                                        <small class="d-block text-muted">{{ $check['message'] }}</small>
                                        @if(isset($check['response_time_ms']))
                                            <small class="text-info">{{ $check['response_time_ms'] }}ms</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.monitoring.activity-logs') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list me-1"></i>View Activity Logs
                        </a>
                        <a href="{{ route('admin.monitoring.error-logs') }}" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-exclamation-circle me-1"></i>View Error Logs
                        </a>
                        <a href="{{ route('admin.monitoring.performance-logs') }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-chart-line me-1"></i>Performance Logs
                        </a>
                        <a href="{{ route('admin.monitoring.system-metrics') }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-chart-bar me-1"></i>System Metrics
                        </a>
                        <a href="{{ route('admin.monitoring.email-logs') }}" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-envelope me-1"></i>Email Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                    <a href="{{ route('admin.monitoring.activity-logs') }}" class="btn btn-sm btn-outline-primary">
                        View All <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    @if($recentActivity->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Resource</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentActivity as $activity)
                                        <tr>
                                            <td>
                                                <small>{{ $activity->created_at->format('H:i:s') }}</small><br>
                                                <small class="text-muted">{{ $activity->created_at->format('M d') }}</small>
                                            </td>
                                            <td>
                                                @if($activity->user)
                                                    <strong>{{ $activity->user->name }}</strong><br>
                                                    <small class="text-muted">{{ $activity->user->email }}</small>
                                                @else
                                                    <span class="text-muted">System</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $activity->action_type }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $activity->resource_type }}</strong>
                                                @if($activity->resource_id)
                                                    <small class="text-muted d-block">ID: {{ $activity->resource_id }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <code>{{ $activity->ip_address }}</code>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">No recent activity to display</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function refreshDashboard() {
    location.reload();
}

function runHealthCheck() {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Checking...';
    button.disabled = true;
    
    fetch('{{ route("admin.monitoring.health-check") }}')
        .then(response => response.json())
        .then(data => {
            // Show success message
            showAlert('Health check completed successfully', 'success');
            
            // Refresh the page after a short delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        })
        .catch(error => {
            console.error('Health check failed:', error);
            showAlert('Health check failed. Please try again.', 'danger');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
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
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Auto-refresh dashboard every 5 minutes
setInterval(() => {
    location.reload();
}, 300000);
</script>
@endpush
@endsection