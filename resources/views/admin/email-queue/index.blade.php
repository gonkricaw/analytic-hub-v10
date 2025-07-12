@extends('layouts.admin')

@section('title', 'Email Queue Monitoring')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-envelope-open-text"></i> Email Queue Monitoring
        </h1>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkEmailModal">
                <i class="fas fa-paper-plane"></i> Send Bulk Email
            </button>
            <button type="button" class="btn btn-warning" onclick="retryFailedEmails()">
                <i class="fas fa-redo"></i> Retry Failed
            </button>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                <i class="fas fa-broom"></i> Cleanup
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Emails
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-emails">
                                {{ $statistics['total_emails'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
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
                                Sent Successfully
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="sent-emails">
                                {{ $statistics['sent_emails'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Queued/Processing
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="queued-emails">
                                {{ ($statistics['queued_emails'] ?? 0) + ($statistics['processing_emails'] ?? 0) }}
                            </div>
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
                                Failed Emails
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="failed-emails">
                                {{ $statistics['failed_emails'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Rate Chart -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Email Delivery Trends (Last 30 Days)</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <div class="dropdown-header">Chart Options:</div>
                            <a class="dropdown-item" href="#" onclick="updateChart('7days')">Last 7 Days</a>
                            <a class="dropdown-item" href="#" onclick="updateChart('30days')">Last 30 Days</a>
                            <a class="dropdown-item" href="#" onclick="updateChart('90days')">Last 90 Days</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="emailTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Email Types Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="emailTypesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Queue Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="m-0 font-weight-bold text-primary">Email Queue</h6>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#filtersCollapse">
                        <i class="fas fa-filter"></i> Filters
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="collapse" id="filtersCollapse">
            <div class="card-body border-bottom">
                <form id="filtersForm" class="row g-3">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter" name="status">
                            <option value="">All Statuses</option>
                            <option value="queued">Queued</option>
                            <option value="processing">Processing</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="typeFilter" class="form-label">Email Type</label>
                        <select class="form-select" id="typeFilter" name="email_type">
                            <option value="">All Types</option>
                            <option value="transactional">Transactional</option>
                            <option value="marketing">Marketing</option>
                            <option value="notification">Notification</option>
                            <option value="system">System</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="priorityFilter" class="form-label">Priority</label>
                        <select class="form-select" id="priorityFilter" name="priority">
                            <option value="">All Priorities</option>
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="dateFromFilter" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="dateFromFilter" name="date_from">
                    </div>
                    <div class="col-md-3">
                        <label for="dateToFilter" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="dateToFilter" name="date_to">
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="emailQueueTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Template</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Attempts</th>
                            <th>Timing</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via DataTables AJAX -->
                    </tbody>
                </table>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <div class="d-flex align-items-center justify-content-between p-3 bg-light border-top">
                    <div>
                        <span class="selected-count">0</span> emails selected
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-warning" onclick="bulkRetryEmails()">
                            <i class="fas fa-redo"></i> Retry Selected
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="bulkCancelEmails()">
                            <i class="fas fa-times"></i> Cancel Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
        </div>
        <div class="card-body">
            @if($recentActivity && $recentActivity->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($recentActivity as $email)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $email->subject }}</h6>
                                <p class="mb-1 text-muted">To: {{ $email->to_email }}</p>
                                <small class="text-muted">{{ $email->created_at->diffForHumans() }}</small>
                            </div>
                            <div>
                                @php
                                    $badgeClass = match($email->status) {
                                        'queued' => 'badge-warning',
                                        'processing' => 'badge-info',
                                        'sent' => 'badge-success',
                                        'failed' => 'badge-danger',
                                        'cancelled' => 'badge-secondary',
                                        'expired' => 'badge-dark',
                                        default => 'badge-light'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($email->status) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted">No recent email activity</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Email Details Modal -->
<div class="modal fade" id="emailDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="emailDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Email Modal -->
<div class="modal fade" id="bulkEmailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Bulk Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkEmailForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bulkTemplateId" class="form-label">Email Template *</label>
                                <select class="form-select" id="bulkTemplateId" name="template_id" required>
                                    <option value="">Select Template</option>
                                    <!-- Options will be loaded via AJAX -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bulkPriority" class="form-label">Priority</label>
                                <select class="form-select" id="bulkPriority" name="priority">
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulkSubject" class="form-label">Subject Override</label>
                        <input type="text" class="form-control" id="bulkSubject" name="subject" placeholder="Leave empty to use template subject">
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulkScheduledAt" class="form-label">Schedule For</label>
                        <input type="datetime-local" class="form-control" id="bulkScheduledAt" name="scheduled_at">
                        <div class="form-text">Leave empty to send immediately</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulkRecipients" class="form-label">Recipients *</label>
                        <textarea class="form-control" id="bulkRecipients" name="recipients" rows="6" required placeholder="Enter recipients in JSON format or one email per line"></textarea>
                        <div class="form-text">
                            Format: One email per line or JSON array with email, name, and template_data fields
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Bulk Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cleanup Modal -->
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cleanup Old Email Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cleanupForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cleanupDays" class="form-label">Delete records older than (days)</label>
                        <input type="number" class="form-control" id="cleanupDays" name="days_old" value="30" min="1" max="365" required>
                        <div class="form-text">This will permanently delete email records older than the specified number of days</div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action cannot be undone. Please make sure you have backups if needed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-broom"></i> Cleanup Records
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('css/admin/email-queue.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/admin/email-queue.js') }}"></script>
@endpush