@extends('layouts.app')

@section('title', 'System Metrics')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-line me-2 text-primary"></i>System Metrics
            </h1>
            <p class="text-muted mb-0">Real-time system performance and resource monitoring</p>
        </div>
        <div>
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
            <button class="btn btn-primary" onclick="refreshMetrics()">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
        </div>
    </div>

    <!-- System Overview -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Server Uptime
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['uptime'] ?? '0 days' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-server fa-2x text-gray-300"></i>
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
                                CPU Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['cpu_usage'] ?? '0' }}%</div>
                            <div class="progress progress-sm mr-2">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: {{ $metrics['cpu_usage'] ?? 0 }}%"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-microchip fa-2x text-gray-300"></i>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['memory_usage'] ?? '0' }}%</div>
                            <div class="progress progress-sm mr-2">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: {{ $metrics['memory_usage'] ?? 0 }}%"></div>
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
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Disk Usage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['disk_usage'] ?? '0' }}%</div>
                            <div class="progress progress-sm mr-2">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: {{ $metrics['disk_usage'] ?? 0 }}%"></div>
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

    <!-- Performance Charts -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">System Performance Trends</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <div class="dropdown-header">Time Range:</div>
                            <a class="dropdown-item" href="?range=1h">Last Hour</a>
                            <a class="dropdown-item" href="?range=6h">Last 6 Hours</a>
                            <a class="dropdown-item" href="?range=24h">Last 24 Hours</a>
                            <a class="dropdown-item" href="?range=7d">Last 7 Days</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Resource Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="resourceChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> Application
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Database
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-info"></i> Cache
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> Files
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Metrics -->
    <div class="row mb-4">
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-database me-1"></i>Database Performance
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-primary h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Active Connections
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $metrics['db_connections'] ?? 0 }}/{{ $metrics['db_max_connections'] ?? 100 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-success h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Avg Query Time
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $metrics['avg_query_time'] ?? '0' }}ms
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-info h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Slow Queries
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $metrics['slow_queries'] ?? 0 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-warning h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        DB Size
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $metrics['db_size'] ?? '0 MB' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tachometer-alt me-1"></i>Application Metrics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-primary h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Active Users
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $metrics['active_users'] ?? 0 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-success h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Requests/Min
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $metrics['requests_per_minute'] ?? 0 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-info h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Cache Hit Rate
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $metrics['cache_hit_rate'] ?? '0' }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-warning h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Queue Jobs
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        {{ $metrics['queue_jobs'] ?? 0 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Processes -->
    <div class="row mb-4">
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-1"></i>Top Processes by CPU
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($topProcesses) && count($topProcesses) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Process</th>
                                        <th>CPU %</th>
                                        <th>Memory</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topProcesses as $process)
                                        <tr>
                                            <td>{{ $process['name'] }}</td>
                                            <td>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-{{ $process['cpu'] > 80 ? 'danger' : ($process['cpu'] > 50 ? 'warning' : 'success') }}" 
                                                         style="width: {{ $process['cpu'] }}%"></div>
                                                </div>
                                                <small>{{ $process['cpu'] }}%</small>
                                            </td>
                                            <td>{{ $process['memory'] }}</td>
                                            <td>
                                                <span class="badge bg-{{ $process['status'] === 'running' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($process['status']) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-cogs fa-2x mb-2"></i>
                            <p>No process data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-network-wired me-1"></i>Network Activity
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Incoming Traffic
                            </div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                {{ $metrics['network_in'] ?? '0 MB/s' }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Outgoing Traffic
                            </div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                {{ $metrics['network_out'] ?? '0 MB/s' }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-area" style="height: 200px;">
                        <canvas id="networkChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-info-circle me-1"></i>System Information
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <h6>Server Details</h6>
                    <table class="table table-sm table-borderless">
                        <tr><td><strong>OS:</strong></td><td>{{ $systemInfo['os'] ?? 'Unknown' }}</td></tr>
                        <tr><td><strong>PHP Version:</strong></td><td>{{ $systemInfo['php_version'] ?? PHP_VERSION }}</td></tr>
                        <tr><td><strong>Laravel Version:</strong></td><td>{{ $systemInfo['laravel_version'] ?? app()->version() }}</td></tr>
                        <tr><td><strong>Server Software:</strong></td><td>{{ $systemInfo['server_software'] ?? $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-3">
                    <h6>Hardware</h6>
                    <table class="table table-sm table-borderless">
                        <tr><td><strong>CPU Cores:</strong></td><td>{{ $systemInfo['cpu_cores'] ?? 'Unknown' }}</td></tr>
                        <tr><td><strong>Total Memory:</strong></td><td>{{ $systemInfo['total_memory'] ?? 'Unknown' }}</td></tr>
                        <tr><td><strong>Total Disk:</strong></td><td>{{ $systemInfo['total_disk'] ?? 'Unknown' }}</td></tr>
                        <tr><td><strong>Architecture:</strong></td><td>{{ $systemInfo['architecture'] ?? php_uname('m') }}</td></tr>
                    </table>
                </div>
                <div class="col-md-3">
                    <h6>Database</h6>
                    <table class="table table-sm table-borderless">
                        <tr><td><strong>Type:</strong></td><td>{{ $systemInfo['db_type'] ?? config('database.default') }}</td></tr>
                        <tr><td><strong>Version:</strong></td><td>{{ $systemInfo['db_version'] ?? 'Unknown' }}</td></tr>
                        <tr><td><strong>Host:</strong></td><td>{{ $systemInfo['db_host'] ?? config('database.connections.mysql.host') }}</td></tr>
                        <tr><td><strong>Port:</strong></td><td>{{ $systemInfo['db_port'] ?? config('database.connections.mysql.port') }}</td></tr>
                    </table>
                </div>
                <div class="col-md-3">
                    <h6>Application</h6>
                    <table class="table table-sm table-borderless">
                        <tr><td><strong>Environment:</strong></td><td><span class="badge bg-{{ app()->environment() === 'production' ? 'success' : 'warning' }}">{{ ucfirst(app()->environment()) }}</span></td></tr>
                        <tr><td><strong>Debug Mode:</strong></td><td><span class="badge bg-{{ config('app.debug') ? 'danger' : 'success' }}">{{ config('app.debug') ? 'Enabled' : 'Disabled' }}</span></td></tr>
                        <tr><td><strong>Cache Driver:</strong></td><td>{{ config('cache.default') }}</td></tr>
                        <tr><td><strong>Queue Driver:</strong></td><td>{{ config('queue.default') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Performance Chart
const performanceCtx = document.getElementById('performanceChart').getContext('2d');
const performanceChart = new Chart(performanceCtx, {
    type: 'line',
    data: {
        labels: @json($chartData['labels'] ?? []),
        datasets: [
            {
                label: 'CPU Usage (%)',
                data: @json($chartData['cpu'] ?? []),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1,
                yAxisID: 'y'
            },
            {
                label: 'Memory Usage (%)',
                data: @json($chartData['memory'] ?? []),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.1,
                yAxisID: 'y'
            },
            {
                label: 'Response Time (ms)',
                data: @json($chartData['response_time'] ?? []),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.1,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Time'
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Usage (%)'
                },
                min: 0,
                max: 100
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Response Time (ms)'
                },
                grid: {
                    drawOnChartArea: false,
                },
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

// Resource Distribution Chart
const resourceCtx = document.getElementById('resourceChart').getContext('2d');
const resourceChart = new Chart(resourceCtx, {
    type: 'doughnut',
    data: {
        labels: ['Application', 'Database', 'Cache', 'Files'],
        datasets: [{
            data: @json($resourceData ?? [40, 30, 20, 10]),
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
            hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#f4b619'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        cutout: '80%',
    },
});

// Network Chart
const networkCtx = document.getElementById('networkChart').getContext('2d');
const networkChart = new Chart(networkCtx, {
    type: 'line',
    data: {
        labels: @json($networkData['labels'] ?? []),
        datasets: [
            {
                label: 'Incoming (MB/s)',
                data: @json($networkData['incoming'] ?? []),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1,
                fill: true
            },
            {
                label: 'Outgoing (MB/s)',
                data: @json($networkData['outgoing'] ?? []),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.1,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'MB/s'
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

function refreshMetrics() {
    location.reload();
}

// Auto-refresh every 30 seconds
setInterval(() => {
    refreshMetrics();
}, 30000);

// Real-time updates for key metrics (if WebSocket is available)
if (typeof io !== 'undefined') {
    const socket = io();
    
    socket.on('metrics-update', function(data) {
        // Update CPU usage
        if (data.cpu_usage !== undefined) {
            document.querySelector('[data-metric="cpu"] .h5').textContent = data.cpu_usage + '%';
            document.querySelector('[data-metric="cpu"] .progress-bar').style.width = data.cpu_usage + '%';
        }
        
        // Update Memory usage
        if (data.memory_usage !== undefined) {
            document.querySelector('[data-metric="memory"] .h5').textContent = data.memory_usage + '%';
            document.querySelector('[data-metric="memory"] .progress-bar').style.width = data.memory_usage + '%';
        }
        
        // Update charts with new data
        if (data.chart_data) {
            performanceChart.data.labels.push(data.chart_data.timestamp);
            performanceChart.data.datasets[0].data.push(data.cpu_usage);
            performanceChart.data.datasets[1].data.push(data.memory_usage);
            performanceChart.data.datasets[2].data.push(data.response_time);
            
            // Keep only last 20 data points
            if (performanceChart.data.labels.length > 20) {
                performanceChart.data.labels.shift();
                performanceChart.data.datasets.forEach(dataset => {
                    dataset.data.shift();
                });
            }
            
            performanceChart.update('none');
        }
    });
}
</script>
@endpush
@endsection