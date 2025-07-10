@extends('layouts.app')

@section('title', 'Create Permission')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        Create New Permission
                    </h4>
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Permissions
                    </a>
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

                    <form action="{{ route('admin.permissions.store') }}" method="POST" id="permissionForm">
                        @csrf
                        
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
                                           value="{{ old('name') }}" 
                                           placeholder="e.g., users.create, posts.edit"
                                           required>
                                    <div class="form-text text-muted">
                                        Unique identifier for the permission (lowercase, dots for hierarchy)
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
                                           value="{{ old('display_name') }}" 
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
                                               value="{{ old('module') }}" 
                                               placeholder="e.g., users, posts, settings"
                                               list="modulesList"
                                               required>
                                        <button type="button" class="btn btn-outline-secondary" id="detectModule">
                                            <i class="fas fa-magic"></i>
                                        </button>
                                    </div>
                                    <datalist id="modulesList">
                                        <!-- Will be populated dynamically -->
                                    </datalist>
                                    <div class="form-text text-muted">
                                        Module or feature this permission belongs to
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
                                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                      placeholder="Brief description of what this permission allows">{{ old('description') }}</textarea>
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
                                                name="parent_id">
                                            <option value="">No Parent (Root Permission)</option>
                                            <!-- Will be populated dynamically -->
                                        </select>
                                        <div class="form-text text-muted">
                                            Select a parent permission to create a hierarchy
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
                                               value="{{ old('level', 1) }}" 
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
                        </div>

                        <!-- Quick Permission Templates -->
                        <div class="mb-4">
                            <h5 class="border-bottom border-secondary pb-2 mb-3">
                                <i class="fas fa-templates me-2"></i>
                                Quick Templates
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="btn-group flex-wrap" role="group">
                                        <button type="button" class="btn btn-outline-info template-btn" data-template="create">
                                            <i class="fas fa-plus me-1"></i>
                                            Create
                                        </button>
                                        <button type="button" class="btn btn-outline-info template-btn" data-template="read">
                                            <i class="fas fa-eye me-1"></i>
                                            Read/View
                                        </button>
                                        <button type="button" class="btn btn-outline-info template-btn" data-template="update">
                                            <i class="fas fa-edit me-1"></i>
                                            Update/Edit
                                        </button>
                                        <button type="button" class="btn btn-outline-info template-btn" data-template="delete">
                                            <i class="fas fa-trash me-1"></i>
                                            Delete
                                        </button>
                                        <button type="button" class="btn btn-outline-info template-btn" data-template="export">
                                            <i class="fas fa-download me-1"></i>
                                            Export
                                        </button>
                                        <button type="button" class="btn btn-outline-info template-btn" data-template="import">
                                            <i class="fas fa-upload me-1"></i>
                                            Import
                                        </button>
                                    </div>
                                    <div class="form-text text-muted mt-2">
                                        Click a template to auto-fill the form with common permission patterns
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Role Assignment Section -->
                        <div class="mb-4">
                            <h5 class="border-bottom border-secondary pb-2 mb-3">
                                <i class="fas fa-users-cog me-2"></i>
                                Assign to Roles
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
                                Create Permission
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
    
    .template-btn {
        margin: 0.25rem;
    }
    
    .template-btn.active {
        background-color: #FF7A00;
        border-color: #FF7A00;
        color: white;
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
    
    // Auto-generate permission name from display name
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
    
    // Template buttons
    $('.template-btn').on('click', function() {
        const template = $(this).data('template');
        const module = $('#module').val() || 'module';
        
        // Remove active class from all buttons
        $('.template-btn').removeClass('active');
        // Add active class to clicked button
        $(this).addClass('active');
        
        const templates = {
            'create': {
                name: module + '.create',
                display_name: 'Create ' + module.charAt(0).toUpperCase() + module.slice(1),
                description: 'Permission to create new ' + module + ' records'
            },
            'read': {
                name: module + '.read',
                display_name: 'View ' + module.charAt(0).toUpperCase() + module.slice(1),
                description: 'Permission to view ' + module + ' records'
            },
            'update': {
                name: module + '.update',
                display_name: 'Edit ' + module.charAt(0).toUpperCase() + module.slice(1),
                description: 'Permission to update existing ' + module + ' records'
            },
            'delete': {
                name: module + '.delete',
                display_name: 'Delete ' + module.charAt(0).toUpperCase() + module.slice(1),
                description: 'Permission to delete ' + module + ' records'
            },
            'export': {
                name: module + '.export',
                display_name: 'Export ' + module.charAt(0).toUpperCase() + module.slice(1),
                description: 'Permission to export ' + module + ' data'
            },
            'import': {
                name: module + '.import',
                display_name: 'Import ' + module.charAt(0).toUpperCase() + module.slice(1),
                description: 'Permission to import ' + module + ' data'
            }
        };
        
        if (templates[template]) {
            $('#name').val(templates[template].name);
            $('#display_name').val(templates[template].display_name);
            $('#description').val(templates[template].description);
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
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Creating...').prop('disabled', true);
        
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
            data: { hierarchy: true },
            success: function(response) {
                const select = $('#parent_id');
                select.find('option:not(:first)').remove();
                
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(permission) {
                        const indent = '&nbsp;'.repeat((permission.level - 1) * 4);
                        select.append(`<option value="${permission.id}" data-level="${permission.level}">${indent}${permission.display_name}</option>`);
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
        let html = '<div class="row">';
        
        roles.forEach((role, index) => {
            if (index % 2 === 0 && index > 0) {
                html += '</div><div class="row">';
            }
            
            html += `
                <div class="col-md-6">
                    <div class="role-item">
                        <div class="form-check">
                            <input class="form-check-input role-checkbox" 
                                   type="checkbox" 
                                   name="roles[]" 
                                   value="${role.id}" 
                                   id="role_${role.id}">
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
    }
});
</script>
@endpush