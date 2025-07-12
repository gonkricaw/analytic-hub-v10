@extends('layouts.app')

@section('title', 'System Health Check')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-heartbeat me-2 text-primary"></i>System Health Check
            </h1>
            <p class="text-muted mb-0">Comprehensive system health monitoring and diagnostics</p>
        </div>
        <div>
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
            <button class="btn btn-primary" onclick="runHealthCheck()">
                <i class="fas fa-sync-alt me-1"></i>Run Health Check
            </button>
        </div>
    </div>

    <!-- Overall Health Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <div class="health-status-icon mb-3">
                        @php
                            $overallStatus = $healthStatus['overall'] ?? 'unknown';
                            $statusConfig = [
                                'healthy' => ['icon' => 'fas fa-check-circle', 'color' => 'success', 'text' => 'System Healthy'],
                                'warning' => ['icon' => 'fas fa-exclamation-triangle', 'color' => 'warning', 'text' => 'System Warning'],
                                'critical' => ['icon' => 'fas fa-times-circle', 'color' => 'danger', 'text' => 'System Critical'],
                                'unknown' => ['icon' => 'fas fa-question-circle', 'color' => 'secondary', 'text' => 'Status Unknown']
                            ];
                            $config = $statusConfig[$overallStatus] ?? $statusConfig['unknown'];
                        @endphp
                        <i class="{{ $config['icon'] }} fa-5x text-{{ $config['color'] }}"></i>
                    </div>
                    <h2 class="text-{{ $config['color'] }}">{{ $config['text'] }}</h2>
                    <p class="text-muted mb-0">Last checked: {{ $healthStatus['last_check'] ?? now()->format('Y-m-d H:i:s') }}</p>
                    @if(isset($healthStatus['uptime']))
                        <p class="text-muted">System uptime: {{ $healthStatus['uptime'] }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Health Check Categories -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-{{ $this->getStatusColor($healthChecks['application'] ?? 'unknown') }} shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-{{ $this->getStatusColor($healthChecks['application'] ?? 'unknown') }} text-uppercase mb-1">
                                Application
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ ucfirst($healthChecks['application'] ?? 'Unknown') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-code fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-{{ $this->getStatusColor($healthChecks['database'] ?? 'unknown') }} shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-{{ $this->getStatusColor($healthChecks['database'] ?? 'unknown') }} text-uppercase mb-1">
                                Database
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ ucfirst($healthChecks['database'] ?? 'Unknown') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-{{ $this->getStatusColor($healthChecks['cache'] ?? 'unknown') }} shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-{{ $this->getStatusColor($healthChecks['cache'] ?? 'unknown') }} text-uppercase mb-1">
                                Cache
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ ucfirst($healthChecks['cache'] ?? 'Unknown') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-memory fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-{{ $this->getStatusColor($healthChecks['storage'] ?? 'unknown') }} shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-{{ $this->getStatusColor($healthChecks['storage'] ?? 'unknown') }} text-uppercase mb-1">
                                Storage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ ucfirst($healthChecks['storage'] ?? 'Unknown') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hdd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Health Checks -->
    <div class="row mb-4">
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cogs me-1"></i>System Components
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($detailedChecks['components']) && count($detailedChecks['components']) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($detailedChecks['components'] as $component)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $component['name'] }}</h6>
                                        <p class="mb-1 text-muted small">{{ $component['description'] ?? '' }}</p>
                                        @if(isset($component['details']))
                                            <small class="text-muted">{{ $component['details'] }}</small>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <span class="badge bg-{{ $this->getStatusColor($component['status']) }} mb-1">
                                            {{ ucfirst($component['status']) }}
                                        </span>
                                        @if(isset($component['response_time']))
                                            <br><small class="text-muted">{{ $component['response_time'] }}ms</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-cogs fa-2x mb-2"></i>
                            <p>No component data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plug me-1"></i>External Services
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($detailedChecks['external_services']) && count($detailedChecks['external_services']) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($detailedChecks['external_services'] as $service)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $service['name'] }}</h6>
                                        <p class="mb-1 text-muted small">{{ $service['url'] ?? '' }}</p>
                                        @if(isset($service['last_check']))
                                            <small class="text-muted">Last checked: {{ $service['last_check'] }}</small>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <span class="badge bg-{{ $this->getStatusColor($service['status']) }} mb-1">
                                            {{ ucfirst($service['status']) }}
                                        </span>
                                        @if(isset($service['response_time']))
                                            <br><small class="text-muted">{{ $service['response_time'] }}ms</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-plug fa-2x mb-2"></i>
                            <p>No external services configured</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Security Checks -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-shield-alt me-1"></i>Security Health Checks
            </h6>
        </div>
        <div class="card-body">
            @if(isset($securityChecks) && count($securityChecks) > 0)
                <div class="row">
                    @foreach($securityChecks as $check)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border-left-{{ $this->getStatusColor($check['status']) }} h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title">{{ $check['name'] }}</h6>
                                            <p class="card-text small text-muted">{{ $check['description'] ?? '' }}</p>
                                        </div>
                                        <span class="badge bg-{{ $this->getStatusColor($check['status']) }}">
                                            {{ ucfirst($check['status']) }}
                                        </span>
                                    </div>
                                    @if(isset($check['recommendation']) && $check['status'] !== 'healthy')
                                        <div class="mt-2">
                                            <small class="text-warning">
                                                <i class="fas fa-lightbulb me-1"></i>{{ $check['recommendation'] }}
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="fas fa-shield-alt fa-2x mb-2"></i>
                    <p>No security checks configured</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-tachometer-alt me-1"></i>Performance Health
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="text-center">
                        <div class="h4 mb-0 font-weight-bold text-{{ $this->getPerformanceColor($performanceMetrics['avg_response_time'] ?? 0) }}">
                            {{ $performanceMetrics['avg_response_time'] ?? '0' }}ms
                        </div>
                        <div class="text-xs font-weight-bold text-uppercase text-muted">Avg Response Time</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="text-center">
                        <div class="h4 mb-0 font-weight-bold text-{{ $this->getPerformanceColor($performanceMetrics['memory_usage'] ?? 0, 'memory') }}">
                            {{ $performanceMetrics['memory_usage'] ?? '0' }}%
                        </div>
                        <div class="text-xs font-weight-bold text-uppercase text-muted">Memory Usage</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="text-center">
                        <div class="h4 mb-0 font-weight-bold text-{{ $this->getPerformanceColor($performanceMetrics['cpu_usage'] ?? 0, 'cpu') }}">
                            {{ $performanceMetrics['cpu_usage'] ?? '0' }}%
                        </div>
                        <div class="text-xs font-weight-bold text-uppercase text-muted">CPU Usage</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="text-center">
                        <div class="h4 mb-0 font-weight-bold text-{{ $this->getPerformanceColor($performanceMetrics['disk_usage'] ?? 0, 'disk') }}">
                            {{ $performanceMetrics['disk_usage'] ?? '0' }}%
                        </div>
                        <div class="text-xs font-weight-bold text-uppercase text-muted">Disk Usage</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Check History -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-history me-1"></i>Health Check History
            </h6>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="exportHealthHistory()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            </div>
        </div>
        <div class="card-body">
            @if(isset($healthHistory) && count($healthHistory) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>Overall Status</th>
                                <th>Application</th>
                                <th>Database</th>
                                <th>Cache</th>
                                <th>Storage</th>
                                <th>Response Time</th>
                                <th>Issues</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($healthHistory as $history)
                                <tr>
                                    <td>
                                        <small>{{ $history['timestamp'] }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getStatusColor($history['overall_status']) }}">
                                            {{ ucfirst($history['overall_status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getStatusColor($history['application']) }}">
                                            {{ ucfirst($history['application']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getStatusColor($history['database']) }}">
                                            {{ ucfirst($history['database']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getStatusColor($history['cache']) }}">
                                            {{ ucfirst($history['cache']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getStatusColor($history['storage']) }}">
                                            {{ ucfirst($history['storage']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $history['response_time'] ?? '-' }}ms</small>
                                    </td>
                                    <td>
                                        @if(isset($history['issues']) && $history['issues'] > 0)
                                            <span class="badge bg-warning">{{ $history['issues'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="fas fa-history fa-2x mb-2"></i>
                    <p>No health check history available</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Health Check Running Modal -->
<div class="modal fade" id="healthCheckModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Running Health Check</h5>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Please wait while we check your system health...</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" id="healthCheckProgress"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function runHealthCheck() {
    const modal = new bootstrap.Modal(document.getElementById('healthCheckModal'));
    modal.show();
    
    const progressBar = document.getElementById('healthCheckProgress');
    let progress = 0;
    
    // Simulate health check progress
    const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress > 100) progress = 100;
        
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
            clearInterval(interval);
            setTimeout(() => {
                modal.hide();
                location.reload();
            }, 1000);
        }
    }, 500);
}

function exportHealthHistory() {
    const exportUrl = `{{ route('admin.monitoring.health') }}?export=csv`;
    window.open(exportUrl, '_blank');
}

// Auto-refresh every 5 minutes
setInterval(() => {
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 300000);
</script>
@endpush

@php
// Helper functions for health check display
function getStatusColor($status) {
    $colors = [
        'healthy' => 'success',
        'warning' => 'warning', 
        'critical' => 'danger',
        'unknown' => 'secondary',
        'error' => 'danger',
        'ok' => 'success',
        'fail' => 'danger'
    ];
    
    return $colors[$status] ?? 'secondary';
}

function getPerformanceColor($value, $type = 'response_time') {
    switch ($type) {
        case 'response_time':
            if ($value < 200) return 'success';
            if ($value < 500) return 'warning';
            return 'danger';
            
        case 'memory':
        case 'cpu':
        case 'disk':
            if ($value < 70) return 'success';
            if ($value < 90) return 'warning';
            return 'danger';
            
        default:
            return 'secondary';
    }
}
@endphp
@endsection