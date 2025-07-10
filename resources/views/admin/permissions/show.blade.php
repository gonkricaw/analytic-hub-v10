@extends('layouts.app')

@section('title', 'Permission Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>
                        Permission Details: {{ $permission->display_name }}
                    </h4>
                    <div>
                        @if(!$permission->is_system)
                            <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning me-2">
                                <i class="fas fa-edit me-1"></i>
                                Edit
                            </a>
                        @endif
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Permissions
                        </a>
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

                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <div class="card bg-secondary mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Basic Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-dark table-borderless">
                                        <tr>
                                            <td class="fw-bold">Permission Name:</td>
                                            <td>
                                                <code class="text-warning">{{ $permission->name }}</code>
                                                @if($permission->is_system)
                                                    <span class="badge bg-warning text-dark ms-2">System</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Display Name:</td>
                                            <td>{{ $permission->display_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Module:</td>
                                            <td>
                                                <span class="badge bg-info">{{ $permission->module }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Status:</td>
                                            <td>
                                                @if($permission->status === 'active')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times-circle me-1"></i>
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Level:</td>
                                            <td>
                                                <span class="badge bg-primary">{{ $permission->level }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Type:</td>
                                            <td>
                                                @if($permission->is_system)
                                                    <span class="badge bg-warning text-dark">System Permission</span>
                                                @else
                                                    <span class="badge bg-success">Custom Permission</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="col-md-6">
                            <div class="card bg-secondary mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Statistics
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="border border-secondary rounded p-3">
                                                <h3 class="text-primary mb-1">{{ $permission->roles->count() }}</h3>
                                                <small class="text-muted">Assigned Roles</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="border border-secondary rounded p-3">
                                                <h3 class="text-info mb-1">{{ $permission->children->count() }}</h3>
                                                <small class="text-muted">Child Permissions</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border border-secondary rounded p-3">
                                                <h3 class="text-success mb-1">{{ $permission->created_at->format('M d, Y') }}</h3>
                                                <small class="text-muted">Created</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border border-secondary rounded p-3">
                                                <h3 class="text-warning mb-1">{{ $permission->updated_at->format('M d, Y') }}</h3>
                                                <small class="text-muted">Last Updated</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    @if($permission->description)
                        <div class="card bg-secondary mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-align-left me-2"></i>
                                    Description
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">{{ $permission->description }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Permission Hierarchy -->
                    <div class="card bg-secondary mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-sitemap me-2"></i>
                                Permission Hierarchy
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Parent Permission</h6>
                                    @if($permission->parent)
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-level-up-alt text-primary me-2"></i>
                                            <div>
                                                <a href="{{ route('admin.permissions.show', $permission->parent) }}" 
                                                   class="text-decoration-none">
                                                    {{ $permission->parent->display_name }}
                                                </a>
                                                <br>
                                                <small class="text-muted">{{ $permission->parent->name }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-muted">
                                            <i class="fas fa-minus-circle me-2"></i>
                                            No parent (Root permission)
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Child Permissions ({{ $permission->children->count() }})</h6>
                                    @if($permission->children->count() > 0)
                                        <div class="max-height-200 overflow-auto">
                                            @foreach($permission->children as $child)
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-level-down-alt text-success me-2"></i>
                                                    <div>
                                                        <a href="{{ route('admin.permissions.show', $child) }}" 
                                                           class="text-decoration-none">
                                                            {{ $child->display_name }}
                                                        </a>
                                                        <br>
                                                        <small class="text-muted">{{ $child->name }}</small>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-muted">
                                            <i class="fas fa-minus-circle me-2"></i>
                                            No child permissions
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Roles -->
                    <div class="card bg-secondary mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-users-cog me-2"></i>
                                Assigned Roles ({{ $permission->roles->count() }})
                            </h5>
                            @if($permission->roles->count() > 5)
                                <button type="button" class="btn btn-sm btn-outline-light" id="toggleAllRoles">
                                    <i class="fas fa-eye me-1"></i>
                                    Show All
                                </button>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($permission->roles->count() > 0)
                                <div class="row" id="rolesContainer">
                                    @foreach($permission->roles->take(5) as $index => $role)
                                        <div class="col-md-6 mb-3 role-item {{ $index >= 5 ? 'd-none' : '' }}">
                                            <div class="border border-secondary rounded p-3">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <a href="{{ route('admin.roles.show', $role) }}" 
                                                               class="text-decoration-none">
                                                                {{ $role->display_name }}
                                                            </a>
                                                        </h6>
                                                        <p class="text-muted mb-1">{{ $role->name }}</p>
                                                        <small class="text-muted">
                                                            Level: {{ $role->level }} | 
                                                            Users: {{ $role->users->count() }}
                                                        </small>
                                                    </div>
                                                    <div>
                                                        @if($role->is_system)
                                                            <span class="badge bg-warning text-dark">System</span>
                                                        @endif
                                                        @if($role->is_default)
                                                            <span class="badge bg-info">Default</span>
                                                        @endif
                                                        <span class="badge bg-{{ $role->status === 'active' ? 'success' : 'danger' }}">
                                                            {{ ucfirst($role->status) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    @if($permission->roles->count() > 5)
                                        @foreach($permission->roles->skip(5) as $index => $role)
                                            <div class="col-md-6 mb-3 role-item d-none additional-role">
                                                <div class="border border-secondary rounded p-3">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                <a href="{{ route('admin.roles.show', $role) }}" 
                                                                   class="text-decoration-none">
                                                                    {{ $role->display_name }}
                                                                </a>
                                                            </h6>
                                                            <p class="text-muted mb-1">{{ $role->name }}</p>
                                                            <small class="text-muted">
                                                                Level: {{ $role->level }} | 
                                                                Users: {{ $role->users->count() }}
                                                            </small>
                                                        </div>
                                                        <div>
                                                            @if($role->is_system)
                                                                <span class="badge bg-warning text-dark">System</span>
                                                            @endif
                                                            @if($role->is_default)
                                                                <span class="badge bg-info">Default</span>
                                                            @endif
                                                            <span class="badge bg-{{ $role->status === 'active' ? 'success' : 'danger' }}">
                                                                {{ ucfirst($role->status) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-users-slash fa-3x mb-3"></i>
                                    <p class="mb-0">This permission is not assigned to any roles.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Audit Information -->
                    <div class="card bg-secondary">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Audit Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-dark table-borderless">
                                        <tr>
                                            <td class="fw-bold">Created At:</td>
                                            <td>{{ $permission->created_at->format('F j, Y \\a\\t g:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Updated At:</td>
                                            <td>{{ $permission->updated_at->format('F j, Y \\a\\t g:i A') }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-dark table-borderless">
                                        <tr>
                                            <td class="fw-bold">Permission ID:</td>
                                            <td><code class="text-info">{{ $permission->id }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Last Modified:</td>
                                            <td>{{ $permission->updated_at->diffForHumans() }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Permissions
                        </a>
                        
                        <div>
                            @if(!$permission->is_system)
                                <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning me-2">
                                    <i class="fas fa-edit me-1"></i>
                                    Edit Permission
                                </a>
                                
                                @if($permission->roles->count() === 0 && $permission->children->count() === 0)
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                        <i class="fas fa-trash me-1"></i>
                                        Delete Permission
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
@if(!$permission->is_system && $permission->roles->count() === 0 && $permission->children->count() === 0)
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
                <p>Are you sure you want to delete the permission <strong>"{{ $permission->display_name }}"</strong>?</p>
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
                <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        Delete Permission
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
    .max-height-200 {
        max-height: 200px;
    }
    
    .role-item {
        transition: all 0.3s ease;
    }
    
    .role-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }
    
    .table-dark td {
        border: none;
        padding: 0.5rem 0;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    code {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle show all roles
    $('#toggleAllRoles').on('click', function() {
        const button = $(this);
        const additionalRoles = $('.additional-role');
        
        if (additionalRoles.hasClass('d-none')) {
            additionalRoles.removeClass('d-none');
            button.html('<i class="fas fa-eye-slash me-1"></i>Show Less');
        } else {
            additionalRoles.addClass('d-none');
            button.html('<i class="fas fa-eye me-1"></i>Show All');
        }
    });
});
</script>
@endpush