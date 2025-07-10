@extends('layouts.app')

@section('title', 'Role Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-eye me-2"></i>
                        Role Details: {{ $role->display_name }}
                        @if($role->is_system)
                            <span class="badge bg-warning text-dark ms-2">
                                <i class="fas fa-shield-alt me-1"></i>
                                System Role
                            </span>
                        @endif
                        @if($role->is_default)
                            <span class="badge bg-info ms-2">
                                <i class="fas fa-star me-1"></i>
                                Default
                            </span>
                        @endif
                    </h4>
                    <div>
                        @can('update', $role)
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning me-2">
                                <i class="fas fa-edit me-1"></i>
                                Edit
                            </a>
                        @endcan
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Roles
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Role Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-secondary">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Basic Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-dark table-borderless">
                                        <tr>
                                            <td class="fw-bold">Role Name:</td>
                                            <td><code>{{ $role->name }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Display Name:</td>
                                            <td>{{ $role->display_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Level:</td>
                                            <td>
                                                <span class="badge bg-primary">{{ $role->level }}</span>
                                                <small class="text-muted ms-2">(Lower = Higher Priority)</small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Status:</td>
                                            <td>
                                                @if($role->status === 'active')
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
                                            <td class="fw-bold">Type:</td>
                                            <td>
                                                @if($role->is_system)
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-shield-alt me-1"></i>
                                                        System Role
                                                    </span>
                                                @else
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-user-cog me-1"></i>
                                                        Custom Role
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($role->is_default)
                                        <tr>
                                            <td class="fw-bold">Default Role:</td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-star me-1"></i>
                                                    Yes
                                                </span>
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-secondary">
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
                                                <h3 class="text-primary mb-1">{{ $role->users->count() }}</h3>
                                                <small class="text-muted">Users Assigned</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="border border-secondary rounded p-3">
                                                <h3 class="text-success mb-1">{{ $role->permissions->count() }}</h3>
                                                <small class="text-muted">Permissions</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border border-secondary rounded p-3">
                                                <h3 class="text-info mb-1">{{ $role->created_at->diffForHumans() }}</h3>
                                                <small class="text-muted">Created</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border border-secondary rounded p-3">
                                                <h3 class="text-warning mb-1">{{ $role->updated_at->diffForHumans() }}</h3>
                                                <small class="text-muted">Last Updated</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    @if($role->description)
                    <div class="mb-4">
                        <div class="card bg-secondary">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-align-left me-2"></i>
                                    Description
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">{{ $role->description }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Assigned Users -->
                    <div class="mb-4">
                        <div class="card bg-secondary">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>
                                    Assigned Users ({{ $role->users->count() }})
                                </h5>
                                @if($role->users->count() > 5)
                                    <button class="btn btn-sm btn-outline-light" id="toggleAllUsers">
                                        <i class="fas fa-eye me-1"></i>
                                        Show All
                                    </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($role->users->count() > 0)
                                    <div class="row" id="usersContainer">
                                        @foreach($role->users->take(5) as $user)
                                            <div class="col-md-6 mb-2 user-item">
                                                <div class="d-flex align-items-center p-2 border border-secondary rounded">
                                                    <div class="me-3">
                                                        @if($user->avatar)
                                                            <img src="{{ $user->avatar }}" alt="{{ $user->full_name }}" class="rounded-circle" width="40" height="40">
                                                        @else
                                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-user text-white"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-bold">{{ $user->full_name }}</div>
                                                        <small class="text-muted">{{ $user->email }}</small>
                                                    </div>
                                                    <div>
                                                        @if($user->status === 'active')
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-danger">{{ ucfirst($user->status) }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        
                                        @if($role->users->count() > 5)
                                            @foreach($role->users->skip(5) as $user)
                                                <div class="col-md-6 mb-2 user-item d-none additional-users">
                                                    <div class="d-flex align-items-center p-2 border border-secondary rounded">
                                                        <div class="me-3">
                                                            @if($user->avatar)
                                                                <img src="{{ $user->avatar }}" alt="{{ $user->full_name }}" class="rounded-circle" width="40" height="40">
                                                            @else
                                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                    <i class="fas fa-user text-white"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-bold">{{ $user->full_name }}</div>
                                                            <small class="text-muted">{{ $user->email }}</small>
                                                        </div>
                                                        <div>
                                                            @if($user->status === 'active')
                                                                <span class="badge bg-success">Active</span>
                                                            @else
                                                                <span class="badge bg-danger">{{ ucfirst($user->status) }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                @else
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <p>No users assigned to this role yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Role Permissions -->
                    <div class="mb-4">
                        <div class="card bg-secondary">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-key me-2"></i>
                                    Permissions ({{ $role->permissions->count() }})
                                </h5>
                            </div>
                            <div class="card-body">
                                @if($role->permissions->count() > 0)
                                    @php
                                        $groupedPermissions = $role->permissions->groupBy('module');
                                    @endphp
                                    
                                    @foreach($groupedPermissions as $module => $permissions)
                                        <div class="permission-group mb-3">
                                            <div class="permission-group-header">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-cube me-2"></i>
                                                    {{ ucfirst($module ?: 'General') }} Module
                                                    <span class="badge bg-primary ms-2">{{ $permissions->count() }}</span>
                                                </h6>
                                            </div>
                                            <div class="permission-group-body">
                                                <div class="row">
                                                    @foreach($permissions as $permission)
                                                        <div class="col-md-6 mb-2">
                                                            <div class="d-flex align-items-center p-2 border border-secondary rounded">
                                                                <div class="me-3">
                                                                    <i class="fas fa-check-circle text-success"></i>
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <div class="fw-bold">{{ $permission->display_name }}</div>
                                                                    <small class="text-muted">{{ $permission->name }}</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-key fa-3x mb-3"></i>
                                        <p>No permissions assigned to this role yet.</p>
                                        @can('update', $role)
                                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-primary">
                                                <i class="fas fa-plus me-1"></i>
                                                Assign Permissions
                                            </a>
                                        @endcan
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Audit Information -->
                    <div class="mb-4">
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
                                                <td>{{ $role->created_at->format('M d, Y H:i:s') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Updated At:</td>
                                                <td>{{ $role->updated_at->format('M d, Y H:i:s') }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-dark table-borderless">
                                            <tr>
                                                <td class="fw-bold">Created:</td>
                                                <td>{{ $role->created_at->diffForHumans() }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Last Modified:</td>
                                                <td>{{ $role->updated_at->diffForHumans() }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Roles
                        </a>
                        <div>
                            @can('update', $role)
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning me-2">
                                    <i class="fas fa-edit me-1"></i>
                                    Edit Role
                                </a>
                            @endcan
                            @can('delete', $role)
                                @if(!$role->is_system && $role->users->count() === 0)
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                        <i class="fas fa-trash me-1"></i>
                                        Delete Role
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
@can('delete', $role)
@if(!$role->is_system && $role->users->count() === 0)
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
                <p>Are you sure you want to delete the role <strong>{{ $role->display_name }}</strong>?</p>
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
                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline">
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
@endif
@endcan
@endsection

@push('styles')
<style>
    .permission-group {
        background-color: #1a1a3a;
        border: 1px solid #444;
        border-radius: 0.375rem;
    }
    
    .permission-group-header {
        background-color: #252560;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #444;
        border-radius: 0.375rem 0.375rem 0 0;
    }
    
    .permission-group-body {
        padding: 1rem;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle show all users
    $('#toggleAllUsers').on('click', function() {
        const button = $(this);
        const additionalUsers = $('.additional-users');
        
        if (additionalUsers.hasClass('d-none')) {
            additionalUsers.removeClass('d-none');
            button.html('<i class="fas fa-eye-slash me-1"></i>Show Less');
        } else {
            additionalUsers.addClass('d-none');
            button.html('<i class="fas fa-eye me-1"></i>Show All');
        }
    });
});
</script>
@endpush