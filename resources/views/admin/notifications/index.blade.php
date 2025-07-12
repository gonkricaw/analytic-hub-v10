@extends('layouts.admin')

@section('title', 'Notification Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-bell"></i> Notification Management
            </h1>
            <p class="mb-0 text-muted">Manage system notifications and announcements</p>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Notification
            </a>
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#statisticsModal">
                <i class="fas fa-chart-bar"></i> Statistics
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filters
            </h6>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="type_filter" class="form-label">Type</label>
                    <select class="form-select" id="type_filter" name="type">
                        <option value="">All Types</option>
                        <option value="system">System</option>
                        <option value="announcement">Announcement</option>
                        <option value="alert">Alert</option>
                        <option value="info">Information</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="priority_filter" class="form-label">Priority</label>
                    <select class="form-select" id="priority_filter" name="priority">
                        <option value="">All Priorities</option>
                        <option value="high">High</option>
                        <option value="normal">Normal</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status_filter" class="form-label">Status</label>
                    <select class="form-select" id="status_filter" name="status">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="sent">Sent</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="target_filter" class="form-label">Target</label>
                    <select class="form-select" id="target_filter" name="target">
                        <option value="">All Targets</option>
                        <option value="all_users">All Users</option>
                        <option value="specific_users">Specific Users</option>
                        <option value="role_based">Role Based</option>
                        <option value="active_users">Active Users</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="button" class="btn btn-primary" onclick="refreshTable()">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Notifications
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="notificationsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Recipients</th>
                            <th>Scheduled</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Modal -->
<div class="modal fade" id="statisticsModal" tabindex="-1" aria-labelledby="statisticsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statisticsModalLabel">
                    <i class="fas fa-chart-bar"></i> Notification Statistics
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="statisticsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#notificationsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("admin.notifications.data") }}',
            data: function(d) {
                d.type = $('#type_filter').val();
                d.priority = $('#priority_filter').val();
                d.status = $('#status_filter').val();
                d.target = $('#target_filter').val();
            }
        },
        columns: [
            { data: 'title', name: 'title' },
            { 
                data: 'type', 
                name: 'type',
                render: function(data) {
                    const badges = {
                        'system': 'bg-info',
                        'announcement': 'bg-primary',
                        'alert': 'bg-danger',
                        'info': 'bg-secondary'
                    };
                    return `<span class="badge ${badges[data] || 'bg-secondary'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { 
                data: 'priority', 
                name: 'priority',
                render: function(data) {
                    const badges = {
                        'high': 'bg-danger',
                        'normal': 'bg-success',
                        'low': 'bg-secondary'
                    };
                    return `<span class="badge ${badges[data] || 'bg-secondary'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { data: 'target_display', name: 'target_display' },
            { 
                data: 'status', 
                name: 'status',
                render: function(data) {
                    const badges = {
                        'draft': 'bg-warning',
                        'scheduled': 'bg-info',
                        'sent': 'bg-success',
                        'expired': 'bg-secondary'
                    };
                    return `<span class="badge ${badges[data] || 'bg-secondary'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { data: 'recipient_count', name: 'recipient_count' },
            { data: 'scheduled_at_formatted', name: 'scheduled_at' },
            { data: 'created_at_formatted', name: 'created_at' },
            { 
                data: 'actions', 
                name: 'actions', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <a href="/admin/notifications/${row.id}" class="btn btn-sm btn-outline-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/admin/notifications/${row.id}/edit" class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteNotification('${row.id}')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[7, 'desc']], // Order by created_at descending
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Load statistics when modal is shown
    $('#statisticsModal').on('show.bs.modal', function() {
        loadStatistics();
    });
});

function refreshTable() {
    $('#notificationsTable').DataTable().ajax.reload();
}

function clearFilters() {
    $('#filterForm')[0].reset();
    refreshTable();
}

function deleteNotification(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/notifications/${id}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire(
                        'Deleted!',
                        'Notification has been deleted.',
                        'success'
                    );
                    refreshTable();
                },
                error: function(xhr) {
                    Swal.fire(
                        'Error!',
                        'Failed to delete notification.',
                        'error'
                    );
                }
            });
        }
    });
}

function loadStatistics() {
    $.ajax({
        url: '{{ route("admin.notifications.statistics") }}',
        type: 'GET',
        success: function(data) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-left-primary">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Notifications</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">${data.total_notifications}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bell fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sent Notifications</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">${data.sent_notifications}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card border-left-info">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Scheduled Notifications</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">${data.scheduled_notifications}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-left-warning">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Draft Notifications</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">${data.draft_notifications}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-edit fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Notifications by Type</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>`;
            
            if (data.by_type && Object.keys(data.by_type).length > 0) {
                Object.entries(data.by_type).forEach(([type, count]) => {
                    const percentage = data.total_notifications > 0 ? ((count / data.total_notifications) * 100).toFixed(1) : 0;
                    html += `
                        <tr>
                            <td>${type.charAt(0).toUpperCase() + type.slice(1)}</td>
                            <td>${count}</td>
                            <td>${percentage}%</td>
                        </tr>
                    `;
                });
            } else {
                html += '<tr><td colspan="3" class="text-center">No data available</td></tr>';
            }
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            $('#statisticsContent').html(html);
        },
        error: function(xhr) {
            $('#statisticsContent').html('<div class="alert alert-danger">Failed to load statistics</div>');
        }
    });
}
</script>
@endpush