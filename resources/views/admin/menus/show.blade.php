@extends('layouts.app')

@section('title', isset($menu) ? 'Menu Details: ' . $menu->title : 'Menu Details')

@section('content')
<div class="container-fluid">
@if(!isset($menu))
    <div class="alert alert-danger">
        <h4>Menu not found or not accessible</h4>
        <p>The requested menu could not be loaded. Please check if the menu exists and you have permission to access it.</p>
        <a href="{{ route('admin.menus.index') }}" class="btn btn-primary">Back to Menu List</a>
    </div>
@else
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-white">Menu Details</h1>
                    <p class="text-muted mb-0">Viewing menu: {{ $menu->title }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.menus.edit', $menu->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button type="button" class="btn btn-warning" onclick="duplicateMenu({{ $menu->id }})">
                        <i class="fas fa-copy"></i> Duplicate
                    </button>
                    <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Menus
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Information -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="font-weight-bold" width="30%">Name:</td>
                                    <td>
                                        <code>{{ $menu->name }}</code>
                                        @if($menu->is_system_menu)
                                            <span class="badge badge-info ml-2">System Menu</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Title:</td>
                                    <td>{{ $menu->title }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Description:</td>
                                    <td>{{ $menu->description ?: 'No description provided' }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">URL:</td>
                                    <td>
                                        @if($menu->url)
                                            @if($menu->is_external)
                                                <a href="{{ $menu->url }}" target="{{ $menu->target }}" class="text-primary">
                                                    {{ $menu->url }}
                                                    <i class="fas fa-external-link-alt ml-1"></i>
                                                </a>
                                            @else
                                                <a href="{{ $menu->url }}" class="text-primary">{{ $menu->url }}</a>
                                            @endif
                                        @else
                                            <span class="text-muted">No URL specified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Icon:</td>
                                    <td>
                                        @if($menu->icon)
                                            <i class="{{ $menu->icon }}"></i> 
                                            <code>{{ $menu->icon }}</code>
                                        @else
                                            <span class="text-muted">No icon specified</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="font-weight-bold" width="30%">Type:</td>
                                    <td>
                                        <span class="badge badge-secondary">{{ ucfirst($menu->type) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Target:</td>
                                    <td><code>{{ $menu->target }}</code></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Level:</td>
                                    <td>
                                        <span class="badge badge-info">Level {{ $menu->level }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Sort Order:</td>
                                    <td>{{ $menu->sort_order }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Status:</td>
                                    <td>
                                        @if($menu->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                        
                                        @if($menu->is_external)
                                            <span class="badge badge-warning ml-1">External</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hierarchy Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sitemap"></i> Hierarchy Information
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Breadcrumb -->
                    <div class="mb-3">
                        <h6>Breadcrumb Path:</h6>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                @foreach($breadcrumb as $item)
                                    @if($loop->last)
                                        <li class="breadcrumb-item active" aria-current="page">{{ $item }}</li>
                                    @else
                                        <li class="breadcrumb-item">{{ $item }}</li>
                                    @endif
                                @endforeach
                            </ol>
                        </nav>
                    </div>

                    <!-- Parent Menu -->
                    @if($menu->parent)
                        <div class="mb-3">
                            <h6>Parent Menu:</h6>
                            <div class="d-flex align-items-center">
                                @if($menu->parent->icon)
                                    <i class="{{ $menu->parent->icon }} mr-2"></i>
                                @endif
                                <a href="{{ route('admin.menus.show', $menu->parent->id) }}" class="text-primary">
                                    {{ $menu->parent->title }}
                                </a>
                                <span class="text-muted ml-2">({{ $menu->parent->name }})</span>
                            </div>
                        </div>
                    @else
                        <div class="mb-3">
                            <h6>Parent Menu:</h6>
                            <span class="text-muted">This is a root menu</span>
                        </div>
                    @endif

                    <!-- Children Menus -->
                    @if($menu->children->count() > 0)
                        <div class="mb-3">
                            <h6>Child Menus ({{ $menu->children->count() }}):</h6>
                            <div class="list-group">
                                @foreach($menu->children->sortBy('sort_order') as $child)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            @if($child->icon)
                                                <i class="{{ $child->icon }} mr-2"></i>
                                            @endif
                                            <div>
                                                <a href="{{ route('admin.menus.show', $child->id) }}" class="text-primary">
                                                    {{ $child->title }}
                                                </a>
                                                <small class="text-muted d-block">{{ $child->name }}</small>
                                            </div>
                                        </div>
                                        <div>
                                            @if($child->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                            <span class="badge badge-light ml-1">Order: {{ $child->sort_order }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="mb-3">
                            <h6>Child Menus:</h6>
                            <span class="text-muted">No child menus</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Access Control -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt"></i> Access Control
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Required Permission:</h6>
                            @if($menu->requiredPermission)
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-key text-warning mr-2"></i>
                                    <span class="badge badge-warning">{{ $menu->requiredPermission->name }}</span>
                                </div>
                                <small class="text-muted">{{ $menu->requiredPermission->description }}</small>
                            @else
                                <span class="text-muted">No permission required</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6>Required Roles:</h6>
                            @if($menu->required_roles && count($menu->required_roles) > 0)
                                <div class="d-flex flex-wrap">
                                    @foreach($menu->required_roles as $role)
                                        <span class="badge badge-info mr-1 mb-1">{{ $role }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">No roles required</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Roles Assignment -->
            @if($menu->roles->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users"></i> Assigned Roles
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($menu->roles as $role)
                                <div class="col-md-4 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tag text-primary mr-2"></i>
                                        <span class="badge badge-primary">{{ $role->name }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Menu Preview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-eye"></i> Menu Preview
                    </h6>
                </div>
                <div class="card-body">
                    <div class="menu-preview">
                        <div class="nav-item">
                            <a href="{{ $menu->url ?: '#' }}" class="nav-link" target="{{ $menu->target }}">
                                @if($menu->icon)
                                    <i class="{{ $menu->icon }}"></i>
                                @else
                                    <i class="fas fa-circle" style="font-size: 0.8em;"></i>
                                @endif
                                <span>{{ $menu->title }}</span>
                                @if($menu->is_external)
                                    <i class="fas fa-external-link-alt ml-1" style="font-size: 0.7em;"></i>
                                @endif
                            </a>
                        </div>
                    </div>
                    @if($menu->url)
                        <div class="mt-2">
                            <small class="text-muted">Click to test the link</small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- System Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-database"></i> System Information
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="font-weight-bold">ID:</td>
                            <td>{{ $menu->id }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">UUID:</td>
                            <td><small class="text-monospace">{{ $menu->uuid }}</small></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Created:</td>
                            <td>
                                <small>{{ $menu->created_at->format('M d, Y H:i:s') }}</small>
                                <br>
                                <small class="text-muted">{{ $menu->created_at->diffForHumans() }}</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Updated:</td>
                            <td>
                                <small>{{ $menu->updated_at->format('M d, Y H:i:s') }}</small>
                                <br>
                                <small class="text-muted">{{ $menu->updated_at->diffForHumans() }}</small>
                            </td>
                        </tr>
                        @if($menu->deleted_at)
                            <tr>
                                <td class="font-weight-bold">Deleted:</td>
                                <td>
                                    <small class="text-danger">{{ $menu->deleted_at->format('M d, Y H:i:s') }}</small>
                                    <br>
                                    <small class="text-muted">{{ $menu->deleted_at->diffForHumans() }}</small>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.menus.edit', $menu->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Menu
                        </a>
                        <button type="button" class="btn btn-warning btn-sm" onclick="duplicateMenu({{ $menu->id }})">
                            <i class="fas fa-copy"></i> Duplicate Menu
                        </button>
                        <a href="{{ route('admin.menus.create', ['parent_id' => $menu->id]) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Add Child Menu
                        </a>
                        <button type="button" class="btn btn-info btn-sm" onclick="toggleMenuStatus({{ $menu->id }})">
                            @if($menu->is_active)
                                <i class="fas fa-toggle-off"></i> Deactivate
                            @else
                                <i class="fas fa-toggle-on"></i> Activate
                            @endif
                        </button>
                        @unless($menu->is_system_menu)
                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteMenu({{ $menu->id }})">
                                <i class="fas fa-trash"></i> Delete Menu
                            </button>
                        @endunless
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-compass"></i> Navigation
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($menu->parent)
                            <a href="{{ route('admin.menus.show', $menu->parent->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-up"></i> Parent: {{ $menu->parent->title }}
                            </a>
                        @endif
                        
                        @if($previousSibling)
                            <a href="{{ route('admin.menus.show', $previousSibling->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Previous: {{ $previousSibling->title }}
                            </a>
                        @endif
                        
                        @if($nextSibling)
                            <a href="{{ route('admin.menus.show', $nextSibling->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-right"></i> Next: {{ $nextSibling->title }}
                            </a>
                        @endif
                        
                        @if($menu->children->count() > 0)
                            <a href="{{ route('admin.menus.show', $menu->children->first()->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-down"></i> First Child: {{ $menu->children->first()->title }}
                            </a>
                        @endif
                    </div>
                </div>
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
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This action cannot be undone.
                    @if($menu->children->count() > 0)
                        <br><strong>Note:</strong> This menu has {{ $menu->children->count() }} child menu(s) that will also be deleted.
                    @endif
                </div>
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
.menu-preview {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
}

.menu-preview .nav-link {
    color: #495057;
    padding: 0.5rem 0.75rem;
    border-radius: 0.25rem;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.menu-preview .nav-link:hover {
    background-color: #e9ecef;
    text-decoration: none;
}

.menu-preview .nav-link i {
    margin-right: 0.5rem;
    width: 1rem;
    text-align: center;
}

.table-borderless td {
    border: none;
    padding: 0.5rem 0;
}

.text-monospace {
    font-family: 'Courier New', monospace;
    font-size: 0.8em;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}

.list-group-item {
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 0.25rem;
    margin-bottom: 0.25rem;
}

.badge {
    font-size: 0.75em;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle menu status
    window.toggleMenuStatus = function(menuId) {
        $.ajax({
            url: '/admin/menus/' + menuId + '/toggle-status',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // Reload the page to reflect changes
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Failed to toggle menu status');
                }
            },
            error: function(xhr) {
                var errorMessage = 'Failed to toggle menu status';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            }
        });
    };

    // Duplicate menu
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
                        setTimeout(function() {
                            window.location.href = '{{ route("admin.menus.index") }}';
                        }, 1500);
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

    // Delete menu
    window.deleteMenu = function(menuId) {
        $('#deleteModal').modal('show');
    };

    $('#confirmDelete').click(function() {
        $.ajax({
            url: '/admin/menus/{{ $menu->id }}',
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(function() {
                        window.location.href = '{{ route("admin.menus.index") }}';
                    }, 1500);
                } else {
                    toastr.error(response.message);
                }
                $('#deleteModal').modal('hide');
            },
            error: function() {
                toastr.error('Failed to delete menu');
                $('#deleteModal').modal('hide');
            }
        });
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endpush
@endif