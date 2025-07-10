@extends('layouts.app')

@section('title', 'User Management')

@section('breadcrumb')
    <li class="breadcrumb-item active">User Management</li>
@endsection

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
<style>
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }
    .user-avatar-placeholder {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        font-weight: bold;
    }
    .status-filter {
        min-width: 120px;
    }
    .bulk-actions {
        display: none;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .bulk-actions.show {
        display: block;
    }
    .table-dark .badge {
        color: #000 !important;
    }
    .table-dark .badge.bg-warning {
        color: #000 !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        User Management
                    </h4>
                    <div>
                        <button type="button" class="btn btn-success me-2" id="bulkActionsBtn" style="display: none;">
                            <i class="fas fa-tasks me-1"></i>
                            Bulk Actions
                        </button>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            Add New User
                        </a>
                    </div>
                </div>
                
                <!-- Bulk Actions Panel -->
                <div id="bulkActionsPanel" class="bulk-actions">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <span id="selectedCount" class="fw-bold text-dark">0 users selected</span>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success btn-sm" onclick="performBulkAction('activate')">
                                    <i class="fas fa-check me-1"></i>Activate
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="performBulkAction('suspend')">
                                    <i class="fas fa-ban me-1"></i>Suspend
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="performBulkAction('delete')">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                                    <i class="fas fa-times me-1"></i>Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select id="statusFilter" class="form-select status-filter">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="pending">Pending</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="roleFilter" class="form-label">Role</label>
                            <select id="roleFilter" class="form-select">
                                <option value="">All Roles</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="verifiedFilter" class="form-label">Email Verification</label>
                            <select id="verifiedFilter" class="form-select">
                                <option value="">All Users</option>
                                <option value="yes">Verified</option>
                                <option value="no">Unverified</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="departmentFilter" class="form-label">Department</label>
                            <input type="text" id="departmentFilter" class="form-control" placeholder="Filter by department...">
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-dark table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th width="50">Avatar</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Roles</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Verification</th>
                                    <th>Last Activity</th>
                                    <th>Created</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via DataTables AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Change Confirmation Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Status Change</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="statusModalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title">Confirm User Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action will:</p>
                <ul>
                    <li>Set the user status to "deleted"</li>
                    <li>Soft delete the user record</li>
                    <li>Prevent the user from logging in</li>
                    <li>Retain all user data for audit purposes</li>
                </ul>
                <p class="text-warning"><strong>This action can be reversed by an administrator.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete User</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Action Confirmation Modal -->
<div class="modal fade" id="bulkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Bulk Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="bulkModalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBulkAction">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.users.index") }}',
            data: function(d) {
                d.status = $('#statusFilter').val();
                d.role = $('#roleFilter').val();
                d.verified = $('#verifiedFilter').val();
                d.department = $('#departmentFilter').val();
            }
        },
        columns: [
            {
                data: 'id',
                name: 'id',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' + data + '">';
                }
            },
            {
                data: 'avatar',
                name: 'avatar',
                orderable: false,
                searchable: false
            },
            {
                data: 'full_name',
                name: 'full_name',
                render: function(data, type, row) {
                    return '<div><strong>' + data + '</strong></div>';
                }
            },
            {
                data: 'email',
                name: 'email'
            },
            {
                data: 'username',
                name: 'username',
                render: function(data, type, row) {
                    return data || '<span class="text-muted">—</span>';
                }
            },
            {
                data: 'roles',
                name: 'roles',
                orderable: false,
                searchable: false
            },
            {
                data: 'department',
                name: 'department',
                render: function(data, type, row) {
                    return data || '<span class="text-muted">—</span>';
                }
            },
            {
                data: 'status_badge',
                name: 'status',
                orderable: false,
                searchable: false
            },
            {
                data: 'verification_status',
                name: 'email_verified_at',
                orderable: false,
                searchable: false
            },
            {
                data: 'last_activity',
                name: 'last_seen_at',
                orderable: false,
                searchable: false
            },
            {
                data: 'created_at',
                name: 'created_at',
                render: function(data, type, row) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[10, 'desc']], // Order by created_at desc
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No users found',
            zeroRecords: 'No matching users found'
        },
        drawCallback: function() {
            // Update bulk actions visibility
            updateBulkActionsVisibility();
        }
    });

    // Filter event handlers
    $('#statusFilter, #roleFilter, #verifiedFilter').on('change', function() {
        table.draw();
    });

    $('#departmentFilter').on('keyup', function() {
        table.draw();
    });

    // Select all checkbox
    $('#selectAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', isChecked);
        updateBulkActionsVisibility();
    });

    // Individual row checkbox
    $(document).on('change', '.row-checkbox', function() {
        updateSelectAllCheckbox();
        updateBulkActionsVisibility();
    });

    // Update select all checkbox state
    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('.row-checkbox').length;
        const checkedCheckboxes = $('.row-checkbox:checked').length;
        
        $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
    }

    // Update bulk actions visibility
    function updateBulkActionsVisibility() {
        const checkedCount = $('.row-checkbox:checked').length;
        
        if (checkedCount > 0) {
            $('#bulkActionsBtn').show();
            $('#bulkActionsPanel').addClass('show');
            $('#selectedCount').text(checkedCount + ' user' + (checkedCount !== 1 ? 's' : '') + ' selected');
        } else {
            $('#bulkActionsBtn').hide();
            $('#bulkActionsPanel').removeClass('show');
        }
    }

    // Clear selection
    window.clearSelection = function() {
        $('.row-checkbox, #selectAll').prop('checked', false);
        updateBulkActionsVisibility();
    };
});

