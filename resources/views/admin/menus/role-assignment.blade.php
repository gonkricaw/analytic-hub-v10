@extends('layouts.admin')

@section('title', isset($menu) ? 'Menu Role Assignment - ' . $menu->title : 'Menu Role Assignment')

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
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users-cog"></i> Menu Role Assignment
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.menus.index') }}">Menus</a></li>
                    <li class="breadcrumb-item">{{ isset($menu) ? $menu->title : 'Menu' }}</li>
                    <li class="breadcrumb-item active" aria-current="page">Role Assignment</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Menus
            </a>
            <button type="button" class="btn btn-info" id="clearCacheBtn">
                <i class="fas fa-sync-alt"></i> Clear Cache
            </button>
        </div>
    </div>

    <!-- Menu Information Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="card-title mb-2">
                                @if(isset($menu) && $menu->icon)
                                    <i class="{{ $menu->icon }}"></i>
                                @endif
                                {{ isset($menu) ? $menu->title : 'Menu' }}
                                @if(isset($menu) && !$menu->is_active)
                                    <span class="badge badge-warning ml-2">Inactive</span>
                                @endif
                            </h5>
                            <p class="card-text text-muted mb-1">{{ isset($menu) ? $menu->description : '' }}</p>
                            <small class="text-muted">
                                <strong>URL:</strong> {{ isset($menu) ? ($menu->url ?: 'N/A') : 'N/A' }} |
                                <strong>Level:</strong> {{ isset($menu) ? $menu->level : 'N/A' }} |
                                <strong>Type:</strong> {{ isset($menu) ? ucfirst($menu->type) : 'N/A' }}
                            </small>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="text-muted">
                                <small>
                                    <strong>Currently Assigned:</strong><br>
                                    <span class="badge badge-primary">{{ isset($menu) ? $menu->roles->count() : 0 }} Role(s)</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Assignment Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-plus"></i> Assign Roles
                    </h6>
                </div>
                <div class="card-body">
                    <form id="roleAssignmentForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="roles">Select Roles <span class="text-danger">*</span></label>
                                    <select name="roles[]" id="roles" class="form-control select2" multiple required>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" 
                                                {{ in_array($role->id, $assignedRoleIds) ? 'selected' : '' }}>
                                                {{ $role->name }}
                                                @if($role->description)
                                                    - {{ $role->description }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Select one or more roles to assign to this menu.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="access_type">Access Type</label>
                                    <select name="access_type" id="access_type" class="form-control">
                                        <option value="view">View Only</option>
                                        <option value="edit">Edit Access</option>
                                        <option value="full">Full Access</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Define the level of access for assigned roles.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="font-weight-bold">Visibility Options</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="is_visible" name="is_visible" value="1" checked>
                                                <label class="custom-control-label" for="is_visible">
                                                    Visible to Role
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="show_in_navigation" name="show_in_navigation" value="1" checked>
                                                <label class="custom-control-label" for="show_in_navigation">
                                                    Show in Navigation
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="show_children" name="show_children" value="1" checked>
                                                <label class="custom-control-label" for="show_children">
                                                    Show Children
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Role Assignment
                            </button>
                            <button type="button" class="btn btn-secondary" id="resetForm">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Current Assignments -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Current Assignments
                    </h6>
                </div>
                <div class="card-body">
                    <div id="currentAssignments">
                        @if(isset($menu) && $menu->roles->count() > 0)
                            @foreach($menu->roles as $role)
                                <div class="d-flex justify-content-between align-items-center mb-2 role-item" data-role-id="{{ $role->id }}">
                                    <div>
                                        <span class="badge badge-primary">{{ $role->name }}</span>
                                        @if($role->pivot->access_type)
                                            <small class="text-muted d-block">{{ ucfirst($role->pivot->access_type) }} Access</small>
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-role" data-role-id="{{ $role->id }}" data-role-name="{{ $role->name }}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted" id="noAssignments">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <p>No roles assigned yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm" id="assignAllRoles">
                            <i class="fas fa-check-double"></i> Assign All Active Roles
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" id="removeAllRoles">
                            <i class="fas fa-times-circle"></i> Remove All Roles
                        </button>
                        <hr>
                        <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-list"></i> Back to Menu List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendor/select2/css/select2.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}" rel="stylesheet">
<style>
.role-item {
    padding: 8px;
    border: 1px solid #e3e6f0;
    border-radius: 4px;
    background-color: #f8f9fc;
}

.role-item:hover {
    background-color: #eaecf4;
}

.select2-container--bootstrap4 .select2-selection--multiple {
    min-height: calc(1.5em + 0.75rem + 2px);
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/select2/js/select2.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('#roles').select2({
        theme: 'bootstrap4',
        placeholder: 'Select roles...',
        allowClear: true
    });

    // Role Assignment Form
    $('#roleAssignmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            roles: $('#roles').val(),
            access_type: $('#access_type').val(),
            is_visible: $('#is_visible').is(':checked'),
            show_in_navigation: $('#show_in_navigation').is(':checked'),
            show_children: $('#show_children').is(':checked'),
            _token: $('input[name="_token"]').val()
        };

        if (!formData.roles || formData.roles.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Roles Selected',
                text: 'Please select at least one role to assign.'
            });
            return;
        }

        $.ajax({
            url: @if(isset($menu)) '{{ route("admin.menus.assign-roles", $menu->id) }}' @else '#' @endif,
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                let message = 'An error occurred while updating role assignments.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message
                });
            },
            complete: function() {
                $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save"></i> Update Role Assignment');
            }
        });
    });

    // Remove individual role
    $(document).on('click', '.remove-role', function() {
        const roleId = $(this).data('role-id');
        const roleName = $(this).data('role-name');
        const $roleItem = $(this).closest('.role-item');

        Swal.fire({
            title: 'Remove Role?',
            text: `Are you sure you want to remove "${roleName}" from this menu?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: @if(isset($menu)) `{{ route('admin.menus.remove-role', ['menu' => $menu->id, 'role' => '__ROLE_ID__']) }}`.replace('__ROLE_ID__', roleId) @else '#' @endif,
                    method: 'DELETE',
                    data: {
                        _token: $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $roleItem.fadeOut(300, function() {
                                $(this).remove();
                                // Update select2
                                $('#roles option[value="' + roleId + '"]').prop('selected', false);
                                $('#roles').trigger('change');
                                
                                // Check if no assignments left
                                if ($('#currentAssignments .role-item').length === 0) {
                                    $('#currentAssignments').html(`
                                        <div class="text-center text-muted" id="noAssignments">
                                            <i class="fas fa-users fa-2x mb-2"></i>
                                            <p>No roles assigned yet.</p>
                                        </div>
                                    `);
                                }
                            });
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Removed!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to remove role assignment.'
                        });
                    }
                });
            }
        });
    });

    // Reset form
    $('#resetForm').on('click', function() {
        $('#roles').val(@json(isset($assignedRoleIds) ? $assignedRoleIds : [])).trigger('change');
        $('#access_type').val('view');
        $('#is_visible, #show_in_navigation, #show_children').prop('checked', true);
    });

    // Assign all roles
    $('#assignAllRoles').on('click', function() {
        const allRoleIds = @json(isset($roles) ? $roles->pluck('id')->toArray() : []);
        $('#roles').val(allRoleIds).trigger('change');
    });

    // Remove all roles
    $('#removeAllRoles').on('click', function() {
        Swal.fire({
            title: 'Remove All Roles?',
            text: 'This will remove all role assignments from this menu.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove all!'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#roles').val([]).trigger('change');
                $('#roleAssignmentForm').submit();
            }
        });
    });

    // Clear cache
    $('#clearCacheBtn').on('click', function() {
        $.ajax({
            url: '{{ route("admin.menus.clear-role-cache") }}',
            method: 'GET',
            beforeSend: function() {
                $('#clearCacheBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Clearing...');
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cache Cleared!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to clear cache.'
                });
            },
            complete: function() {
                $('#clearCacheBtn').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Clear Cache');
            }
        });
    });
});
</script>
@endpush
@endif