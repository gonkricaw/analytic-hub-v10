@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user-shield me-2"></i>
                        Role Management
                    </h4>
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Add New Role
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table id="rolesTable" class="table table-dark table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Display Name</th>
                                    <th>Level</th>
                                    <th>Users</th>
                                    <th>Permissions</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this role?</p>
                <p class="text-warning mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        Delete Role
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<style>
    .table-dark {
        --bs-table-bg: #1a1a3a;
        --bs-table-striped-bg: #252560;
        --bs-table-hover-bg: #2a2a70;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        background-color: #2a2a70;
        border: 1px solid #444;
        color: white;
    }
    
    .dataTables_wrapper .dataTables_length select {
        background-color: #2a2a70;
        border: 1px solid #444;
        color: white;
    }
    
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        color: #b0b0b0;
    }
    
    .page-link {
        background-color: #2a2a70;
        border-color: #444;
        color: #fff;
    }
    
    .page-link:hover {
        background-color: #FF7A00;
        border-color: #FF7A00;
        color: #fff;
    }
    
    .page-item.active .page-link {
        background-color: #FF7A00;
        border-color: #FF7A00;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#rolesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("admin.roles.data") }}',
            type: 'GET'
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'display_name', name: 'display_name' },
            { data: 'level', name: 'level', className: 'text-center' },
            { data: 'users_count', name: 'users_count', className: 'text-center' },
            { data: 'permissions_count', name: 'permissions_count', className: 'text-center' },
            { data: 'status', name: 'status', className: 'text-center' },
            { data: 'type', name: 'type', className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[2, 'asc']], // Order by level
        pageLength: 25,
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading...',
            emptyTable: 'No roles found',
            zeroRecords: 'No matching roles found'
        },
        drawCallback: function() {
            // Reinitialize tooltips after table redraw
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Handle delete button clicks
    $(document).on('click', '.delete-role', function(e) {
        e.preventDefault();
        
        const roleId = $(this).data('role-id');
        const roleName = $(this).data('role-name');
        const deleteUrl = '{{ route("admin.roles.destroy", ":id") }}'.replace(':id', roleId);
        
        // Update modal content
        $('#deleteModal .modal-body p:first').text(`Are you sure you want to delete the role "${roleName}"?`);
        $('#deleteForm').attr('action', deleteUrl);
        
        // Show modal
        $('#deleteModal').modal('show');
    });

    // Handle delete form submission
    $('#deleteForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Deleting...').prop('disabled', true);
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#deleteModal').modal('hide');
                
                if (response.success) {
                    // Show success message
                    showAlert('success', response.message);
                    
                    // Reload table
                    table.ajax.reload();
                } else {
                    showAlert('danger', response.message || 'Failed to delete role');
                }
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                
                let message = 'Failed to delete role';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                showAlert('danger', message);
            },
            complete: function() {
                // Reset button state
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Function to show alerts
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('.card-body').prepend(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endpush