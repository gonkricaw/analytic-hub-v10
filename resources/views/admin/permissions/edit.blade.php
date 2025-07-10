@extends('layouts.app')

@section('title', 'Edit Permission')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Edit Permission: {{ $permission->display_name }}
                    </h4>
                    <div>
                        <a href="{{ route('admin.permissions.show', $permission) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye me-1"></i>
                            View
                        </a>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Permissions
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

                    @if($permission->is_system)
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>System Permission:</strong> This is a system permission. Some fields may be restricted from editing.
                        </div>
                    @endif

                    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST" id="permissionForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-tag me-1"></i>
                                        Permission Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control bg-dark text-white border-secondary @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $permission->name) }}" 
                                           placeholder="e.g., users.create, posts.edit"
                                           {{ $permission->is_system ? 'readonly' : 'required' }}>
                                    <div class="form-text text-muted">
                                        Unique identifier for the permission (lowercase, dots for hierarchy)
                                        @if($permission->is_system)
                                            <br><strong>Note:</strong> System permission names cannot be changed
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
                                           value="{{ old('display_name', $permission->display_name) }}" 
                                           placeholder="e.g., Create Users, Edit Posts"
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
                                    <label for="module" class="form-label">
                                        <i class="fas fa-cube me-1"></i>
                                        Module <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control bg-dark text-white border-secondary @error('module') is-invalid @enderror" 
                                               id="module" 
                                               name="module" 
                                               value="{{ old('module', $permission->module) }}" 
                                               placeholder="e.g., users, posts, settings"
                                               list="modulesList"
                                               {{ $permission->is_system ? 'readonly' : 'required' }}>
                                        @if(!$permission->is_system)
                                            <button type="button" class="btn btn-outline-secondary" id="detectModule">
                                                <i class="fas fa-magic"></i>
                                            </button>
                                        @endif
                                    </div>
                                    <datalist id="modulesList">
                                        <!-- Will be populated dynamically -->
                                    </datalist>
                                    <div class="form-text text-muted">
                                        Module or feature this permission belongs to
                                        @if($permission->is_system)
                                            <br><strong>Note:</strong> System permission modules cannot be changed
                                        @endif
                                    </div>
                                    @error('module')
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
                                        <option value="active" {{ old('status', $permission->status) === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $permission->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                      placeholder="Brief description of what this permission allows">{{ old('description', $permission->description) }}</textarea>
                            <div class="form-text text-muted">
                                Optional description to help identify the permission's purpose
                            </div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Permission Hierarchy Section -->
                        <div class="mb-4">
                            <h5 class="border-bottom border-secondary pb-2 mb-3">
                                <i class="fas fa-sitemap me-2"></i>
                                Permission Hierarchy
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="parent_id" class="form-label">
                                            <i class="fas fa-level-up-alt me-1"></i>
                                            Parent Permission
                                        </label>
                                        <select class="form-select bg-dark text-white border-secondary @error('parent_id') is-invalid @enderror" 
                                                id="parent_id" 
                                                name="parent_id"
                                                {{ $permission->is_system ? 'disabled' : '' }}>
                                            <option value="">No Parent (Root Permission)</option>
                                            <!-- Will be populated dynamically -->
                                        </select>
                                        <div class="form-text text-muted">
                                            Select a parent permission to create a hierarchy
                                            @if($permission->is_system)
                                                <br><strong>Note:</strong> System permission hierarchy cannot be changed
                                            @endif
                                        </div>
                                        @error('parent_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="level" class="form-label">
                                            <i class="fas fa-layer-group me-1"></i>
                                            Permission Level
                                        </label>
                                        <input type="number" 
                                               class="form-control bg-dark text-white border-secondary @error('level') is-invalid @enderror" 
                                               id="level" 
                                               name="level" 
                                               value="{{ old('level', $permission->level) }}" 
                                               min="1" 
                                               max="10"
                                               readonly>
                                        <div class="form-text text-muted">
                                            Automatically calculated based on parent permission
                                        </div>
                                        @error('level')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            @if($permission->children->count() > 0)
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Child Permissions:</strong> This permission has {{ $permission->children->count() }} child permission(s). 
                                    Changing the hierarchy may affect these relationships.
                                </div>
                            @endif
                        </div>

                        <!-- Permission Statistics -->
                        <div class="mb-4">
                            <h5 class="border-bottom border-secondary pb-2 mb-3">
                                <i class="fas fa-chart-bar me-2"></i>
                                Permission Statistics
                            </h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-secondary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">{{ $permission->roles->count() }}</h5>
                                            <p class="card-text">Assigned Roles</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-secondary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">{{ $permission->children->count() }}</h5>
                                            <p class="card-text">Child Permissions</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-secondary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">{{ $permission->level }}</h5>
                                            <p class="card-text">Hierarchy Level</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-secondary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">
                                                @if($permission->status === 'active')
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

                        <!-- Role Assignment Section -->
                        <div class="mb-4">
                            <h5 class="border-bottom border-secondary pb-2 mb-3">
                                <i class="fas fa-users-cog me-2"></i>
                                Assign to Roles
                                <small class="text-muted ms-2">({{ $permission->roles->count() }} currently assigned)</small>
                            </h5>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="selectAllRoles">
                                        <label class="form-check-label fw-bold" for="selectAllRoles">
                                            Select All Roles
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="rolesContainer">
                                <div class="text-center py-3">
                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                    Loading roles...
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-1"></i>
                                Update Permission
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
    
    .role-item {
        padding: 0.5rem;
        border: 1px solid #444;
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
        background-color: #1a1a3a;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Load initial data
    loadModules();
    loadParentPermissions();
    loadRoles();
    
    // Auto-generate permission name from display name (only if not system permission)
    @if(!$permission->is_system)
    $('#display_name').on('input', function() {
        const displayName = $(this).val();
        const module = $('#module').val();
        
        let permissionName = displayName.toLowerCase()
                                       .replace(/[^a-z0-9\s]/g, '')
                                       .replace(/\s+/g, '_')
                                       .trim();
        
        if (module) {
            permissionName = module + '.' + permissionName;
        }
        
        $('#name').val(permissionName);
    });
    
    // Update permission name when module changes
    $('#module').on('input change', function() {
        const module = $(this).val();
        const displayName = $('#display_name').val();
        
        if (displayName) {
            let permissionName = displayName.toLowerCase()
                                           .replace(/[^a-z0-9\s]/g, '')
                                           .replace(/\s+/g, '_')
                                           .trim();
            
            if (module) {
                permissionName = module + '.' + permissionName;
            }
            
            $('#name').val(permissionName);
        }
    });
    
    // Detect module from permission name
    $('#detectModule').on('click', function() {
        const permissionName = $('#name').val();
        if (permissionName && permissionName.includes('.')) {
            const module = permissionName.split('.')[0];
            $('#module').val(module);
        }
    });
    @endif
    
    // Parent permission change
    $('#parent_id').on('change', function() {
        const parentId = $(this).val();
        if (parentId) {
            // Get parent level and set child level
            const parentOption = $(this).find('option:selected');
            const parentLevel = parseInt(parentOption.data('level')) || 0;
            $('#level').val(parentLevel + 1);
        } else {
            $('#level').val(1);
        }
    });
    
    // Select all roles checkbox
    $(document).on('change', '#selectAllRoles', function() {
        const isChecked = $(this).is(':checked');
        $('.role-checkbox').prop('checked', isChecked);
    });
    
    // Individual role checkbox change
    $(document).on('change', '.role-checkbox', function() {
        const totalRoles = $('.role-checkbox').length;
        const checkedRoles = $('.role-checkbox:checked').length;
        
        $('#selectAllRoles').prop('checked', totalRoles === checkedRoles);
    });
    
    // Form submission
    $('#permissionForm').on('submit', function(e) {
        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Updating...').prop('disabled', true);
        
        // Allow form to submit normally
        // The loading state will be reset on page reload or error
    });
    
    function loadModules() {
        $.ajax({
            url: '{{ route("admin.permissions.data") }}',
            method: 'GET',
            data: { modules_only: true },
            success: function(response) {
                const datalist = $('#modulesList');
                datalist.empty();
                
                if (response.modules && response.modules.length > 0) {
                    response.modules.forEach(function(module) {
                        if (module) {
                            datalist.append(`<option value="${module}">${module}</option>`);
                        }
                    });
                }
            },
            error: function() {
                console.error('Failed to load modules');
            }
        });
    }
    
    function loadParentPermissions() {
        $.ajax({
            url: '{{ route("admin.permissions.data") }}',
            method: 'GET',
            data: { 
                hierarchy: true,
                exclude: {{ $permission->id }}
            },
            success: function(response) {
                const select = $('#parent_id');
                select.find('option:not(:first)').remove();
                
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(permission) {
                        const indent = '&nbsp;'.repeat((permission.level - 1) * 4);
                        const selected = permission.id == {{ $permission->parent_id ?? 'null' }} ? 'selected' : '';
                        select.append(`<option value="${permission.id}" data-level="${permission.level}" ${selected}>${indent}${permission.display_name}</option>`);
                    });
                }
            },
            error: function() {
                console.error('Failed to load parent permissions');
            }
        });
    }
    
    function loadRoles() {
        $.ajax({
            url: '{{ route("admin.roles.data") }}',
            method: 'GET',
            data: { all: true },
            success: function(response) {
                renderRoles(response.data);
            },
            error: function() {
                $('#rolesContainer').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to load roles. Please refresh the page.
                    </div>
                `);
            }
        });
    }
    
    function renderRoles(roles) {
        // Get current permission roles
        const currentRoles = @json($permission->roles->pluck('id')->toArray());
        
        let html = '<div class="row">';
        
        roles.forEach((role, index) => {
            if (index % 2 === 0 && index > 0) {
                html += '</div><div class="row">';
            }
            
            const isChecked = currentRoles.includes(role.id);
            
            html += `
                <div class="col-md-6">
                    <div class="role-item">
                        <div class="form-check">
                            <input class="form-check-input role-checkbox" 
                                   type="checkbox" 
                                   name="roles[]" 
                                   value="${role.id}" 
                                   id="role_${role.id}"
                                   ${isChecked ? 'checked' : ''}>
                            <label class="form-check-label" for="role_${role.id}">
                                <strong>${role.display_name}</strong>
                                <br>
                                <small class="text-muted">${role.name}</small>
                                ${role.is_system ? '<span class="badge bg-warning text-dark ms-2">System</span>' : ''}
                            </label>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        $('#rolesContainer').html(html);
        
        // Update select all checkbox based on current state
        const totalRoles = $('.role-checkbox').length;
        const checkedRoles = $('.role-checkbox:checked').length;
        $('#selectAllRoles').prop('checked', totalRoles === checkedRoles);
    }
});
</script>
@endpush