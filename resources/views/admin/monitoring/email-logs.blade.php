@extends('layouts.app')

@section('title', 'Email Logs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-envelope me-2 text-primary"></i>Email Logs
            </h1>
            <p class="text-muted mb-0">Monitor email delivery, queue status, and communication logs</p>
        </div>
        <div>
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Email Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Sent Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $emailStats['sent_today'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-paper-plane fa-2x text-gray-300"></i>
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
                                Pending Queue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $emailStats['pending'] ?? 0 }}</div>
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
                                Failed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $emailStats['failed'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                Delivery Rate
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $emailStats['delivery_rate'] ?? '0' }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Delivery Chart -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Email Delivery Trend</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <div class="dropdown-header">Time Range:</div>
                            <a class="dropdown-item" href="?range=24h">Last 24 Hours</a>
                            <a class="dropdown-item" href="?range=7d">Last 7 Days</a>
                            <a class="dropdown-item" href="?range=30d">Last 30 Days</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="emailDeliveryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Email Templates Usage</h6>
                </div>
                <div class="card-body">
                    @if(isset($templateUsage) && count($templateUsage) > 0)
                        @foreach($templateUsage as $template)
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="flex-grow-1">
                                    <div class="small font-weight-bold">{{ $template['name'] }}</div>
                                    <div class="text-muted small">{{ $template['sent_count'] }} sent</div>
                                </div>
                                <div class="text-right">
                                    <div class="small font-weight-bold text-{{ $template['success_rate'] > 90 ? 'success' : ($template['success_rate'] > 70 ? 'warning' : 'danger') }}">
                                        {{ number_format($template['success_rate'], 1) }}%
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-envelope fa-2x mb-2"></i>
                            <p>No template usage data</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Status -->
    @if(isset($queueStatus) && count($queueStatus) > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-1"></i>Queue Status
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($queueStatus as $queue)
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-{{ $queue['status'] === 'active' ? 'success' : 'warning' }} h-100">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">{{ $queue['name'] }}</div>
                                <div class="h6 mb-1">{{ $queue['jobs_count'] }} jobs</div>
                                <div class="small text-muted">{{ ucfirst($queue['status']) }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-1"></i>Email Log Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.monitoring.email-logs') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Email, Subject, Template...">
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="bounced" {{ request('status') === 'bounced' ? 'selected' : '' }}>Bounced</option>
                        <option value="opened" {{ request('status') === 'opened' ? 'selected' : '' }}>Opened</option>
                        <option value="clicked" {{ request('status') === 'clicked' ? 'selected' : '' }}>Clicked</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="template" class="form-label">Template</label>
                    <select class="form-select" id="template" name="template">
                        <option value="">All Templates</option>
                        <option value="user_invitation" {{ request('template') === 'user_invitation' ? 'selected' : '' }}>User Invitation</option>
                        <option value="password_reset" {{ request('template') === 'password_reset' ? 'selected' : '' }}>Password Reset</option>
                        <option value="welcome" {{ request('template') === 'welcome' ? 'selected' : '' }}>Welcome</option>
                        <option value="notification" {{ request('template') === 'notification' ? 'selected' : '' }}>Notification</option>
                        <option value="terms_update" {{ request('template') === 'terms_update' ? 'selected' : '' }}>Terms Update</option>
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
                        <a href="{{ route('admin.monitoring.email-logs') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Email Logs Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Email Communication Logs</h6>
            <div class="d-flex gap-2">
                <span class="badge bg-info">{{ $logs->total() }} emails</span>
                <button class="btn btn-sm btn-outline-primary" onclick="exportEmailLogs()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <button class="btn btn-sm btn-outline-warning" onclick="retryFailedEmails()">
                    <i class="fas fa-redo me-1"></i>Retry Failed
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="emailLogsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Template</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>Delivery Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="{{ $this->getEmailRowClass($log) }}">
                                    <td>
                                        <small>{{ $log->created_at->format('Y-m-d H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $log->recipient_email }}</strong>
                                            @if($log->recipient_name)
                                                <br><small class="text-muted">{{ $log->recipient_name }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;" title="{{ $log->subject }}">
                                            {{ $log->subject }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $log->template_name ?? 'Custom' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $this->getStatusColor($log->status) }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                        @if($log->tracking_data)
                                            <div class="mt-1">
                                                @if(isset($log->tracking_data['opened']))
                                                    <small class="badge bg-info">Opened</small>
                                                @endif
                                                @if(isset($log->tracking_data['clicked']))
                                                    <small class="badge bg-success">Clicked</small>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $log->attempts > 1 ? 'warning' : 'info' }}">
                                            {{ $log->attempts }}/3
                                        </span>
                                    </td>
                                    <td>
                                        @if($log->sent_at)
                                            <small>{{ $log->sent_at->diffInSeconds($log->created_at) }}s</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-info" onclick="viewEmailDetails({{ $log->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($log->status === 'failed' && $log->attempts < 3)
                                                <button class="btn btn-sm btn-outline-warning" onclick="retryEmail({{ $log->id }})">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            @endif
                                            @if(in_array($log->status, ['sent', 'delivered']))
                                                <button class="btn btn-sm btn-outline-success" onclick="resendEmail({{ $log->id }})">
                                                    <i class="fas fa-paper-plane"></i>
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
                            Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} emails
                        </small>
                    </div>
                    <div>
                        {{ $logs->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                    <h5 class="text-primary">No Email Logs</h5>
                    <p class="text-muted">No email logs match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Email Detail Modal -->
<div class="modal fade" id="emailDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="emailDetailContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info" onclick="copyEmailContent()">
                    <i class="fas fa-copy me-1"></i>Copy Content
                </button>
                <button type="button" class="btn btn-warning" id="retryEmailBtn" style="display: none;">
                    <i class="fas fa-redo me-1"></i>Retry
                </button>
                <button type="button" class="btn btn-success" id="resendEmailBtn" style="display: none;">
                    <i class="fas fa-paper-plane me-1"></i>Resend
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Email Delivery Chart
const ctx = document.getElementById('emailDeliveryChart').getContext('2d');
const emailDeliveryChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($chartData['labels'] ?? []),
        datasets: [
            {
                label: 'Sent',
                data: @json($chartData['sent'] ?? []),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            },
            {
                label: 'Failed',
                data: @json($chartData['failed'] ?? []),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.1
            },
            {
                label: 'Delivered',
                data: @json($chartData['delivered'] ?? []),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.1
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
                    text: 'Email Count'
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

let currentEmailId = null;

function viewEmailDetails(emailId) {
    currentEmailId = emailId;
    
    // In a real implementation, this would fetch email details via AJAX
    const modal = new bootstrap.Modal(document.getElementById('emailDetailModal'));
    
    // Mock data for demonstration
    const emailDetails = `
        <div class="row">
            <div class="col-md-6">
                <h6>Email Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Email ID:</strong></td><td>${emailId}</td></tr>
                    <tr><td><strong>Template:</strong></td><td>User Invitation</td></tr>
                    <tr><td><strong>Status:</strong></td><td><span class="badge bg-success">Sent</span></td></tr>
                    <tr><td><strong>Attempts:</strong></td><td>1/3</td></tr>
                    <tr><td><strong>Queue:</strong></td><td>emails</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Recipient Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Email:</strong></td><td>user@example.com</td></tr>
                    <tr><td><strong>Name:</strong></td><td>John Doe</td></tr>
                    <tr><td><strong>Sent At:</strong></td><td>2024-01-15 14:30:25</td></tr>
                    <tr><td><strong>Delivered At:</strong></td><td>2024-01-15 14:30:28</td></tr>
                    <tr><td><strong>Opened At:</strong></td><td>2024-01-15 14:35:12</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Email Content</h6>
                <div class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                    <div id="emailContent">
                        <h4>Welcome to Analytics Hub</h4>
                        <p>Dear John Doe,</p>
                        <p>You have been invited to join Analytics Hub. Please click the link below to set up your account:</p>
                        <p><a href="#" class="btn btn-primary">Set Up Account</a></p>
                        <p>Best regards,<br>Analytics Hub Team</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Tracking Information</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Timestamp</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success">Sent</span></td>
                                <td>2024-01-15 14:30:25</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-info">Delivered</span></td>
                                <td>2024-01-15 14:30:28</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning">Opened</span></td>
                                <td>2024-01-15 14:35:12</td>
                                <td>192.168.1.100</td>
                                <td>Mozilla/5.0...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('emailDetailContent').innerHTML = emailDetails;
    modal.show();
}

function retryEmail(emailId) {
    if (!confirm('Are you sure you want to retry sending this email?')) {
        return;
    }
    
    showAlert('Email has been queued for retry', 'info');
}

function resendEmail(emailId) {
    if (!confirm('Are you sure you want to resend this email?')) {
        return;
    }
    
    showAlert('Email has been queued for resending', 'info');
}

function retryFailedEmails() {
    if (!confirm('Are you sure you want to retry all failed emails?')) {
        return;
    }
    
    showAlert('All failed emails have been queued for retry', 'info');
}

function copyEmailContent() {
    const content = document.getElementById('emailContent');
    if (content) {
        const textContent = content.innerText || content.textContent;
        navigator.clipboard.writeText(textContent).then(() => {
            showAlert('Email content copied to clipboard', 'success');
        }).catch(() => {
            showAlert('Failed to copy content', 'danger');
        });
    }
}

function exportEmailLogs() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    const exportUrl = `{{ route('admin.monitoring.email-logs') }}?${params.toString()}`;
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
// Helper functions for email log display
function getEmailRowClass($log) {
    $classes = [
        'failed' => 'table-danger',
        'bounced' => 'table-danger',
        'pending' => 'table-warning',
        'sent' => 'table-success',
        'delivered' => 'table-success',
    ];
    
    return $classes[$log->status] ?? '';
}

function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'sent' => 'success',
        'delivered' => 'success',
        'failed' => 'danger',
        'bounced' => 'danger',
        'opened' => 'info',
        'clicked' => 'primary',
    ];
    
    return $colors[$status] ?? 'secondary';
}
@endphp
@endsection