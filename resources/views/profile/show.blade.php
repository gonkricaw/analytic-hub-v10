@extends('layouts.app')

@section('title', 'My Profile')

@section('breadcrumb')
    <li class="breadcrumb-item active">My Profile</li>
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
        color: white;
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
        border: 4px solid rgba(255, 255, 255, 0.3);
        overflow: hidden;
        position: relative;
        background: rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .avatar-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .avatar-placeholder {
        font-size: 2.5rem;
        font-weight: bold;
        color: rgba(255, 255, 255, 0.8);
    }
    
    .profile-info h2 {
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .profile-info .text-muted {
        color: rgba(255, 255, 255, 0.7) !important;
    }
    
    .info-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
    }
    
    .info-card h5 {
        color: #495057;
        margin-bottom: 1rem;
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.5rem;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 500;
        color: #6c757d;
        min-width: 120px;
    }
    
    .info-value {
        color: #495057;
        flex: 1;
        text-align: right;
    }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-suspended {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .activity-item {
        padding: 1rem;
        border-left: 3px solid #e9ecef;
        margin-bottom: 1rem;
        background: #f8f9fa;
        border-radius: 0 0.25rem 0.25rem 0;
    }
    
    .activity-item.recent {
        border-left-color: #28a745;
        background: #f8fff9;
    }
    
    .activity-time {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .btn-edit-profile {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 0.25rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-edit-profile:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        color: white;
    }
    
    .notification-toggle {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .notification-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: #28a745;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
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
                        @if($user->avatar && $user->avatar->file_url)
                            <img src="{{ $user->avatar->file_url }}" alt="{{ $user->full_name }}" id="profileAvatar">
                        @else
                            <div class="avatar-placeholder">
                                {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="profile-info">
                        <h2>{{ $user->full_name }}</h2>
                        <p class="text-muted mb-2">{{ $user->email }}</p>
                        @if($user->position)
                            <p class="text-muted mb-2">{{ $user->position }}</p>
                        @endif
                        @if($user->department)
                            <p class="text-muted mb-0">{{ $user->department }}</p>
                        @endif
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <a href="{{ route('profile.edit') }}" class="btn btn-edit-profile">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="info-card">
                <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                
                <div class="info-row">
                    <span class="info-label">Full Name:</span>
                    <span class="info-value">{{ $user->full_name }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $user->email }}</span>
                </div>
                
                @if($user->phone)
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">{{ $user->phone }}</span>
                </div>
                @endif
                
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
                
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-{{ $user->status }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Account Information -->
        <div class="col-md-6">
            <div class="info-card">
                <h5><i class="fas fa-cog me-2"></i>Account Settings</h5>
                
                <div class="info-row">
                    <span class="info-label">Member Since:</span>
                    <span class="info-value">{{ $user->created_at->format('M d, Y') }}</span>
                </div>
                
                @if($user->last_login_at)
                <div class="info-row">
                    <span class="info-label">Last Login:</span>
                    <span class="info-value">{{ $user->last_login_at->diffForHumans() }}</span>
                </div>
                @endif
                
                @if($user->password_changed_at)
                <div class="info-row">
                    <span class="info-label">Password Changed:</span>
                    <span class="info-value">{{ $user->password_changed_at->diffForHumans() }}</span>
                </div>
                @endif
                
                <div class="info-row">
                    <span class="info-label">Email Notifications:</span>
                    <span class="info-value">
                        <label class="notification-toggle">
                            <input type="checkbox" id="emailNotifications" 
                                   {{ $user->email_notifications ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Roles:</span>
                    <span class="info-value">
                        @forelse($user->roles as $role)
                            <span class="badge bg-primary me-1">{{ $role->name }}</span>
                        @empty
                            <span class="text-muted">No roles assigned</span>
                        @endforelse
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Biography -->
    @if($user->bio)
    <div class="row">
        <div class="col-12">
            <div class="info-card">
                <h5><i class="fas fa-info-circle me-2"></i>Biography</h5>
                <p class="mb-0">{{ $user->bio }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="info-card">
                <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                
                @forelse($recentActivities as $activity)
                    <div class="activity-item {{ $activity->created_at->isToday() ? 'recent' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}</strong>
                                <p class="mb-1">{{ $activity->description }}</p>
                                <small class="activity-time">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $activity->created_at->diffForHumans() }}
                                </small>
                            </div>
                            @if($activity->ip_address)
                                <small class="text-muted">{{ $activity->ip_address }}</small>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No recent activity found.</p>
                @endforelse
                
                @if($recentActivities->count() >= 10)
                    <div class="text-center mt-3">
                        <button class="btn btn-outline-primary" id="loadMoreActivity">
                            <i class="fas fa-plus me-2"></i>Load More Activity
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Handle email notification toggle
    $('#emailNotifications').change(function() {
        const isEnabled = $(this).is(':checked');
        
        $.ajax({
            url: '{{ route("profile.notifications") }}',
            method: 'POST',
            data: {
                email_notifications: isEnabled,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error('Failed to update notification preferences.');
                    // Revert the toggle
                    $('#emailNotifications').prop('checked', !isEnabled);
                }
            },
            error: function() {
                toastr.error('Failed to update notification preferences.');
                // Revert the toggle
                $('#emailNotifications').prop('checked', !isEnabled);
            }
        });
    });
    
    // Load more activity
    let activityPage = 1;
    $('#loadMoreActivity').click(function() {
        const button = $(this);
        const originalText = button.html();
        
        button.html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');
        button.prop('disabled', true);
        
        activityPage++;
        
        $.ajax({
            url: '{{ route("profile.activity") }}',
            method: 'GET',
            data: {
                page: activityPage,
                per_page: 10
            },
            success: function(response) {
                if (response.success && response.data.data.length > 0) {
                    response.data.data.forEach(function(activity) {
                        const activityHtml = `
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>${activity.activity_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</strong>
                                        <p class="mb-1">${activity.description}</p>
                                        <small class="activity-time">
                                            <i class="fas fa-clock me-1"></i>
                                            ${moment(activity.created_at).fromNow()}
                                        </small>
                                    </div>
                                    ${activity.ip_address ? `<small class="text-muted">${activity.ip_address}</small>` : ''}
                                </div>
                            </div>
                        `;
                        button.parent().before(activityHtml);
                    });
                    
                    // Hide button if no more data
                    if (response.data.data.length < 10 || !response.data.next_page_url) {
                        button.parent().hide();
                    }
                } else {
                    button.parent().hide();
                }
            },
            error: function() {
                toastr.error('Failed to load more activity.');
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush