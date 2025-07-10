@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Edit Role: {{ $role->display_name }}
                    </h4>
                    <div>
                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye me-1"></i>
                            View
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Roles
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($role->is_system)
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>System Role:</strong> This is a system role. Some fields may be restricted from editing.
                        </div>
                    @endif

                    <form action="{{ route('admin.roles.update', $role) }}" method="POST" id="roleForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-tag me-1"></i>
                                        Role Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control bg-dark text-white border-secondary @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $role->name) }}" 
                                           placeholder="e.g., admin, manager, user"
                                           {{ $role->is_system ? 'readonly' : 'required' }}>
                                    <div class="form-text text-muted">
                                        Unique identifier for the role (lowercase, no spaces)
                                        @if($role->is_system)
                                            <br><strong>Note:</strong> System role names cannot be changed
                                        @endif
                                    </div>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">
                                        <i class="fas fa-eye me-1"></i>
                                        Display Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control bg-dark text-white border-secondary @error('display_name') is-invalid @enderror" 
                                           id="display_name" 
                                           name="display_name" 
                                           value="{{ old('display_name', $role->display_name) }}" 
                                           placeholder="e.g., Administrator, Manager, User"
                                           required>
                                    <div class="form-text text-muted">
                                        Human-readable name shown in the interface
                                    </div>
                                    @error('display_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="level" class="form-label">
                                        <i class="fas fa-layer-group me-1"></i>
                                        Role Level <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control bg-dark text-white border-secondary @error('level') is-invalid @enderror" 
                                           id="level" 
                                           name="level" 
                                           value="{{ old('level', $role->level) }}" 
                                           min="1" 
                                           max="1000"
                                           {{ $role->is_system ? 'readonly' : 'required' }}>
                                    <div class="form-text text-muted">
                                        Lower numbers = higher hierarchy (1 = highest, 1000 = lowest)
                                        @if($role->is_system)
                                            <br><strong>Note:</strong> System role levels cannot be changed
                                        @endif
                                    </div>
                                    @error('level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-toggle-on me-1"></i>
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select bg-dark text-white border-secondary @error('status') is-invalid @enderror" 
                                            id="status" 
                                            name="status" 
                                            required>
                                        <option value="active" {{ old('status', $role->status) === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $role->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1"></i>
                                Description
                            </label>
                            <textarea class="form-control bg-dark text-white border-secondary @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Brief description of the role's purpose and responsibilities">{{ old('description', $role->description) }}</textarea>
                            <div class="form-text text-muted">
                                Optional description to help identify the role's purpose
                            </div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_default" 
                                           name="is_default" 
                                           value="1" 
                                           {{ old('is_default', $role->is_default) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_default">
                                        <i class="fas fa-star me-1"></i>
                                        Default Role
                                    </label>
                                    <div class="form-text text-muted">
                                        Automatically assign this role to new users
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permission Assignment Section -->
                        <div class="mb-4">
                            <h5 class="border-bottom border-secondary pb-2 mb-3">
                                <i class="fas fa-key me-2"></i>
                                Assign Permissions
                                <small class="text-muted ms-2">({{ $role->permissions->count() }} currently assigned)</small>
                            </h5>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="selectAllPermissions">
                                        <label class="form-check-label fw-bold" for="selectAllPermissions">
                                            Select All Permissions
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="permissionsContainer">
                                <div class="text-center py-3">
                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                    Loading permissions...
                                </div>
                            </div>
                        </div>

                        <!-- Role Statistics -->
                        <div class="mb-4">
                            <h5 class="border-bottom border-secondary pb-2 mb-3">
                                <i class="fas fa-chart-bar me-2"></i>
                                Role Statistics
                            </h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-secondary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">{{ $role->users->count() }}</h5>
                                            <p class="card-text">Users Assigned</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-secondary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">{{ $role->permissions->count() }}</h5>
                                            <p class="card-text">Permissions</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-secondary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">{{ $role->level }}</h5>
                                            <p class="card-text">Hierarchy Level</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-secondary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">
                                                @if($role->status === 'active')
                                                    <i class="fas fa-check-circle text-success"></i>
                                                @else
                                                    <i class="fas fa-times-circle text-danger"></i>
                                                @endif
                                            </h5>
                                            <p class="card-text">Status</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-1"></i>
                                Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .permission-group {
        background-color: #1a1a3a;
        border: 1px solid #444;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
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
    
    .permission-item {
        padding: 0.5rem 0;
        border-bottom: 1px solid #333;
    }
    
    .permission-item:last-child {
        border-bottom: none;
    }
    
    .form-control:focus,
    .form-select:focus {
        background-color: #2a2a70;
        border-color: #FF7A00;
        box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.25);
        color: white;
    }
    
    .form-check-input:checked {
        background-color: #FF7A00;
        border-color: #FF7A00;
    }
    
    .form-check-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.25);
    }
    
    .form-control[readonly] {
        background-color: #2a2a2a;
        opacity: 0.7;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Load permissions on page load
    loadPermissions();
    
    // Auto-generate role name from display name (only if not system role)
    @if(!$role->is_system)
    $('#display_name').on('input', function() {
        const displayName = $(this).val();
        const roleName = displayName.toLowerCase()
                                   .replace(/[^a-z0-9\s]/g, '')
                                   .replace(/\s+/g, '_')
                                   .trim();
        $('#name').val(roleName);
    });
    @endif
    
    // Select all permissions checkbox
    $(document).on('change', '#selectAllPermissions', function() {
        const isChecked = $(this).is(':checked');
        $('.permission-checkbox').prop('checked', isChecked);
    });
    
    // Individual permission checkbox change
    $(document).on('change', '.permission-checkbox', function() {
        const totalPermissions = $('.permission-checkbox').length;
        const checkedPermissions = $('.permission-checkbox:checked').length;
        
        $('#selectAllPermissions').prop('checked', totalPermissions === checkedPermissions);
    });
    
    // Module select all checkbox
    $(document).on('change', '.module-select-all', function() {
        const isChecked = $(this).is(':checked');
        const module = $(this).data('module');
        $(`.permission-checkbox[data-module="${module}"]`).prop('checked', isChecked);
        
        // Update main select all checkbox
        const totalPermissions = $('.permission-checkbox').length;
        const checkedPermissions = $('.permission-checkbox:checked').length;
        $('#selectAllPermissions').prop('checked', totalPermissions === checkedPermissions);
    });
    
    // Form submission
    $('#roleForm').on('submit', function(e) {
        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Updating...').prop('disabled', true);
        
        // Allow form to submit normally
        // The loading state will be reset on page reload or error
    });
    
    function loadPermissions() {
        $.ajax({
            url: '{{ route("admin.permissions.data") }}',
            method: 'GET',
            data: { all: true },
            success: function(response) {
                renderPermissions(response.data);
            },
            error: function() {
                $('#permissionsContainer').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to load permissions. Please refresh the page.
                    </div>
                `);
            }
        });
    }
    
    function renderPermissions(permissions) {
        // Get current role permissions
        const currentPermissions = @json($role->permissions->pluck('id')->toArray());
        
        // Group permissions by module
        const groupedPermissions = {};
        permissions.forEach(permission => {
            const module = permission.module || 'General';
            if (!groupedPermissions[module]) {
                groupedPermissions[module] = [];
            }
            groupedPermissions[module].push(permission);
        });
        
        let html = '';
        
        Object.keys(groupedPermissions).sort().forEach(module => {
            const modulePermissions = groupedPermissions[module];
            const modulePermissionIds = modulePermissions.map(p => p.id);
            const checkedModulePermissions = modulePermissionIds.filter(id => currentPermissions.includes(id));
            const isModuleFullyChecked = checkedModulePermissions.length === modulePermissions.length;
            
            html += `
                <div class="permission-group">
                    <div class="permission-group-header">
                        <div class="form-check">
                            <input class="form-check-input module-select-all" 
                                   type="checkbox" 
                                   id="module_${module}" 
                                   data-module="${module}"
                                   ${isModuleFullyChecked ? 'checked' : ''}>
                            <label class="form-check-label fw-bold" for="module_${module}">
                                <i class="fas fa-cube me-2"></i>
                                ${module.charAt(0).toUpperCase() + module.slice(1)} Module
                                <small class="text-muted">(${checkedModulePermissions.length}/${modulePermissions.length})</small>
                            </label>
                        </div>
                    </div>
                    <div class="permission-group-body">
                        <div class="row">
            `;
            
            modulePermissions.forEach((permission, index) => {
                if (index % 2 === 0 && index > 0) {
                    html += '</div><div class="row">';
                }
                
                const isChecked = currentPermissions.includes(permission.id);
                
                html += `
                    <div class="col-md-6">
                        <div class="permission-item">
                            <div class="form-check">
                                <input class="form-check-input permission-checkbox" 
                                       type="checkbox" 
                                       name="permissions[]" 
                                       value="${permission.id}" 
                                       id="permission_${permission.id}"
                                       data-module="${module}"
                                       ${isChecked ? 'checked' : ''}>
                                <label class="form-check-label" for="permission_${permission.id}">
                                    <strong>${permission.display_name}</strong>
                                    <br>
                                    <small class="text-muted">${permission.name}</small>
                                </label>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#permissionsContainer').html(html);
        
        // Update select all checkbox based on current state
        const totalPermissions = $('.permission-checkbox').length;
        const checkedPermissions = $('.permission-checkbox:checked').length;
        $('#selectAllPermissions').prop('checked', totalPermissions === checkedPermissions);
    }
});
</script>
@endpush