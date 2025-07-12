@extends('layouts.admin')

@section('title', 'Popular Content Analytics')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-line text-primary me-2"></i>
                Popular Content Analytics
            </h1>
            <p class="text-muted mb-0">Comprehensive analytics for content performance and popularity</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="refreshDashboard">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-1"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportAnalytics('csv', '30_days')">CSV (30 days)</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportAnalytics('csv', '7_days')">CSV (7 days)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="exportAnalytics('xlsx', '30_days')">Excel (30 days)</a></li>
                </ul>
            </div>
        </div>
    </div>

    @if(isset($error))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Dashboard Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Content
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['total_content'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
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
                                Views (30 Days)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['total_views_30_days'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-gray-300"></i>
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
                                Trending Content
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['trending_count'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                Avg Engagement Score
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['avg_engagement_score'] ?? 0, 1) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-heart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Popular Content Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar me-2"></i>
                        Popular Content Overview
                    </h6>
                    <div class="dropdown no-arrow">
                        <select class="form-select form-select-sm" id="popularContentPeriod" onchange="updatePopularContentChart()">
                            <option value="24_hours">Last 24 Hours</option>
                            <option value="7_days">Last 7 Days</option>
                            <option value="30_days" selected>Last 30 Days</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="popularContentChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engagement Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>
                        Engagement Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="engagementChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Tables Row -->
    <div class="row mb-4">
        <!-- Popular Content Table -->
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-fire me-2"></i>
                        Most Popular Content
                    </h6>
                    <div class="dropdown no-arrow">
                        <select class="form-select form-select-sm" id="popularTablePeriod" onchange="updatePopularTable()">
                            <option value="24_hours">Today</option>
                            <option value="7_days">This Week</option>
                            <option value="30_days" selected>This Month</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="popularContentTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Views</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody id="popularContentTableBody">
                                @if(isset($popularMonth) && $popularMonth->count() > 0)
                                    @foreach($popularMonth->take(10) as $content)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($content->is_featured)
                                                        <i class="fas fa-star text-warning me-2" title="Featured"></i>
                                                    @endif
                                                    <div>
                                                        <div class="font-weight-bold">{{ Str::limit($content->title, 40) }}</div>
                                                        <div class="text-muted small">{{ $content->category }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ ucfirst($content->type) }}</span>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold">{{ number_format($content->views_last_30_days ?? 0) }}</div>
                                                <div class="text-muted small">30 days</div>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold">{{ number_format($content->engagement_score ?? 0, 1) }}</div>
                                                @if($content->is_trending)
                                                    <i class="fas fa-trending-up text-success ms-1" title="Trending"></i>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No popular content data available</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trending Content Table -->
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line me-2"></i>
                        Trending Content
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="trendingContentTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Growth</th>
                                    <th>Views (7d)</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($trendingContent) && $trendingContent->count() > 0)
                                    @foreach($trendingContent->take(10) as $content)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-trending-up text-success me-2"></i>
                                                    <div>
                                                        <div class="font-weight-bold">{{ Str::limit($content->title, 35) }}</div>
                                                        <div class="text-muted small">{{ $content->type }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-success">
                                                    +{{ number_format($content->growth_rate_percentage ?? 0, 1) }}%
                                                </span>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold">{{ number_format($content->views_last_7_days ?? 0) }}</div>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold">{{ number_format($content->engagement_score ?? 0, 1) }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No trending content data available</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics Row -->
    <div class="row mb-4">
        <!-- Content by Category -->
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tags me-2"></i>
                        Performance by Category
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="categoryChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content by Type -->
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-layer-group me-2"></i>
                        Performance by Type
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="typeChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Trends -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area me-2"></i>
                        Performance Trends (Last 30 Days)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="trendsChart" width="400" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-3">Loading analytics data...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.chart-area {
    position: relative;
    height: 300px;
    width: 100%;
}

.chart-pie {
    position: relative;
    height: 250px;
    width: 100%;
}

.chart-bar {
    position: relative;
    height: 250px;
    width: 100%;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.badge-secondary {
    background-color: #6c757d;
}

.badge-success {
    background-color: #28a745;
}

.text-xs {
    font-size: 0.7rem;
}

.font-weight-bold {
    font-weight: 700;
}

.text-gray-800 {
    color: #5a5c69;
}

.text-gray-300 {
    color: #dddfeb;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart instances
let popularContentChart;
let engagementChart;
let categoryChart;
let typeChart;
let trendsChart;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    setupEventListeners();
});

/**
 * Initialize all charts with data
 */
function initializeCharts() {
    initializePopularContentChart();
    initializeEngagementChart();
    initializeCategoryChart();
    initializeTypeChart();
    initializeTrendsChart();
}

/**
 * Initialize popular content chart
 */
function initializePopularContentChart() {
    const ctx = document.getElementById('popularContentChart').getContext('2d');
    
    @if(isset($popularMonth) && $popularMonth->count() > 0)
        const popularData = {
            labels: {!! json_encode($popularMonth->take(10)->pluck('title')->map(function($title) { return Str::limit($title, 20); })) !!},
            datasets: [{
                label: 'Views (30 days)',
                data: {!! json_encode($popularMonth->take(10)->pluck('views_last_30_days')) !!},
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                fill: true
            }]
        };
    @else
        const popularData = {
            labels: [],
            datasets: [{
                label: 'Views (30 days)',
                data: [],
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                fill: true
            }]
        };
    @endif
    
    popularContentChart = new Chart(ctx, {
        type: 'line',
        data: popularData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    display: true,
                    ticks: {
                        maxRotation: 45
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Initialize engagement distribution chart
 */
function initializeEngagementChart() {
    const ctx = document.getElementById('engagementChart').getContext('2d');
    
    @if(isset($engagementAnalytics['engagement_distribution']) && $engagementAnalytics['engagement_distribution']->count() > 0)
        const engagementData = {
            labels: {!! json_encode($engagementAnalytics['engagement_distribution']->pluck('engagement_level')) !!},
            datasets: [{
                data: {!! json_encode($engagementAnalytics['engagement_distribution']->pluck('content_count')) !!},
                backgroundColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e'
                ],
                borderWidth: 2
            }]
        };
    @else
        const engagementData = {
            labels: ['No Data'],
            datasets: [{
                data: [1],
                backgroundColor: ['#e3e6f0'],
                borderWidth: 2
            }]
        };
    @endif
    
    engagementChart = new Chart(ctx, {
        type: 'doughnut',
        data: engagementData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

/**
 * Initialize category performance chart
 */
function initializeCategoryChart() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    @if(isset($performanceMetrics['top_by_category']) && $performanceMetrics['top_by_category']->count() > 0)
        const categoryData = {
            labels: {!! json_encode($performanceMetrics['top_by_category']->pluck('category')) !!},
            datasets: [{
                label: 'Total Views',
                data: {!! json_encode($performanceMetrics['top_by_category']->pluck('total_views')) !!},
                backgroundColor: 'rgba(28, 200, 138, 0.8)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 1
            }]
        };
    @else
        const categoryData = {
            labels: ['No Data'],
            datasets: [{
                label: 'Total Views',
                data: [0],
                backgroundColor: 'rgba(227, 230, 240, 0.8)',
                borderColor: 'rgba(227, 230, 240, 1)',
                borderWidth: 1
            }]
        };
    @endif
    
    categoryChart = new Chart(ctx, {
        type: 'bar',
        data: categoryData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Initialize type performance chart
 */
function initializeTypeChart() {
    const ctx = document.getElementById('typeChart').getContext('2d');
    
    @if(isset($performanceMetrics['top_by_type']) && $performanceMetrics['top_by_type']->count() > 0)
        const typeData = {
            labels: {!! json_encode($performanceMetrics['top_by_type']->pluck('type')->map(function($type) { return ucfirst($type); })) !!},
            datasets: [{
                label: 'Total Views',
                data: {!! json_encode($performanceMetrics['top_by_type']->pluck('total_views')) !!},
                backgroundColor: 'rgba(54, 185, 204, 0.8)',
                borderColor: 'rgba(54, 185, 204, 1)',
                borderWidth: 1
            }]
        };
    @else
        const typeData = {
            labels: ['No Data'],
            datasets: [{
                label: 'Total Views',
                data: [0],
                backgroundColor: 'rgba(227, 230, 240, 0.8)',
                borderColor: 'rgba(227, 230, 240, 1)',
                borderWidth: 1
            }]
        };
    @endif
    
    typeChart = new Chart(ctx, {
        type: 'bar',
        data: typeData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Initialize performance trends chart
 */
function initializeTrendsChart() {
    const ctx = document.getElementById('trendsChart').getContext('2d');
    
    @if(isset($performanceMetrics['performance_trends']) && $performanceMetrics['performance_trends']->count() > 0)
        const trendsData = {
            labels: {!! json_encode($performanceMetrics['performance_trends']->pluck('date')) !!},
            datasets: [
                {
                    label: 'Total Accesses',
                    data: {!! json_encode($performanceMetrics['performance_trends']->pluck('total_accesses')) !!},
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    fill: true
                },
                {
                    label: 'Unique Users',
                    data: {!! json_encode($performanceMetrics['performance_trends']->pluck('unique_users')) !!},
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    borderColor: 'rgba(28, 200, 138, 1)',
                    borderWidth: 2,
                    fill: false
                }
            ]
        };
    @else
        const trendsData = {
            labels: [],
            datasets: [
                {
                    label: 'Total Accesses',
                    data: [],
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    fill: true
                }
            ]
        };
    @endif
    
    trendsChart = new Chart(ctx, {
        type: 'line',
        data: trendsData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                x: {
                    display: true
                },
                y: {
                    display: true,
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Refresh dashboard button
    document.getElementById('refreshDashboard').addEventListener('click', function() {
        location.reload();
    });
}

/**
 * Update popular content chart based on period selection
 */
function updatePopularContentChart() {
    const period = document.getElementById('popularContentPeriod').value;
    showLoading();
    
    fetch(`{{ route('admin.analytics.popular-content.get-popular') }}?period=${period}&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateChartData(popularContentChart, data.popular_content);
            }
        })
        .catch(error => {
            console.error('Error updating chart:', error);
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Update popular content table based on period selection
 */
function updatePopularTable() {
    const period = document.getElementById('popularTablePeriod').value;
    showLoading();
    
    fetch(`{{ route('admin.analytics.popular-content.get-popular') }}?period=${period}&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePopularTableData(data.popular_content, period);
            }
        })
        .catch(error => {
            console.error('Error updating table:', error);
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Update chart data
 */
function updateChartData(chart, data) {
    chart.data.labels = data.map(item => item.title.substring(0, 20));
    chart.data.datasets[0].data = data.map(item => {
        switch(document.getElementById('popularContentPeriod').value) {
            case '24_hours': return item.views_today || 0;
            case '7_days': return item.views_last_7_days || 0;
            case '30_days': return item.views_last_30_days || 0;
            default: return item.views_last_30_days || 0;
        }
    });
    chart.update();
}

/**
 * Update popular table data
 */
function updatePopularTableData(data, period) {
    const tbody = document.getElementById('popularContentTableBody');
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No popular content data available</td></tr>';
        return;
    }
    
    data.forEach(content => {
        const viewsField = period === '24_hours' ? 'views_today' : 
                          period === '7_days' ? 'views_last_7_days' : 'views_last_30_days';
        const viewsLabel = period === '24_hours' ? 'today' : 
                          period === '7_days' ? '7 days' : '30 days';
        
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        ${content.is_featured ? '<i class="fas fa-star text-warning me-2" title="Featured"></i>' : ''}
                        <div>
                            <div class="font-weight-bold">${content.title.substring(0, 40)}${content.title.length > 40 ? '...' : ''}</div>
                            <div class="text-muted small">${content.category || 'Uncategorized'}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-secondary">${content.type ? content.type.charAt(0).toUpperCase() + content.type.slice(1) : 'Unknown'}</span>
                </td>
                <td>
                    <div class="font-weight-bold">${(content[viewsField] || 0).toLocaleString()}</div>
                    <div class="text-muted small">${viewsLabel}</div>
                </td>
                <td>
                    <div class="font-weight-bold">${(content.engagement_score || 0).toFixed(1)}</div>
                    ${content.is_trending ? '<i class="fas fa-trending-up text-success ms-1" title="Trending"></i>' : ''}
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

/**
 * Export analytics data
 */
function exportAnalytics(format, period) {
    showLoading();
    
    const params = new URLSearchParams({
        format: format,
        period: period,
        include_trending: 'true',
        include_engagement: 'true'
    });
    
    window.location.href = `{{ route('admin.analytics.popular-content.export') }}?${params.toString()}`;
    
    setTimeout(() => {
        hideLoading();
    }, 2000);
}

/**
 * Show loading modal
 */
function showLoading() {
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
}

/**
 * Hide loading modal
 */
function hideLoading() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
    if (modal) {
        modal.hide();
    }
}
</script>
@endpush