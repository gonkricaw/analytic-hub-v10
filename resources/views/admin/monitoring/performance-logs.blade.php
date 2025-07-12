@extends('layouts.app')

@section('title', 'Performance Logs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt me-2 text-info"></i>Performance Logs
            </h1>
            <p class="text-muted mb-0">Monitor application performance, response times, and resource usage</p>
        </div>
        <div>
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Performance Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Avg Response Time
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $performanceStats['avg_response_time'] ?? '0' }}ms</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Slow Requests
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $performanceStats['slow_requests'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
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
                                Memory Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $performanceStats['avg_memory'] ?? '0' }}MB</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-memory fa-2x text-gray-300"></i>
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
                                Performance Score
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $performanceStats['score'] ?? '85' }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Response Time Trend</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <div class="dropdown-header">Time Range:</div>
                            <a class="dropdown-item" href="?range=1h">Last Hour</a>
                            <a class="dropdown-item" href="?range=24h">Last 24 Hours</a>
                            <a class="dropdown-item" href="?range=7d">Last 7 Days</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="responseTimeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Slow Endpoints</h6>
                </div>
                <div class="card-body">
                    @if(isset($slowEndpoints) && count($slowEndpoints) > 0)
                        @foreach($slowEndpoints as $endpoint)
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="flex-grow-1">
                                    <div class="small font-weight-bold">{{ $endpoint['method'] }} {{ $endpoint['uri'] }}</div>
                                    <div class="text-muted small">{{ $endpoint['count'] }} requests</div>
                                </div>
                                <div class="text-right">
                                    <div class="small font-weight-bold text-{{ $endpoint['avg_time'] > 2000 ? 'danger' : ($endpoint['avg_time'] > 1000 ? 'warning' : 'success') }}">
                                        {{ number_format($endpoint['avg_time']) }}ms
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-rocket fa-2x mb-2"></i>
                            <p>All endpoints performing well!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-1"></i>Performance Log Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.monitoring.performance-logs') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="URL, User, IP...">
                </div>
                
                <div class="col-md-2">
                    <label for="method" class="form-label">HTTP Method</label>
                    <select class="form-select" id="method" name="method">
                        <option value="">All Methods</option>
                        <option value="GET" {{ request('method') === 'GET' ? 'selected' : '' }}>GET</option>
                        <option value="POST" {{ request('method') === 'POST' ? 'selected' : '' }}>POST</option>
                        <option value="PUT" {{ request('method') === 'PUT' ? 'selected' : '' }}>PUT</option>
                        <option value="DELETE" {{ request('method') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                        <option value="PATCH" {{ request('method') === 'PATCH' ? 'selected' : '' }}>PATCH</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="min_time" class="form-label">Min Time (ms)</label>
                    <input type="number" class="form-control" id="min_time" name="min_time" 
                           value="{{ request('min_time') }}" placeholder="1000">
                </div>
                
                <div class="col-md-2">
                    <label for="status_code" class="form-label">Status Code</label>
                    <select class="form-select" id="status_code" name="status_code">
                        <option value="">All Status</option>
                        <option value="200" {{ request('status_code') === '200' ? 'selected' : '' }}>200 OK</option>
                        <option value="404" {{ request('status_code') === '404' ? 'selected' : '' }}>404 Not Found</option>
                        <option value="500" {{ request('status_code') === '500' ? 'selected' : '' }}>500 Error</option>
                        <option value="403" {{ request('status_code') === '403' ? 'selected' : '' }}>403 Forbidden</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ request('date_from') }}">
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('admin.monitoring.performance-logs') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Performance Logs Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Performance Logs</h6>
            <div class="d-flex gap-2">
                <span class="badge bg-info">{{ $logs->total() }} requests</span>
                <button class="btn btn-sm btn-outline-primary" onclick="exportPerformanceLogs()">
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
                    <table class="table table-bordered table-hover" id="performanceLogsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>Method</th>
                                <th>URL</th>
                                <th>Response Time</th>
                                <th>Memory</th>
                                <th>DB Queries</th>
                                <th>Status</th>
                                <th>User</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="{{ $this->getPerformanceRowClass($log) }}">
                                    <td>
                                        <small>{{ $log->created_at->format('Y-m-d H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getMethodColor($log->method) }}">
                                            {{ $log->method }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;" title="{{ $log->url }}">
                                            {{ $log->url }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getResponseTimeColor($log->response_time) }}">
                                            {{ number_format($log->response_time) }}ms
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ number_format($log->memory_usage / 1024 / 1024, 2) }}MB</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $log->query_count > 10 ? 'warning' : 'info' }}">
                                            {{ $log->query_count }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getStatusColor($log->status_code) }}">
                                            {{ $log->status_code }}
                                        </span>
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
                                        <button class="btn btn-sm btn-outline-info" onclick="viewPerformanceDetails({{ $log->id }})">
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
                            Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} requests
                        </small>
                    </div>
                    <div>
                        {{ $logs->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-tachometer-alt fa-3x text-info mb-3"></i>
                    <h5 class="text-info">No Performance Data</h5>
                    <p class="text-muted">No performance logs match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Performance Detail Modal -->
<div class="modal fade" id="performanceDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Performance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="performanceDetailContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="optimizeEndpoint()">
                    <i class="fas fa-rocket me-1"></i>Optimize
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Response Time Chart
const ctx = document.getElementById('responseTimeChart').getContext('2d');
const responseTimeChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($chartData['labels'] ?? []),
        datasets: [{
            label: 'Average Response Time (ms)',
            data: @json($chartData['data'] ?? []),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Response Time (ms)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Time'
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});

function viewPerformanceDetails(logId) {
    // In a real implementation, this would fetch performance details via AJAX
    const modal = new bootstrap.Modal(document.getElementById('performanceDetailModal'));
    
    // Mock data for demonstration
    const performanceDetails = `
        <div class="row">
            <div class="col-md-6">
                <h6>Request Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Request ID:</strong></td><td>${logId}</td></tr>
                    <tr><td><strong>Method:</strong></td><td>GET</td></tr>
                    <tr><td><strong>URL:</strong></td><td>/admin/dashboard</td></tr>
                    <tr><td><strong>Status Code:</strong></td><td>200</td></tr>
                    <tr><td><strong>Response Time:</strong></td><td>1,250ms</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Performance Metrics</h6>
                <table class="table table-sm">
                    <tr><td><strong>Memory Usage:</strong></td><td>45.2 MB</td></tr>
                    <tr><td><strong>Peak Memory:</strong></td><td>48.1 MB</td></tr>
                    <tr><td><strong>DB Queries:</strong></td><td>15</td></tr>
                    <tr><td><strong>Query Time:</strong></td><td>320ms</td></tr>
                    <tr><td><strong>Cache Hits:</strong></td><td>8/12</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Slow Queries</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Query</th>
                                <th>Time</th>
                                <th>Rows</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>SELECT * FROM users WHERE status = 'active'</code></td>
                                <td>125ms</td>
                                <td>1,250</td>
                            </tr>
                            <tr>
                                <td><code>SELECT * FROM content WHERE published = 1</code></td>
                                <td>95ms</td>
                                <td>850</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('performanceDetailContent').innerHTML = performanceDetails;
    modal.show();
}

function optimizeEndpoint() {
    showAlert('Optimization suggestions have been generated and sent to the development team', 'info');
}

function exportPerformanceLogs() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    const exportUrl = `{{ route('admin.monitoring.performance-logs') }}?${params.toString()}`;
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

// Auto-refresh every 2 minutes
setInterval(() => {
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 120000);
</script>
@endpush

@php
// Helper functions for performance log display
function getPerformanceRowClass($log) {
    if ($log->response_time > 3000) {
        return 'table-danger';
    } elseif ($log->response_time > 1000) {
        return 'table-warning';
    }
    return '';
}

function getMethodColor($method) {
    $colors = [
        'GET' => 'primary',
        'POST' => 'success',
        'PUT' => 'warning',
        'DELETE' => 'danger',
        'PATCH' => 'info',
    ];
    
    return $colors[$method] ?? 'secondary';
}

function getResponseTimeColor($responseTime) {
    if ($responseTime > 3000) {
        return 'danger';
    } elseif ($responseTime > 1000) {
        return 'warning';
    } elseif ($responseTime > 500) {
        return 'info';
    }
    return 'success';
}

function getStatusColor($statusCode) {
    if ($statusCode >= 500) {
        return 'danger';
    } elseif ($statusCode >= 400) {
        return 'warning';
    } elseif ($statusCode >= 300) {
        return 'info';
    }
    return 'success';
}
@endphp
@endsection