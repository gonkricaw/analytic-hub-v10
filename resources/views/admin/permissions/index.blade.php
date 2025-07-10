@extends('layouts.app')

@section('title', 'Permissions Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>
                        Permissions Management
                    </h4>
                    <div>
                        <a href="{{ route('admin.role-permissions.index') }}" class="btn btn-info me-2">
                            <i class="fas fa-link me-1"></i>
                            Role-Permission Matrix
                        </a>
                        @can('create', App\Models\Permission::class)
                            <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>
                                Add Permission
                            </a>
                        @endcan
                    </div>
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

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select id="moduleFilter" class="form-select bg-dark text-white border-secondary">
                                <option value="">All Modules</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-select bg-dark text-white border-secondary">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="typeFilter" class="form-select bg-dark text-white border-secondary">
                                <option value="">All Types</option>
                                <option value="1">System Permissions</option>
                                <option value="0">Custom Permissions</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-secondary" id="clearFilters">
                                <i class="fas fa-times me-1"></i>
                                Clear Filters
                            </button>
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="table-responsive">
                        <table id="permissionsTable" class="table table-dark table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Permission</th>
                                    <th>Module</th>
                                    <th>Type</th>
                                    <th>Roles</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
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
                <p>Are you sure you want to delete this permission?</p>
                <div id="deleteWarnings"></div>
                <p class="text-warning mb-0">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-1"></i>
                    Delete Permission
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Permission Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Permission Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Dark theme DataTables styling */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        background-color: #2a2a2a;
        border: 1px solid #444;
        color: white;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        color: #fff;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        background: #2a2a2a;
        border: 1px solid #444;
        color: #fff !important;
        margin: 0 2px;
        border-radius: 0.375rem;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #FF7A00 !important;
        border-color: #FF7A00 !important;
        color: white !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #FF7A00 !important;
        border-color: #FF7A00 !important;
        color: white !important;
    }
    
    .table-dark th {
        border-color: #444;
        background-color: #1a1a3a;
    }
    
    .table-dark td {
        border-color: #333;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .form-select:focus,
    .form-control:focus {
        background-color: #2a2a70;
        border-color: #FF7A00;
        box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.25);
        color: white;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let table;
    let deleteUrl = '';
    
    // Initialize DataTable
    table = $('#permissionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.permissions.data") }}',
            data: function(d) {
                d.module = $('#moduleFilter').val();
                d.status = $('#statusFilter').val();
                d.is_system = $('#typeFilter').val();
            }
        },
        columns: [
            {
                data: 'display_name',
                name: 'display_name',
                render: function(data, type, row) {
                    let html = `<div class="fw-bold">${data}</div>`;
                    html += `<small class="text-muted">${row.name}</small>`;
                    if (row.description) {
                        html += `<br><small class="text-info">${row.description}</small>`;
                    }
                    return html;
                }
            },
            {
                data: 'module',
                name: 'module',
                render: function(data, type, row) {
                    if (!data) return '<span class="text-muted">General</span>';
                    return `<span class="badge bg-secondary">${data}</span>`;
                }
            },
            {
                data: 'is_system',
                name: 'is_system',
                render: function(data, type, row) {
                    if (data) {
                        return '<span class="badge bg-warning text-dark"><i class="fas fa-shield-alt me-1"></i>System</span>';
                    }
                    return '<span class="badge bg-info"><i class="fas fa-user-cog me-1"></i>Custom</span>';
                }
            },
            {
                data: 'roles_count',
                name: 'roles_count',
                render: function(data, type, row) {
                    if (data > 0) {
                        return `<span class="badge bg-success">${data} Role${data > 1 ? 's' : ''}</span>`;
                    }
                    return '<span class="text-muted">No roles</span>';
                }
            },
            {
                data: 'status',
                name: 'status',
                render: function(data, type, row) {
                    if (data === 'active') {
                        return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>';
                    }
                    return '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inactive</span>';
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                render: function(data, type, row) {
                    const date = new Date(data);
                    return date.toLocaleDateString() + '<br><small class="text-muted">' + date.toLocaleTimeString() + '</small>';
                }
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading permissions...',
            emptyTable: 'No permissions found',
            zeroRecords: 'No matching permissions found'
        },
        drawCallback: function() {
            // Reinitialize tooltips after table redraw
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
    
    // Load modules for filter
    loadModules();
    
    // Filter change events
    $('#moduleFilter, #statusFilter, #typeFilter').on('change', function() {
        table.ajax.reload();
    });
    
    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#moduleFilter, #statusFilter, #typeFilter').val('');
        table.ajax.reload();
    });
    
    // Delete permission
    $(document).on('click', '.delete-permission', function(e) {
        e.preventDefault();
        
        const permissionId = $(this).data('id');
        const permissionName = $(this).data('name');
        const rolesCount = $(this).data('roles-count');
        const isSystem = $(this).data('is-system');
        
        deleteUrl = $(this).attr('href');
        
        // Show warnings
        let warnings = '';
        if (isSystem) {
            warnings += '<div class="alert alert-warning"><i class="fas fa-shield-alt me-2"></i>This is a system permission. Deleting it may affect system functionality.</div>';
        }
        if (rolesCount > 0) {
            warnings += `<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>This permission is assigned to ${rolesCount} role${rolesCount > 1 ? 's' : ''}. It will be removed from all roles.</div>`;
        }
        
        $('#deleteWarnings').html(warnings);
        $('#deleteModal .modal-body p:first').html(`Are you sure you want to delete the permission <strong>${permissionName}</strong>?`);
        $('#deleteModal').modal('show');
    });
    
    // Confirm delete
    $('#confirmDelete').on('click', function() {
        if (deleteUrl) {
            // Show loading state
            $(this).html('<i class="fas fa-spinner fa-spin me-1"></i>Deleting...').prop('disabled', true);
            
            // Create and submit form
            const form = $('<form>', {
                method: 'POST',
                action: deleteUrl
            });
            
            form.append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: $('meta[name="csrf-token"]').attr('content')
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: '_method',
                value: 'DELETE'
            }));
            
            $('body').append(form);
            form.submit();
        }
    });
    
    // View permission details
    $(document).on('click', '.view-permission', function(e) {
        e.preventDefault();
        
        const permissionId = $(this).data('id');
        
        // Show loading state
        $('#detailsContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading permission details...</div>');
        $('#detailsModal').modal('show');
        
        // Load permission details
        $.ajax({
            url: $(this).attr('href'),
            method: 'GET',
            success: function(response) {
                $('#detailsContent').html(response);
            },
            error: function() {
                $('#detailsContent').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Failed to load permission details.</div>');
            }
        });
    });
    
    // Auto-refresh table every 30 seconds
    setInterval(function() {
        if (table && !$('.modal').hasClass('show')) {
            table.ajax.reload(null, false);
        }
    }, 30000);
    
    function loadModules() {
        $.ajax({
            url: '{{ route("admin.permissions.data") }}',
            method: 'GET',
            data: { modules_only: true },
            success: function(response) {
                const select = $('#moduleFilter');
                select.find('option:not(:first)').remove();
                
                if (response.modules && response.modules.length > 0) {
                    response.modules.forEach(function(module) {
                        if (module) {
                            select.append(`<option value="${module}">${module}</option>`);
                        }
                    });
                }
            },
            error: function() {
                console.error('Failed to load modules for filter');
            }
        });
    }
    
    // Show success/error messages
    @if(session('success'))
        setTimeout(function() {
            $('.alert-success').fadeOut();
        }, 5000);
    @endif
    
    @if(session('error'))
        setTimeout(function() {
            $('.alert-danger').fadeOut();
        }, 5000);
    @endif
});
</script>
@endpush