// Global variables for modals
let currentUserId = null;
let currentStatus = null;
let currentBulkAction = null;

// Toggle user status
function toggleUserStatus(userId, newStatus) {
    currentUserId = userId;
    currentStatus = newStatus;
    
    const action = newStatus === 'active' ? 'activate' : 'suspend';
    $('#statusModalMessage').text(`Are you sure you want to ${action} this user?`);
    $('#statusModal').modal('show');
}

// Confirm status change
$('#confirmStatusChange').on('click', function() {
    if (currentUserId && currentStatus) {
        $.ajax({
            url: `/admin/users/${currentUserId}/toggle-status`,
            method: 'POST',
            data: {
                status: currentStatus,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    $('#usersTable').DataTable().ajax.reload(null, false);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Failed to update user status.');
            },
            complete: function() {
                $('#statusModal').modal('hide');
                currentUserId = null;
                currentStatus = null;
            }
        });
    }
});

// Delete user
function deleteUser(userId) {
    currentUserId = userId;
    $('#deleteModal').modal('show');
}

// Confirm delete
$('#confirmDelete').on('click', function() {
    if (currentUserId) {
        $.ajax({
            url: `/admin/users/${currentUserId}`,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    $('#usersTable').DataTable().ajax.reload(null, false);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Failed to delete user.');
            },
            complete: function() {
                $('#deleteModal').modal('hide');
                currentUserId = null;
            }
        });
    }
});

// Perform bulk action
function performBulkAction(action) {
    const selectedIds = $('.row-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selectedIds.length === 0) {
        showAlert('warning', 'Please select at least one user.');
        return;
    }
    
    currentBulkAction = action;
    const actionText = action === 'activate' ? 'activate' : (action === 'suspend' ? 'suspend' : 'delete');
    $('#bulkModalMessage').text(`Are you sure you want to ${actionText} ${selectedIds.length} selected user(s)?`);
    $('#bulkModal').modal('show');
}

// Confirm bulk action
$('#confirmBulkAction').on('click', function() {
    if (currentBulkAction) {
        const selectedIds = $('.row-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        $.ajax({
            url: '{{ route("admin.users.bulk-action") }}',
            method: 'POST',
            data: {
                action: currentBulkAction,
                user_ids: selectedIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    $('#usersTable').DataTable().ajax.reload(null, false);
                    clearSelection();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Failed to perform bulk action.');
            },
            complete: function() {
                $('#bulkModal').modal('hide');
                currentBulkAction = null;
            }
        });
    }
});

// Show alert function
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-danger';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the card body
    $('.card-body').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endpush