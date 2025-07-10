@extends('layouts.app')

@section('title', 'Create User')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User Management</a></li>
    <li class="breadcrumb-item active">Create User</li>
@endsection

@push('styles')
<style>
    .form-section {
        background-color: #2d3748;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .form-section h5 {
        color: #e2e8f0;
        border-bottom: 2px solid #4a5568;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    .required {
        color: #f56565;
    }
    .password-info {
        background-color: #2a4365;
        border: 1px solid #3182ce;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-top: 1rem;
    }
    .role-checkbox {
        background-color: #4a5568;
        border: 1px solid #718096;
        border-radius: 0.375rem;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        transition: all 0.2s;
    }
    .role-checkbox:hover {
        background-color: #2d3748;
    }
    .role-checkbox.selected {
        background-color: #3182ce;
        border-color: #63b3ed;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Create New User
                    </h4>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Users
                    </a>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.users.store') }}" method="POST" id="createUserForm">
                        @csrf

                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-user me-2"></i>Basic Information</h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">
                                            First Name <span class="required">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('first_name') is-invalid @enderror" 
                                               id="first_name" 
                                               name="first_name" 
                                               value="{{ old('first_name') }}" 
                                               required 
                                               maxlength="100"
                                               placeholder="Enter first name">
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">
                                            Last Name <span class="required">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('last_name') is-invalid @enderror" 
                                               id="last_name" 
                                               name="last_name" 
                                               value="{{ old('last_name') }}" 
                                               required 
                                               maxlength="100"
                                               placeholder="Enter last name">
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            Email Address <span class="required">*</span>
                                        </label>
                                        <input type="email" 
                                               class="form-control @error('email') is-invalid @enderror" 
                                               id="email" 
                                               name="email" 
                                               value="{{ old('email') }}" 
                                               required 
                                               maxlength="255"
                                               placeholder="Enter email address">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text text-muted">
                                            This will be used for login and notifications.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">
                                            Username <small class="text-muted">(Optional)</small>
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('username') is-invalid @enderror" 
                                               id="username" 
                                               name="username" 
                                               value="{{ old('username') }}" 
                                               maxlength="50"
                                               pattern="[a-zA-Z0-9._-]+"
                                               placeholder="Enter username (optional)">
                                        @error('username')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text text-muted">
                                            Only letters, numbers, dots, underscores, and hyphens allowed.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Professional Information Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-briefcase me-2"></i>Professional Information</h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" 
                                               class="form-control @error('department') is-invalid @enderror" 
                                               id="department" 
                                               name="department" 
                                               value="{{ old('department') }}" 
                                               maxlength="100"
                                               placeholder="Enter department">
                                        @error('department')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="position" class="form-label">Position/Title</label>
                                        <input type="text" 
                                               class="form-control @error('position') is-invalid @enderror" 
                                               id="position" 
                                               name="position" 
                                               value="{{ old('position') }}" 
                                               maxlength="100"
                                               placeholder="Enter position or job title">
                                        @error('position')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" 
                                               class="form-control @error('phone') is-invalid @enderror" 
                                               id="phone" 
                                               name="phone" 
                                               value="{{ old('phone') }}" 
                                               maxlength="20"
                                               placeholder="Enter phone number">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="email_notifications" 
                                                   name="email_notifications" 
                                                   value="1" 
                                                   {{ old('email_notifications', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="email_notifications">
                                                Enable Email Notifications
                                            </label>
                                        </div>
                                        <div class="form-text text-muted">
                                            User will receive system notifications via email.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="bio" class="form-label">Biography</label>
                                <textarea class="form-control @error('bio') is-invalid @enderror" 
                                          id="bio" 
                                          name="bio" 
                                          rows="3" 
                                          maxlength="1000"
                                          placeholder="Enter a brief biography (optional)">{{ old('bio') }}</textarea>
                                @error('bio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    <span id="bioCount">0</span>/1000 characters
                                </div>
                            </div>
                        </div>

                        <!-- Role Assignment Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-user-shield me-2"></i>Role Assignment</h5>
                            
                            @if($roles->count() > 0)
                                <div class="row">
                                    @foreach($roles as $role)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="role-checkbox" data-role-id="{{ $role->id }}">
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           id="role_{{ $role->id }}" 
                                                           name="roles[]" 
                                                           value="{{ $role->id }}"
                                                           {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100" for="role_{{ $role->id }}">
                                                        <div class="fw-bold">{{ $role->display_name }}</div>
                                                        <small class="text-muted">{{ $role->name }}</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('roles')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                                @error('roles.*')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No active roles available. Please create roles first.
                                </div>
                            @endif
                        </div>

                        <!-- Invitation Options Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-envelope me-2"></i>Invitation Options</h5>
                            
                            <div class="mb-3">
                                <label for="custom_message" class="form-label">Custom Message (Optional)</label>
                                <textarea class="form-control @error('custom_message') is-invalid @enderror" 
                                          id="custom_message" 
                                          name="custom_message" 
                                          rows="3" 
                                          maxlength="1000"
                                          placeholder="Add a personal message to include in the invitation email...">{{ old('custom_message') }}</textarea>
                                @error('custom_message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    <span id="messageCount">0</span>/1000 characters - This message will be included in the invitation email.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="template_id" class="form-label">Email Template (Optional)</label>
                                <select class="form-select @error('template_id') is-invalid @enderror" 
                                        id="template_id" 
                                        name="template_id">
                                    <option value="">Use Default Template</option>
                                    <!-- Template options will be populated via AJAX or server-side -->
                                </select>
                                @error('template_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    Select a custom email template or leave blank to use the default invitation template.
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Invitation Process:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>An invitation email will be automatically sent to the user's email address</li>
                                    <li>The email will contain login credentials and instructions</li>
                                    <li>The user will be required to change their password on first login</li>
                                    <li>You can resend the invitation from the user management page if needed</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Password Information -->
                        <div class="password-info">
                            <h6><i class="fas fa-key me-2"></i>Password Information</h6>
                            <p class="mb-2">
                                A temporary password will be automatically generated for this user with the following characteristics:
                            </p>
                            <ul class="mb-2">
                                <li>8 characters long</li>
                                <li>Contains uppercase letters (A-Z)</li>
                                <li>Contains lowercase letters (a-z)</li>
                                <li>Contains numbers (0-9)</li>
                            </ul>
                            <p class="mb-0 text-warning">
                                <i class="fas fa-info-circle me-1"></i>
                                The user will be required to change this password on their first login.
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                Cancel
                            </a>
                            
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-user-plus me-1"></i>
                                Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Bio character counter
    $('#bio').on('input', function() {
        const length = $(this).val().length;
        $('#bioCount').text(length);
        
        if (length > 900) {
            $('#bioCount').addClass('text-warning');
        } else {
            $('#bioCount').removeClass('text-warning');
        }
    });
    
    // Initialize bio counter
    $('#bio').trigger('input');
    
    // Custom message character counter
    $('#custom_message').on('input', function() {
        const length = $(this).val().length;
        $('#messageCount').text(length);
        
        if (length > 900) {
            $('#messageCount').addClass('text-warning');
        } else {
            $('#messageCount').removeClass('text-warning');
        }
    });
    
    // Initialize message counter
    $('#custom_message').trigger('input');
    
    // Role checkbox styling
    $('.role-checkbox input[type="checkbox"]').on('change', function() {
        const roleBox = $(this).closest('.role-checkbox');
        if ($(this).is(':checked')) {
            roleBox.addClass('selected');
        } else {
            roleBox.removeClass('selected');
        }
    });
    
    // Initialize role checkbox styling
    $('.role-checkbox input[type="checkbox"]:checked').each(function() {
        $(this).closest('.role-checkbox').addClass('selected');
    });
    
    // Click on role box to toggle checkbox
    $('.role-checkbox').on('click', function(e) {
        if (e.target.type !== 'checkbox' && e.target.tagName !== 'LABEL') {
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        }
    });
    
    // Form validation
    $('#createUserForm').on('submit', function(e) {
        const submitBtn = $('#submitBtn');
        
        // Disable submit button to prevent double submission
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Creating User...');
        
        // Re-enable button after 3 seconds in case of issues
        setTimeout(function() {
            submitBtn.prop('disabled', false);
            submitBtn.html('<i class="fas fa-user-plus me-1"></i>Create User');
        }, 3000);
    });
    
    // Auto-generate username from email (optional)
    $('#email').on('blur', function() {
        const email = $(this).val();
        const username = $('#username').val();
        
        // Only auto-generate if username is empty
        if (email && !username) {
            const emailUsername = email.split('@')[0];
            // Clean the username to match the pattern
            const cleanUsername = emailUsername.replace(/[^a-zA-Z0-9._-]/g, '');
            if (cleanUsername) {
                $('#username').val(cleanUsername);
            }
        }
    });
    
    // Real-time username validation
    $('#username').on('input', function() {
        const username = $(this).val();
        const pattern = /^[a-zA-Z0-9._-]+$/;
        
        if (username && !pattern.test(username)) {
            $(this).addClass('is-invalid');
            if (!$(this).siblings('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Username can only contain letters, numbers, dots, underscores, and hyphens.</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });
});
</script>
@endpush