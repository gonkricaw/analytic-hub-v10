@extends('layouts.app')

@section('title', 'User Details')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User Management</a></li>
    <li class="breadcrumb-item active">{{ $user->full_name }}</li>
@endsection

@push('styles')
<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 0.5rem;
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.1);
        z-index: 1;
    }
    .profile-content {
        position: relative;
        z-index: 2;
    }
    .avatar-container {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: #4a5568;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #e2e8f0;
        border: 4px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 1rem;
    }
    .info-card {
        background-color: #2d3748;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #4a5568;
    }
    .info-card h5 {
        color: #e2e8f0;
        border-bottom: 2px solid #4a5568;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #4a5568;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        font-weight: 600;
        color: #a0aec0;
        min-width: 140px;
    }
    .info-value {
        color: #e2e8f0;
        text-align: right;
        flex: 1;
    }
    .status-badge {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-weight: 600;
    }
    .role-badge {
        background-color: #3182ce;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        margin: 0.25rem;
        display: inline-block;
    }
    .activity-item {
        background-color: #4a5568;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
        border-left: 4px solid #3182ce;
    }
    .activity-time {
        font-size: 0.875rem;
        color: #a0aec0;
    }
    .stats-card {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        border-radius: 0.5rem;
        padding: 1.5rem;
        text-align: center;
        color: white;
        margin-bottom: 1rem;
    }
    .stats-number {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .stats-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-content">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="avatar-container mx-auto">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" alt="{{ $user->full_name }}" class="w-100 h-100 rounded-circle object-fit-cover">
                        @else
                            {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <h2 class="text-white mb-2">{{ $user->full_name }}</h2>
                    <p class="text-white-50 mb-3">{{ $user->email }}</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="status-badge 
                            @if($user->status === 'active') bg-success
                            @elseif($user->status === 'suspended') bg-warning text-dark
                            @elseif($user->status === 'pending') bg-info
                            @else bg-danger
                            @endif">
                            {{ ucfirst($user->status) }}
                        </span>
                        @if($user->email_verified_at)
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Email Verified
                            </span>
                        @else
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-exclamation-circle me-1"></i>Email Unverified
                            </span>
                        @endif
                        @if($user->is_first_login)
                            <span class="badge bg-info">
                                <i class="fas fa-user-clock me-1"></i>First Login Pending
                            </span>
                        @endif
                    </div>
                    @if($user->position || $user->department)
                        <p class="text-white-50 mb-0">
                            @if($user->position)
                                {{ $user->position }}
                                @if($user->department) • @endif
                            @endif
                            @if($user->department)
                                {{ $user->department }}
                            @endif
                        </p>
                    @endif
                </div>
                <div class="col-md-3 text-end">
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-light">
                            <i class="fas fa-edit me-1"></i>
                            Edit User
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="info-card">
                <h5><i class="fas fa-user me-2"></i>Basic Information</h5>
                
                <div class="info-row">
                    <span class="info-label">Full Name:</span>
                    <span class="info-value">{{ $user->full_name }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">
                        {{ $user->email }}
                        @if($user->email_verified_at)
                            <i class="fas fa-check-circle text-success ms-1" title="Verified on {{ $user->email_verified_at->format('M d, Y H:i') }}"></i>
                        @else
                            <i class="fas fa-exclamation-circle text-warning ms-1" title="Not verified"></i>
                        @endif
                    </span>
                </div>
                
                @if($user->username)
                    <div class="info-row">
                        <span class="info-label">Username:</span>
                        <span class="info-value">{{ $user->username }}</span>
                    </div>
                @endif
                
                @if($user->phone)
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">{{ $user->phone }}</span>
                    </div>
                @endif
                
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge 
                            @if($user->status === 'active') bg-success
                            @elseif($user->status === 'suspended') bg-warning text-dark
                            @elseif($user->status === 'pending') bg-info
                            @else bg-danger
                            @endif">
                            {{ ucfirst($user->status) }}
                        </span>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Notifications:</span>
                    <span class="info-value">
                        @if($user->email_notifications)
                            <span class="badge bg-success">Enabled</span>
                        @else
                            <span class="badge bg-secondary">Disabled</span>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Professional Information -->
            @if($user->department || $user->position || $user->bio)
                <div class="info-card">
                    <h5><i class="fas fa-briefcase me-2"></i>Professional Information</h5>
                    
                    @if($user->department)
                        <div class="info-row">
                            <span class="info-label">Department:</span>
                            <span class="info-value">{{ $user->department }}</span>
                        </div>
                    @endif
                    
                    @if($user->position)
                        <div class="info-row">
                            <span class="info-label">Position:</span>
                            <span class="info-value">{{ $user->position }}</span>
                        </div>
                    @endif
                    
                    @if($user->bio)
                        <div class="info-row">
                            <span class="info-label">Biography:</span>
                            <span class="info-value">{{ $user->bio }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Roles & Permissions -->
            <div class="info-card">
                <h5><i class="fas fa-user-shield me-2"></i>Roles & Permissions</h5>
                
                @if($user->roles->count() > 0)
                    <div class="info-row">
                        <span class="info-label">Assigned Roles:</span>
                        <div class="info-value">
                            @foreach($user->roles as $role)
                                <span class="role-badge">{{ $role->display_name }}</span>
                            @endforeach
                        </div>
                    </div>
                    
                    @if($user->permissions->count() > 0)
                        <div class="info-row">
                            <span class="info-label">Direct Permissions:</span>
                            <div class="info-value">
                                @foreach($user->permissions as $permission)
                                    <span class="badge bg-secondary m-1">{{ $permission->display_name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No roles assigned to this user.
                    </div>
                @endif
            </div>

            <!-- Recent Activity -->
            <div class="info-card">
                <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                
                @if($recentActivities->count() > 0)
                    @foreach($recentActivities as $activity)
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold text-white">{{ $activity->action }}</div>
                                    @if($activity->description)
                                        <div class="text-muted small">{{ $activity->description }}</div>
                                    @endif
                                    @if($activity->ip_address)
                                        <div class="text-muted small">
                                            <i class="fas fa-map-marker-alt me-1"></i>{{ $activity->ip_address }}
                                        </div>
                                    @endif
                                </div>
                                <div class="activity-time">
                                    {{ $activity->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>
                            View All Activity
                        </a>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        No recent activity recorded.
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Stats -->
            <div class="row">
                <div class="col-6">
                    <div class="stats-card">
                        <div class="stats-number">{{ $user->activities->count() }}</div>
                        <div class="stats-label">Total Activities</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stats-card">
                        <div class="stats-number">{{ $user->roles->count() }}</div>
                        <div class="stats-label">Assigned Roles</div>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="info-card">
                <h5><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                
                <div class="info-row">
                    <span class="info-label">User ID:</span>
                    <span class="info-value"><code>{{ $user->id }}</code></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span class="info-value">
                        {{ $user->created_at->format('M d, Y H:i') }}
                        <small class="text-muted d-block">{{ $user->created_at->diffForHumans() }}</small>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Last Updated:</span>
                    <span class="info-value">
                        {{ $user->updated_at->format('M d, Y H:i') }}
                        <small class="text-muted d-block">{{ $user->updated_at->diffForHumans() }}</small>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Last Login:</span>
                    <span class="info-value">
                        @if($user->last_login_at)
                            {{ $user->last_login_at->format('M d, Y H:i') }}
                            <small class="text-muted d-block">{{ $user->last_login_at->diffForHumans() }}</small>
                        @else
                            <span class="text-muted">Never logged in</span>
                        @endif
                    </span>
                </div>
                
                @if($user->password_changed_at)
                    <div class="info-row">
                        <span class="info-label">Password Changed:</span>
                        <span class="info-value">
                            {{ $user->password_changed_at->format('M d, Y H:i') }}
                            <small class="text-muted d-block">{{ $user->password_changed_at->diffForHumans() }}</small>
                        </span>
                    </div>
                @endif
                
                @if($user->terms_accepted_at)
                    <div class="info-row">
                        <span class="info-label">Terms Accepted:</span>
                        <span class="info-value">
                            {{ $user->terms_accepted_at->format('M d, Y H:i') }}
                            <small class="text-muted d-block">{{ $user->terms_accepted_at->diffForHumans() }}</small>
                        </span>
                    </div>
                @endif
            </div>

            <!-- Security Information -->
            <div class="info-card">
                <h5><i class="fas fa-shield-alt me-2"></i>Security Information</h5>
                
                <div class="info-row">
                    <span class="info-label">Email Verified:</span>
                    <span class="info-value">
                        @if($user->email_verified_at)
                            <span class="badge bg-success">Yes</span>
                            <small class="text-muted d-block">{{ $user->email_verified_at->format('M d, Y H:i') }}</small>
                        @else
                            <span class="badge bg-warning text-dark">No</span>
                        @endif
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">First Login:</span>
                    <span class="info-value">
                        @if($user->is_first_login)
                            <span class="badge bg-warning text-dark">Pending</span>
                        @else
                            <span class="badge bg-success">Completed</span>
                        @endif
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Terms Accepted:</span>
                    <span class="info-value">
                        @if($user->terms_accepted)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-warning text-dark">No</span>
                        @endif
                    </span>
                </div>
                
                @if($user->locked_until)
                    <div class="info-row">
                        <span class="info-label">Account Locked:</span>
                        <span class="info-value">
                            <span class="badge bg-danger">Until {{ $user->locked_until->format('M d, Y H:i') }}</span>
                        </span>
                    </div>
                @endif
            </div>

            <!-- Quick Actions -->
            <div class="info-card">
                <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>
                        Edit User
                    </a>
                    
                    @if($user->status === 'active')
                        <button type="button" class="btn btn-warning" onclick="toggleUserStatus('{{ $user->id }}', 'suspend')">
                            <i class="fas fa-user-slash me-1"></i>
                            Suspend User
                        </button>
                    @elseif($user->status === 'suspended')
                        <button type="button" class="btn btn-success" onclick="toggleUserStatus('{{ $user->id }}', 'activate')">
                            <i class="fas fa-user-check me-1"></i>
                            Activate User
                        </button>
                    @endif
                    
                    @if(!$user->email_verified_at)
                        <button type="button" class="btn btn-info" onclick="resendVerification('{{ $user->id }}')">
                            <i class="fas fa-envelope me-1"></i>
                            Resend Verification
                        </button>
                    @endif
                    
                    <button type="button" class="btn btn-secondary" onclick="resetPassword('{{ $user->id }}')">
                        <i class="fas fa-key me-1"></i>
                        Reset Password
                    </button>
                    
                    @if($user->status !== 'deleted')
                        <button type="button" class="btn btn-danger" onclick="deleteUser('{{ $user->id }}')">
                            <i class="fas fa-trash me-1"></i>
                            Delete User
                        </button>
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

<!-- Generic Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle">Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="actionModalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmActionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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
        performAction(`/admin/users/${userId}/toggle-status`, { action: action }, 'User status updated successfully!');
        modal.hide();
    };
    
    modal.show();
}

// Reset password function
function resetPassword(userId) {
    const modal = new bootstrap.Modal(document.getElementById('actionModal'));
    const title = document.getElementById('actionModalTitle');
    const body = document.getElementById('actionModalBody');
    const confirmBtn = document.getElementById('confirmActionBtn');
    
    title.textContent = 'Reset Password';
    body.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Information:</strong> This will generate a new temporary password.
        </div>
        <p>Are you sure you want to reset this user's password? This will:</p>
        <ul>
            <li>Generate a new temporary password</li>
            <li>Force the user to change password on next login</li>
            <li>Send notification email to the user</li>
        </ul>
    `;
    confirmBtn.className = 'btn btn-warning';
    confirmBtn.innerHTML = '<i class="fas fa-key me-1"></i>Reset Password';
    
    confirmBtn.onclick = function() {
        performAction(`/admin/users/${userId}/reset-password`, {}, 'Password reset successfully!');
        modal.hide();
    };
    
    modal.show();
}

// Resend verification function
function resendVerification(userId) {
    const modal = new bootstrap.Modal(document.getElementById('actionModal'));
    const title = document.getElementById('actionModalTitle');
    const body = document.getElementById('actionModalBody');
    const confirmBtn = document.getElementById('confirmActionBtn');
    
    title.textContent = 'Resend Email Verification';
    body.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-envelope me-2"></i>
            <strong>Information:</strong> This will send a new verification email.
        </div>
        <p>Are you sure you want to resend the email verification? This will:</p>
        <ul>
            <li>Send a new verification email to the user</li>
            <li>Invalidate any previous verification links</li>
            <li>Allow the user to verify their email address</li>
        </ul>
    `;
    confirmBtn.className = 'btn btn-info';
    confirmBtn.innerHTML = '<i class="fas fa-envelope me-1"></i>Resend Verification';
    
    confirmBtn.onclick = function() {
        performAction(`/admin/users/${userId}/resend-verification`, {}, 'Verification email sent successfully!');
        modal.hide();
    };
    
    modal.show();
}

// Delete user function
function deleteUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('actionModal'));
    const title = document.getElementById('actionModalTitle');
    const body = document.getElementById('actionModalBody');
    const confirmBtn = document.getElementById('confirmActionBtn');
    
    title.textContent = 'Delete User';
    body.innerHTML = `
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
    `;
    confirmBtn.className = 'btn btn-danger';
    confirmBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Delete User';
    
    confirmBtn.onclick = function() {
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

// Generic action performer
function performAction(url, data, successMessage) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>${successMessage}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container-fluid');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Reload page after a short delay
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            alert('Error: ' + (data.message || 'Action failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while performing the action');
    });
}
</script>
@endpush