<div class="notification-item {{ $notification->pivot->is_read ? 'read' : 'unread' }} {{ $notification->priority }}-priority p-3 mb-3 border rounded" 
     data-id="{{ $notification->id }}" 
     onclick="showNotification({{ $notification->id }})" 
     style="cursor: pointer;">
    
    <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
            <!-- Header -->
            <div class="d-flex align-items-center mb-2">
                <!-- Priority Indicator -->
                <span class="priority-indicator priority-{{ $notification->priority }}"></span>
                
                <!-- Type Badge -->
                <span class="badge notification-type-badge me-2
                    @if($notification->type === 'system') bg-primary
                    @elseif($notification->type === 'announcement') bg-info
                    @elseif($notification->type === 'alert') bg-danger
                    @else bg-secondary
                    @endif">
                    {{ ucfirst($notification->type) }}
                </span>
                
                <!-- Priority Badge -->
                @if($notification->priority === 'high')
                    <span class="badge bg-danger notification-type-badge me-2">High Priority</span>
                @endif
                
                <!-- Unread Indicator -->
                @if(!$notification->pivot->is_read)
                    <span class="badge bg-success notification-type-badge unread-indicator">New</span>
                @endif
                
                <!-- Category -->
                @if($notification->category)
                    <span class="badge bg-light text-dark notification-type-badge">{{ $notification->category }}</span>
                @endif
            </div>
            
            <!-- Title -->
            <h6 class="mb-2 {{ !$notification->pivot->is_read ? 'fw-bold' : '' }}">
                {{ $notification->title }}
            </h6>
            
            <!-- Message Preview -->
            <p class="mb-2 text-muted">
                {{ Str::limit(strip_tags($notification->message), 120) }}
            </p>
            
            <!-- Timestamp -->
            <div class="d-flex align-items-center notification-time">
                <i class="fas fa-clock me-1"></i>
                <span title="{{ $notification->created_at->format('M d, Y H:i:s') }}">
                    {{ $notification->created_at->diffForHumans() }}
                </span>
                
                @if($notification->expires_at)
                    <span class="ms-3 text-warning" title="Expires: {{ $notification->expires_at->format('M d, Y H:i:s') }}">
                        <i class="fas fa-hourglass-end me-1"></i>
                        @if($notification->expires_at->isFuture())
                            Expires {{ $notification->expires_at->diffForHumans() }}
                        @else
                            Expired
                        @endif
                    </span>
                @endif
            </div>
        </div>
        
        <!-- Actions -->
        <div class="notification-actions ms-3">
            <div class="btn-group-vertical" role="group">
                @if(!$notification->pivot->is_read)
                    <button type="button" class="btn btn-sm btn-outline-success" 
                            onclick="event.stopPropagation(); markAsRead({{ $notification->id }})" 
                            title="Mark as Read">
                        <i class="fas fa-check"></i>
                    </button>
                @endif
                
                <button type="button" class="btn btn-sm btn-outline-warning" 
                        onclick="event.stopPropagation(); dismissNotification({{ $notification->id }})" 
                        title="Dismiss">
                    <i class="fas fa-times"></i>
                </button>
                
                @if($notification->action_url)
                    <a href="{{ $notification->action_url }}" 
                       class="btn btn-sm btn-outline-primary" 
                       onclick="event.stopPropagation(); markAsRead({{ $notification->id }})" 
                       title="{{ $notification->action_text ?: 'View Details' }}" 
                       target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Action Button (if URL provided) -->
    @if($notification->action_url)
        <div class="mt-2">
            <a href="{{ $notification->action_url }}" 
               class="btn btn-sm btn-primary" 
               onclick="event.stopPropagation(); markAsRead({{ $notification->id }})" 
               target="_blank">
                <i class="fas fa-external-link-alt me-1"></i>
                {{ $notification->action_text ?: 'View Details' }}
            </a>
        </div>
    @endif
</div>