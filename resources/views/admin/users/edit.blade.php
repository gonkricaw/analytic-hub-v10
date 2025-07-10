@extends('layouts.app')

@section('title', 'Edit User')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User Management</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $user) }}">{{ $user->full_name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
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
    .user-status-badge {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
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
    .password-section {
        background-color: #2a4365;
        border: 1px solid #3182ce;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-top: 1rem;
    }
    .danger-zone {
        background-color: #742a2a;
        border: 1px solid #e53e3e;
        border-radius: 0.375rem;
        padding: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>
                            Edit User: {{ $user->full_name }}
                        </h4>
                        <div class="mt-2">
                            <span class="badge user-status-badge 
                                @if($user->status === 'active') bg-success
                                @elseif($user->status === 'suspended') bg-warning
                                @elseif($user->status === 'pending') bg-info
                                @else bg-danger
                                @endif">
                                {{ ucfirst($user->status) }}
                            </span>
                            @if($user->email_verified_at)
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-check-circle me-1"></i>Email Verified
                                </span>
                            @else
                                <span class="badge bg-warning ms-2">
                                    <i class="fas fa-exclamation-circle me-1"></i>Email Unverified
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye me-1"></i>
                            View Profile
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Users
                        </a>
                    </div>
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

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.users.update', $user) }}" method="POST" id="editUserForm">
                        @csrf
                        @method('PUT')

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
                                               value="{{ old('first_name', $user->first_name) }}" 
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
                                               value="{{ old('last_name', $user->last_name) }}" 
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
                                               value="{{ old('email', $user->email) }}" 
                                               required 
                                               maxlength="255"
                                               placeholder="Enter email address">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if($user->email !== old('email', $user->email))
                                            <div class="form-text text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Changing email will require re-verification.
                                            </div>
                                        @endif
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
                                               value="{{ old('username', $user->username) }}" 
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
                                               value="{{ old('department', $user->department) }}" 
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
                                               value="{{ old('position', $user->position) }}" 
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
                                               value="{{ old('phone', $user->phone) }}" 
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
                                                   {{ old('email_notifications', $user->email_notifications) ? 'checked' : '' }}>
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
                                          placeholder="Enter a brief biography (optional)">{{ old('bio', $user->bio) }}</textarea>
                                @error('bio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    <span id="bioCount">0</span>/1000 characters
                                </div>
                            </div>
                        </div>

                        <!-- User Status Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-user-check me-2"></i>User Status</h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">
                                            Status <span class="required">*</span>
                                        </label>
                                        <select class="form-select @error('status') is-invalid @enderror" 
                                                id="status" 
                                                name="status" 
                                                required>
                                            <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>
                                                Active - User can login and access the system
                                            </option>
                                            <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>
                                                Suspended - User cannot login but data is preserved
                                            </option>
                                            <option value="pending" {{ old('status', $user->status) === 'pending' ? 'selected' : '' }}>
                                                Pending - User account is not yet activated
                                            </option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Account Information</label>
                                        <div class="bg-secondary p-3 rounded">
                                            <div class="row text-sm">
                                                <div class="col-6">
                                                    <strong>Created:</strong><br>
                                                    {{ $user->created_at->format('M d, Y H:i') }}
                                                </div>
                                                <div class="col-6">
                                                    <strong>Last Login:</strong><br>
                                                    {{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                                           {{ in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
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

                        <!-- Password Reset Section -->
                        <div class="password-section">
                            <h6><i class="fas fa-key me-2"></i>Password Management</h6>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="reset_password" 
                                       name="reset_password" 
                                       value="1">
                                <label class="form-check-label" for="reset_password">
                                    Generate new temporary password
                                </label>
                            </div>
                            <div class="form-text text-muted mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                If checked, a new temporary password will be generated and the user will be required to change it on next login.
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                Cancel
                            </a>
                            
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-1"></i>
                                Update User
                            </button>
                        </div>
                    </form>

                    <!-- Danger Zone -->
                    @if($user->status !== 'deleted')
                        <div class="danger-zone mt-4">
                            <h6 class="text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Danger Zone
                            </h6>
                            <p class="mb-3 text-light">
                                These actions are irreversible. Please proceed with caution.
                            </p>
                            
                            <div class="d-flex gap-2">
                                @if($user->status !== 'suspended')
                                    <button type="button" 
                                            class="btn btn-warning btn-sm" 
                                            onclick="toggleUserStatus('{{ $user->id }}', 'suspend')">
                                        <i class="fas fa-user-slash me-1"></i>
                                        Suspend User
                                    </button>
                                @else
                                    <button type="button" 
                                            class="btn btn-success btn-sm" 
                                            onclick="toggleUserStatus('{{ $user->id }}', 'activate')">
                                        <i class="fas fa-user-check me-1"></i>
                                        Activate User
                                    </button>
                                @endif
                                
                                <button type="button" 
                                        class="btn btn-danger btn-sm" 
                                        onclick="deleteUser('{{ $user->id }}')">
                                    <i class="fas fa-trash me-1"></i>
                                    Delete User
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Toggle Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalTitle">Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="statusModalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone.
                </div>
                <p>Are you sure you want to delete this user? This will:</p>
                <ul>
                    <li>Soft delete the user account</li>
                    <li>Prevent the user from logging in</li>
                    <li>Preserve user data for audit purposes</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>
                    Delete User
                </button>
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
    $('#editUserForm').on('submit', function(e) {
        const submitBtn = $('#submitBtn');
        
        // Disable submit button to prevent double submission
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Updating User...');
        
        // Re-enable button after 3 seconds in case of issues
        setTimeout(function() {
            submitBtn.prop('disabled', false);
            submitBtn.html('<i class="fas fa-save me-1"></i>Update User');
        }, 3000);
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

// Toggle user status function
function toggleUserStatus(userId, action) {
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    const title = document.getElementById('statusModalTitle');
    const body = document.getElementById('statusModalBody');
    const confirmBtn = document.getElementById('confirmStatusBtn');
    
    if (action === 'suspend') {
        title.textContent = 'Suspend User';
        body.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> This will suspend the user account.
            </div>
            <p>Are you sure you want to suspend this user? This will:</p>
            <ul>
                <li>Prevent the user from logging in</li>
                <li>Preserve all user data</li>
                <li>Allow reactivation later</li>
            </ul>
        `;
        confirmBtn.className = 'btn btn-warning';
        confirmBtn.innerHTML = '<i class="fas fa-user-slash me-1"></i>Suspend User';
    } else {
        title.textContent = 'Activate User';
        body.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Confirmation:</strong> This will activate the user account.
            </div>
            <p>Are you sure you want to activate this user? This will:</p>
            <ul>
                <li>Allow the user to log in</li>
                <li>Restore full access to the system</li>
                <li>Resume all user privileges</li>
            </ul>
        `;
        confirmBtn.className = 'btn btn-success';
        confirmBtn.innerHTML = '<i class="fas fa-user-check me-1"></i>Activate User';
    }
    
    confirmBtn.onclick = function() {
        // Make AJAX request to toggle status
        fetch(`/admin/users/${userId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ action: action })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to update user status'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating user status');
        });
        
        modal.hide();
    };
    
    modal.show();
}

// Delete user function
function deleteUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    confirmBtn.onclick = function() {
        // Make AJAX request to delete user
        fetch(`/admin/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/admin/users';
            } else {
                alert('Error: ' + (data.message || 'Failed to delete user'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting user');
        });
        
        modal.hide();
    };
    
    modal.show();
}
</script>
@endpush