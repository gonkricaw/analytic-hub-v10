@extends('layouts.app')

@section('title', 'Menu Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-white">Menu Management</h1>
                    <p class="text-muted mb-0">Manage system navigation menus and hierarchy</p>
                </div>
                <div>
                    <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Menu
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $hierarchicalMenus->count() }}</h4>
                            <p class="mb-0">Root Menus</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-sitemap fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ \App\Models\Menu::where('is_active', true)->count() }}</h4>
                            <p class="mb-0">Active Menus</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ \App\Models\Menu::where('is_active', false)->count() }}</h4>
                            <p class="mb-0">Inactive Menus</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-pause-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ \App\Models\Menu::where('is_system_menu', true)->count() }}</h4>
                            <p class="mb-0">System Menus</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-cog fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Hierarchy View -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sitemap"></i> Menu Hierarchy
                    </h5>
                </div>
                <div class="card-body">
                    @if($hierarchicalMenus->count() > 0)
                        <div class="menu-hierarchy">
                            @foreach($hierarchicalMenus as $menu)
                                @include('admin.menus.partials.hierarchy-item', ['menu' => $menu, 'level' => 0])
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-sitemap fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No menus found</h5>
                            <p class="text-muted">Create your first menu to get started.</p>
                            <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Menu
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable View -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-table"></i> Menu List
                        </h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshTable()">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAll()">
                                <i class="fas fa-expand-arrows-alt"></i> Expand All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAll()">
                                <i class="fas fa-compress-arrows-alt"></i> Collapse All
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="menusTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Menu Hierarchy</th>
                                    <th>Name</th>
                                    <th>Parent</th>
                                    <th>Level</th>
                                    <th>Order</th>
                                    <th>Roles</th>
                                    <th>Status</th>
                                    <th>Created</th>
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

<!-- Menu Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-eye"></i> Menu Preview
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-trash"></i> Confirm Deletion
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this menu item?</p>
                <p class="text-warning"><strong>Warning:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.menu-hierarchy {
    font-family: 'Courier New', monospace;
}

.menu-hierarchy .menu-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.menu-hierarchy .menu-item:last-child {
    border-bottom: none;
}

.menu-hierarchy .menu-level-0 {
    font-weight: bold;
    color: #007bff;
}

.menu-hierarchy .menu-level-1 {
    color: #28a745;
    margin-left: 20px;
}

.menu-hierarchy .menu-level-2 {
    color: #ffc107;
    margin-left: 40px;
}

.menu-hierarchy .menu-icon {
    margin-right: 8px;
}

.menu-hierarchy .menu-actions {
    float: right;
}

.menu-hierarchy .menu-actions .btn {
    margin-left: 4px;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
}

.badge {
    font-size: 0.75em;
}

.btn-group .btn {
    border-radius: 0.25rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.text-muted {
    color: #6c757d !important;
}

.sortable {
    cursor: move;
}

.sortable:hover {
    background-color: #f8f9fa;
}

.ui-sortable-helper {
    background-color: #fff;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.ui-sortable-placeholder {
    background-color: #e9ecef;
    border: 2px dashed #dee2e6;
    height: 40px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#menusTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.menus.data") }}',
            type: 'GET'
        },
        columns: [
            { data: 'hierarchy', name: 'hierarchy', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'parent_name', name: 'parent.title' },
            { data: 'level', name: 'level' },
            { data: 'sort_order', name: 'sort_order' },
            { data: 'roles_count', name: 'roles_count', orderable: false, searchable: false },
            { data: 'status', name: 'is_active' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[3, 'asc'], [4, 'asc']], // Order by level, then sort_order
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading...',
            emptyTable: 'No menus found',
            zeroRecords: 'No matching menus found'
        },
        drawCallback: function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    // Refresh table function
    window.refreshTable = function() {
        table.ajax.reload();
        toastr.info('Table refreshed');
    };

    // Expand/Collapse functions (for future tree table implementation)
    window.expandAll = function() {
        // Implementation for expanding all menu items
        toastr.info('Expand all functionality coming soon');
    };

    window.collapseAll = function() {
        // Implementation for collapsing all menu items
        toastr.info('Collapse all functionality coming soon');
    };

    // Preview menu function
    window.previewMenu = function(menuId) {
        $.ajax({
            url: '/admin/menus/' + menuId + '/preview',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var content = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Basic Information</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Name:</strong></td><td>${data.name}</td></tr>
                                    <tr><td><strong>Title:</strong></td><td>${data.title}</td></tr>
                                    <tr><td><strong>Description:</strong></td><td>${data.description || 'N/A'}</td></tr>
                                    <tr><td><strong>Level:</strong></td><td>${data.level}</td></tr>
                                    <tr><td><strong>URL:</strong></td><td>${data.url || 'N/A'}</td></tr>
                                    <tr><td><strong>Target:</strong></td><td>${data.target}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Status & Permissions</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Status:</strong></td><td>${data.is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'}</td></tr>
                                    <tr><td><strong>External:</strong></td><td>${data.is_external ? 'Yes' : 'No'}</td></tr>
                                    <tr><td><strong>Parent:</strong></td><td>${data.parent || 'Root'}</td></tr>
                                    <tr><td><strong>Children:</strong></td><td>${data.children_count}</td></tr>
                                    <tr><td><strong>Icon:</strong></td><td>${data.icon ? '<i class="' + data.icon + '"></i> ' + data.icon : 'N/A'}</td></tr>
                                    <tr><td><strong>Roles:</strong></td><td>${data.roles.length > 0 ? data.roles.join(', ') : 'No roles assigned'}</td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Breadcrumb</h6>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        ${data.breadcrumb.map(item => '<li class="breadcrumb-item">' + item + '</li>').join('')}
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    `;
                    $('#previewContent').html(content);
                    $('#previewModal').modal('show');
                } else {
                    toastr.error('Failed to load menu preview');
                }
            },
            error: function() {
                toastr.error('Failed to load menu preview');
            }
        });
    };

    // Duplicate menu function
    window.duplicateMenu = function(menuId) {
        if (confirm('Are you sure you want to duplicate this menu?')) {
            $.ajax({
                url: '/admin/menus/' + menuId + '/duplicate',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        table.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Failed to duplicate menu');
                }
            });
        }
    };

    // Delete menu function
    var deleteMenuId = null;
    window.deleteMenu = function(menuId) {
        deleteMenuId = menuId;
        $('#deleteModal').modal('show');
    };

    $('#confirmDelete').click(function() {
        if (deleteMenuId) {
            $.ajax({
                url: '/admin/menus/' + deleteMenuId,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        table.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                    $('#deleteModal').modal('hide');
                    deleteMenuId = null;
                },
                error: function() {
                    toastr.error('Failed to delete menu');
                    $('#deleteModal').modal('hide');
                    deleteMenuId = null;
                }
            });
        }
    });

    // Make hierarchy items sortable (for future implementation)
    $('.menu-hierarchy').sortable({
        items: '.menu-item',
        handle: '.sortable-handle',
        placeholder: 'ui-sortable-placeholder',
        update: function(event, ui) {
            // Implementation for updating menu order
            var items = [];
            $('.menu-item').each(function(index) {
                items.push({
                    id: $(this).data('menu-id'),
                    sort_order: index + 1
                });
            });
            
            // Send AJAX request to update order
            $.ajax({
                url: '{{ route("admin.menus.update-order") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    items: items
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        table.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Failed to update menu order');
                }
            });
        }
    });
});
</script>
@endpush