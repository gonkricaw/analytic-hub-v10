@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        Create New Role
                    </h4>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Roles
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

                    <form action="{{ route('admin.roles.store') }}" method="POST" id="roleForm">
                        @csrf
                        
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
                                           value="{{ old('name') }}" 
                                           placeholder="e.g., admin, manager, user"
                                           required>
                                    <div class="form-text text-muted">
                                        Unique identifier for the role (lowercase, no spaces)
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
                                           value="{{ old('level', 100) }}" 
                                           min="1" 
                                           max="1000"
                                           required>
                                    <div class="form-text text-muted">
                                        Lower numbers = higher hierarchy (1 = highest, 1000 = lowest)
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
                                      placeholder="Brief description of the role's purpose and responsibilities">{{ old('description') }}</textarea>
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
                                           {{ old('is_default') ? 'checked' : '' }}>
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

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-1"></i>
                                Create Role
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
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Load permissions on page load
    loadPermissions();
    
    // Auto-generate role name from display name
    $('#display_name').on('input', function() {
        const displayName = $(this).val();
        const roleName = displayName.toLowerCase()
                                   .replace(/[^a-z0-9\s]/g, '')
                                   .replace(/\s+/g, '_')
                                   .trim();
        $('#name').val(roleName);
    });
    
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
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Creating...').prop('disabled', true);
        
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
            html += `
                <div class="permission-group">
                    <div class="permission-group-header">
                        <div class="form-check">
                            <input class="form-check-input module-select-all" 
                                   type="checkbox" 
                                   id="module_${module}" 
                                   data-module="${module}">
                            <label class="form-check-label fw-bold" for="module_${module}">
                                <i class="fas fa-cube me-2"></i>
                                ${module.charAt(0).toUpperCase() + module.slice(1)} Module
                            </label>
                        </div>
                    </div>
                    <div class="permission-group-body">
                        <div class="row">
            `;
            
            groupedPermissions[module].forEach((permission, index) => {
                if (index % 2 === 0 && index > 0) {
                    html += '</div><div class="row">';
                }
                
                html += `
                    <div class="col-md-6">
                        <div class="permission-item">
                            <div class="form-check">
                                <input class="form-check-input permission-checkbox" 
                                       type="checkbox" 
                                       name="permissions[]" 
                                       value="${permission.id}" 
                                       id="permission_${permission.id}"
                                       data-module="${module}">
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
    }
});
</script>
@endpush