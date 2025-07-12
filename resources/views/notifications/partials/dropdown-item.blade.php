@if($notifications->count() > 0)
    @foreach($notifications as $notification)
        <div class="notification-bell-item {{ $notification->pivot->is_read ? 'read' : 'unread' }}" 
             data-id="{{ $notification->id }}"
             onclick="showNotificationFromDropdown({{ $notification->id }})">
            <div class="d-flex align-items-start">
                <!-- Notification Icon -->
                <div class="notification-icon me-3
                    @if($notification->type === 'system') bg-primary
                    @elseif($notification->type === 'announcement') bg-info
                    @elseif($notification->type === 'alert') bg-danger
                    @else bg-secondary
                    @endif">
                    @if($notification->type === 'system')
                        <i class="fas fa-cog"></i>
                    @elseif($notification->type === 'announcement')
                        <i class="fas fa-bullhorn"></i>
                    @elseif($notification->type === 'alert')
                        <i class="fas fa-exclamation-triangle"></i>
                    @else
                        <i class="fas fa-info-circle"></i>
                    @endif
                </div>
                
                <!-- Notification Content -->
                <div class="notification-content">
                    <div class="notification-title">
                        {{ Str::limit($notification->title, 40) }}
                        
                        <!-- Priority Indicator -->
                        @if($notification->priority === 'high')
                            <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">HIGH</span>
                        @endif
                    </div>
                    
                    <div class="notification-message">
                        {{ Str::limit(strip_tags($notification->message), 80) }}
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="notification-time">
                            <i class="fas fa-clock me-1"></i>
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                        
                        <!-- Actions -->
                        <div class="notification-actions">
                            @if(!$notification->pivot->is_read)
                                <button type="button" 
                                        class="btn btn-sm btn-outline-success mark-read-btn" 
                                        onclick="event.stopPropagation(); markNotificationAsReadFromDropdown({{ $notification->id }})" 
                                        title="Mark as Read">
                                    <i class="fas fa-check"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    
    @if($notifications->count() >= 5)
        <div class="text-center py-2 border-top">
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-link text-decoration-none">
                <i class="fas fa-plus me-1"></i>
                View {{ $totalCount - 5 }} more notifications
            </a>
        </div>
    @endif
@else
    <div class="notification-empty">
        <i class="fas fa-bell-slash"></i>
        <p class="mb-0">No notifications</p>
        <small class="text-muted">You're all caught up!</small>
    </div>
@endif