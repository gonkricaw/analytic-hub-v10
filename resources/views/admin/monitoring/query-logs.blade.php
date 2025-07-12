@extends('layouts.app')

@section('title', 'Query Logs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-database me-2 text-success"></i>Query Logs
            </h1>
            <p class="text-muted mb-0">Monitor database queries, performance, and optimization opportunities</p>
        </div>
        <div>
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Query Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Queries
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $queryStats['total_queries'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
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
                                Slow Queries
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $queryStats['slow_queries'] ?? 0 }}</div>
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
                                Avg Query Time
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $queryStats['avg_time'] ?? '0' }}ms</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Failed Queries
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $queryStats['failed_queries'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Query Performance Chart -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Query Performance Trend</h6>
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
                        <canvas id="queryPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Most Frequent Queries</h6>
                </div>
                <div class="card-body">
                    @if(isset($frequentQueries) && count($frequentQueries) > 0)
                        @foreach($frequentQueries as $query)
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="flex-grow-1">
                                    <div class="small font-weight-bold text-truncate" style="max-width: 200px;" title="{{ $query['sql'] }}">
                                        {{ $query['type'] }} {{ $query['table'] }}
                                    </div>
                                    <div class="text-muted small">{{ $query['count'] }} executions</div>
                                </div>
                                <div class="text-right">
                                    <div class="small font-weight-bold text-{{ $query['avg_time'] > 100 ? 'danger' : ($query['avg_time'] > 50 ? 'warning' : 'success') }}">
                                        {{ number_format($query['avg_time'], 2) }}ms
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <p>No query data available</p>
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
                <i class="fas fa-filter me-1"></i>Query Log Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.monitoring.query-logs') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Table, SQL, Connection...">
                </div>
                
                <div class="col-md-2">
                    <label for="query_type" class="form-label">Query Type</label>
                    <select class="form-select" id="query_type" name="query_type">
                        <option value="">All Types</option>
                        <option value="SELECT" {{ request('query_type') === 'SELECT' ? 'selected' : '' }}>SELECT</option>
                        <option value="INSERT" {{ request('query_type') === 'INSERT' ? 'selected' : '' }}>INSERT</option>
                        <option value="UPDATE" {{ request('query_type') === 'UPDATE' ? 'selected' : '' }}>UPDATE</option>
                        <option value="DELETE" {{ request('query_type') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                        <option value="CREATE" {{ request('query_type') === 'CREATE' ? 'selected' : '' }}>CREATE</option>
                        <option value="ALTER" {{ request('query_type') === 'ALTER' ? 'selected' : '' }}>ALTER</option>
                        <option value="DROP" {{ request('query_type') === 'DROP' ? 'selected' : '' }}>DROP</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="min_time" class="form-label">Min Time (ms)</label>
                    <input type="number" class="form-control" id="min_time" name="min_time" 
                           value="{{ request('min_time') }}" placeholder="100" step="0.01">
                </div>
                
                <div class="col-md-2">
                    <label for="connection" class="form-label">Connection</label>
                    <select class="form-select" id="connection" name="connection">
                        <option value="">All Connections</option>
                        <option value="mysql" {{ request('connection') === 'mysql' ? 'selected' : '' }}>MySQL</option>
                        <option value="sqlite" {{ request('connection') === 'sqlite' ? 'selected' : '' }}>SQLite</option>
                        <option value="pgsql" {{ request('connection') === 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
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
                        <a href="{{ route('admin.monitoring.query-logs') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Query Logs Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Database Query Logs</h6>
            <div class="d-flex gap-2">
                <span class="badge bg-info">{{ $logs->total() }} queries</span>
                <button class="btn btn-sm btn-outline-primary" onclick="exportQueryLogs()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <button class="btn btn-sm btn-outline-warning" onclick="analyzeQueries()">
                    <i class="fas fa-chart-line me-1"></i>Analyze
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="queryLogsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>Type</th>
                                <th>SQL Query</th>
                                <th>Execution Time</th>
                                <th>Rows</th>
                                <th>Connection</th>
                                <th>Bindings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="{{ $this->getQueryRowClass($log) }}">
                                    <td>
                                        <small>{{ $log->created_at->format('Y-m-d H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getQueryTypeColor($log->query_type) }}">
                                            {{ $log->query_type }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="query-preview" style="max-width: 400px;">
                                            <code class="text-truncate d-block" title="{{ $log->sql }}">
                                                {{ $this->formatSqlPreview($log->sql) }}
                                            </code>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getExecutionTimeColor($log->execution_time) }}">
                                            {{ number_format($log->execution_time, 2) }}ms
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ number_format($log->rows_affected ?? 0) }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $log->connection_name }}</span>
                                    </td>
                                    <td>
                                        @if($log->bindings)
                                            <button class="btn btn-sm btn-outline-info" onclick="showBindings({{ $log->id }})">
                                                <i class="fas fa-eye"></i> {{ count(json_decode($log->bindings, true) ?? []) }}
                                            </button>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-info" onclick="viewQueryDetails({{ $log->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="explainQuery({{ $log->id }})">
                                                <i class="fas fa-search"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="optimizeQuery({{ $log->id }})">
                                                <i class="fas fa-rocket"></i>
                                            </button>
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
                            Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} queries
                        </small>
                    </div>
                    <div>
                        {{ $logs->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-database fa-3x text-success mb-3"></i>
                    <h5 class="text-success">No Query Data</h5>
                    <p class="text-muted">No database queries match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Query Detail Modal -->
<div class="modal fade" id="queryDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Query Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="queryDetailContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info" onclick="copyQuery()">
                    <i class="fas fa-copy me-1"></i>Copy SQL
                </button>
                <button type="button" class="btn btn-warning" onclick="explainCurrentQuery()">
                    <i class="fas fa-search me-1"></i>Explain
                </button>
                <button type="button" class="btn btn-success" onclick="optimizeCurrentQuery()">
                    <i class="fas fa-rocket me-1"></i>Optimize
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Query Performance Chart
const ctx = document.getElementById('queryPerformanceChart').getContext('2d');
const queryPerformanceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($chartData['labels'] ?? []),
        datasets: [
            {
                label: 'Average Query Time (ms)',
                data: @json($chartData['avg_time'] ?? []),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            },
            {
                label: 'Query Count',
                data: @json($chartData['query_count'] ?? []),
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
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Query Time (ms)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Query Count'
                },
                grid: {
                    drawOnChartArea: false,
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

let currentQueryId = null;
let currentQuerySql = null;

function viewQueryDetails(queryId) {
    currentQueryId = queryId;
    
    // In a real implementation, this would fetch query details via AJAX
    const modal = new bootstrap.Modal(document.getElementById('queryDetailModal'));
    
    // Mock data for demonstration
    const queryDetails = `
        <div class="row">
            <div class="col-md-6">
                <h6>Query Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Query ID:</strong></td><td>${queryId}</td></tr>
                    <tr><td><strong>Type:</strong></td><td>SELECT</td></tr>
                    <tr><td><strong>Connection:</strong></td><td>mysql</td></tr>
                    <tr><td><strong>Execution Time:</strong></td><td>125.45ms</td></tr>
                    <tr><td><strong>Rows Affected:</strong></td><td>1,250</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Performance Metrics</h6>
                <table class="table table-sm">
                    <tr><td><strong>Memory Usage:</strong></td><td>2.5 MB</td></tr>
                    <tr><td><strong>CPU Time:</strong></td><td>98ms</td></tr>
                    <tr><td><strong>I/O Time:</strong></td><td>27ms</td></tr>
                    <tr><td><strong>Cache Hit:</strong></td><td>Yes</td></tr>
                    <tr><td><strong>Index Used:</strong></td><td>PRIMARY, idx_status</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>SQL Query</h6>
                <div class="bg-dark text-light p-3 rounded">
                    <pre id="sqlQuery" style="color: #f8f9fa; margin: 0;">SELECT u.id, u.name, u.email, u.status, p.name as profile_name\nFROM users u\nLEFT JOIN profiles p ON u.id = p.user_id\nWHERE u.status = 'active'\nAND u.created_at >= '2024-01-01'\nORDER BY u.created_at DESC\nLIMIT 50</pre>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Query Bindings</h6>
                <div class="bg-light p-3 rounded">
                    <pre>["active", "2024-01-01", 50]</pre>
                </div>
            </div>
        </div>
    `;
    
    currentQuerySql = "SELECT u.id, u.name, u.email, u.status, p.name as profile_name\nFROM users u\nLEFT JOIN profiles p ON u.id = p.user_id\nWHERE u.status = 'active'\nAND u.created_at >= '2024-01-01'\nORDER BY u.created_at DESC\nLIMIT 50";
    
    document.getElementById('queryDetailContent').innerHTML = queryDetails;
    modal.show();
}

function showBindings(queryId) {
    // In a real implementation, this would show query bindings
    showAlert('Query bindings displayed in the query details modal', 'info');
    viewQueryDetails(queryId);
}

function explainQuery(queryId) {
    showAlert('Query execution plan analysis initiated', 'info');
}

function optimizeQuery(queryId) {
    showAlert('Query optimization suggestions generated', 'success');
}

function copyQuery() {
    if (currentQuerySql) {
        navigator.clipboard.writeText(currentQuerySql).then(() => {
            showAlert('SQL query copied to clipboard', 'success');
        }).catch(() => {
            showAlert('Failed to copy query', 'danger');
        });
    }
}

function explainCurrentQuery() {
    if (currentQueryId) {
        showAlert('Generating execution plan for current query...', 'info');
    }
}

function optimizeCurrentQuery() {
    if (currentQueryId) {
        showAlert('Analyzing query for optimization opportunities...', 'info');
    }
}

function exportQueryLogs() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    const exportUrl = `{{ route('admin.monitoring.query-logs') }}?${params.toString()}`;
    window.open(exportUrl, '_blank');
}

function analyzeQueries() {
    showAlert('Starting comprehensive query analysis...', 'info');
    // In a real implementation, this would trigger query analysis
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

// Auto-refresh every 3 minutes
setInterval(() => {
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 180000);
</script>
@endpush

@php
// Helper functions for query log display
function getQueryRowClass($log) {
    if ($log->execution_time > 1000) {
        return 'table-danger';
    } elseif ($log->execution_time > 500) {
        return 'table-warning';
    }
    return '';
}

function getQueryTypeColor($queryType) {
    $colors = [
        'SELECT' => 'primary',
        'INSERT' => 'success',
        'UPDATE' => 'warning',
        'DELETE' => 'danger',
        'CREATE' => 'info',
        'ALTER' => 'secondary',
        'DROP' => 'dark',
    ];
    
    return $colors[$queryType] ?? 'secondary';
}

function getExecutionTimeColor($executionTime) {
    if ($executionTime > 1000) {
        return 'danger';
    } elseif ($executionTime > 500) {
        return 'warning';
    } elseif ($executionTime > 100) {
        return 'info';
    }
    return 'success';
}

function formatSqlPreview($sql) {
    // Truncate and clean SQL for preview
    $preview = preg_replace('/\s+/', ' ', $sql);
    return strlen($preview) > 80 ? substr($preview, 0, 80) . '...' : $preview;
}
@endphp
@endsection